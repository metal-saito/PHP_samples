<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * 認証APIコントローラー
 * 
 * ユーザー登録、ログイン、ログアウト、プロフィール取得などの
 * 認証関連の機能を提供します。Laravel Sanctumを使用した
 * トークンベースの認証を実装しています。
 */
class AuthController extends Controller
{
    /**
     * トークン名の定数
     * 
     * Sanctumで生成するトークンに付与する名前
     */
    private const TOKEN_NAME = 'api-token';

    /**
     * 新規ユーザーの登録
     * 
     * 新しいユーザーアカウントを作成し、APIトークンを発行します。
     * パスワードは自動的にハッシュ化されます。
     *
     * @param Request $request リクエスト（name, email, password, password_confirmation）
     * @return JsonResponse 登録成功レスポンス（ユーザー情報とトークン）
     * @throws ValidationException バリデーションエラーの場合
     * 
     * @example POST /api/register
     * {
     *   "name": "山田太郎",
     *   "email": "yamada@example.com",
     *   "password": "password123",
     *   "password_confirmation": "password123"
     * }
     */
    public function register(Request $request): JsonResponse
    {
        // 入力データのバリデーション
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email', // メールアドレスの重複チェック
            ],
            'password' => [
                'required',
                'string',
                'confirmed', // password_confirmationフィールドと一致する必要がある
                Password::min(8) // 最低8文字
                    ->letters()   // 英字を含む
                    ->numbers(),  // 数字を含む
            ],
        ], [
            'name.required' => '名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードは必須です。',
            'password.confirmed' => 'パスワードが確認用と一致しません。',
        ]);

        // 新しいユーザーを作成
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // パスワードをハッシュ化
        ]);

        // APIトークンを生成
        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        // 成功レスポンスを返却（201 Created）
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $this->formatUserData($user),
            'token' => $token,
        ], 201);
    }

    /**
     * ユーザーのログイン
     * 
     * メールアドレスとパスワードで認証を行い、
     * 成功した場合はAPIトークンを発行します。
     *
     * @param Request $request リクエスト（email, password）
     * @return JsonResponse ログイン成功レスポンス（ユーザー情報とトークン）
     * @throws ValidationException 認証失敗の場合
     * 
     * @example POST /api/login
     * {
     *   "email": "yamada@example.com",
     *   "password": "password123"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        // 入力データのバリデーション
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ], [
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードは必須です。',
        ]);

        // 認証を試行
        if (!Auth::attempt($credentials)) {
            // 認証失敗：エラーメッセージを投げる（422 Unprocessable Entity）
            throw ValidationException::withMessages([
                'email' => ['メールアドレスまたはパスワードが正しくありません。'],
            ]);
        }

        // 認証成功：ユーザーを取得
        /** @var User $user */
        $user = Auth::user();

        // 既存のトークンを削除（セキュリティ向上のため）
        // オプション：必要に応じてコメントアウト
        // $user->tokens()->delete();

        // 新しいAPIトークンを生成
        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        // 成功レスポンスを返却
        return response()->json([
            'message' => 'Login successful',
            'user' => $this->formatUserData($user),
            'token' => $token,
        ]);
    }

    /**
     * ユーザーのログアウト
     * 
     * 現在使用中のAPIトークンを無効化します。
     * トークンが削除されるため、以降そのトークンでのアクセスはできなくなります。
     *
     * @param Request $request リクエスト（認証済み）
     * @return JsonResponse ログアウト成功メッセージ
     * 
     * @example POST /api/logout
     * Headers: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        // 現在のアクセストークンを削除
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * 認証済みユーザー情報の取得
     * 
     * 現在認証されているユーザーのプロフィール情報を取得します。
     *
     * @param Request $request リクエスト（認証済み）
     * @return JsonResponse ユーザー情報
     * 
     * @example GET /api/me
     * Headers: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUserData($user, true),
        ]);
    }

    /**
     * ユーザーデータのフォーマット
     * 
     * APIレスポンスで返却するユーザーデータの形式を統一します。
     * センシティブな情報（パスワードなど）は含まれません。
     *
     * @param User $user ユーザーモデル
     * @param bool $includeTimestamps タイムスタンプを含めるかどうか（デフォルト: false）
     * @return array<string, mixed> フォーマット済みユーザーデータ
     */
    private function formatUserData(User $user, bool $includeTimestamps = false): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        // タイムスタンプが必要な場合は追加（プロフィール取得時など）
        if ($includeTimestamps) {
            $data['created_at'] = $user->created_at?->toIso8601String();
            $data['updated_at'] = $user->updated_at?->toIso8601String();
        }

        return $data;
    }
}
