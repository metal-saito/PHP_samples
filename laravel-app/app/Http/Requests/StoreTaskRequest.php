<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * タスク作成リクエスト
 * 
 * 新しいタスクを作成する際の入力データのバリデーションを行います。
 * - タイトルは必須
 * - ステータスと優先度はモデルで定義された有効な値のみ許可
 * - 期限は現在より未来の日時のみ許可
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     * 
     * 認証済みのユーザーは誰でもタスクを作成できます。
     * （認証はミドルウェアで行われるため、ここでは常にtrueを返す）
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールの定義
     * 
     * タスク作成時に適用されるバリデーションルールを定義します。
     * モデルの定数を使用することで、値の整合性を保ちます。
     *
     * @return array<string, mixed> バリデーションルールの配列
     */
    public function rules(): array
    {
        return [
            // タイトル：必須、文字列、最大255文字
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            
            // 説明：任意、文字列
            'description' => [
                'nullable',
                'string',
                'max:10000', // 最大10,000文字
            ],
            
            // ステータス：任意、モデルで定義された有効な値のみ
            // 指定しない場合はコントローラーでデフォルト値（pending）が設定される
            'status' => [
                'nullable',
                'string',
                Rule::in(Task::VALID_STATUSES),
            ],
            
            // 優先度：任意、モデルで定義された有効な値のみ
            // 指定しない場合はコントローラーでデフォルト値（medium）が設定される
            'priority' => [
                'nullable',
                'string',
                Rule::in(Task::VALID_PRIORITIES),
            ],
            
            // 期限：任意、日付形式、現在より未来の日時
            'due_date' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     * 
     * ユーザーフレンドリーな日本語エラーメッセージを定義します。
     *
     * @return array<string, string> カスタムメッセージの連想配列
     */
    public function messages(): array
    {
        return [
            // タイトル関連
            'title.required' => 'タスクのタイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            
            // 説明関連
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は10,000文字以内で入力してください。',
            
            // ステータス関連
            'status.in' => 'ステータスは ' . implode(', ', Task::VALID_STATUSES) . ' のいずれかを指定してください。',
            
            // 優先度関連
            'priority.in' => '優先度は ' . implode(', ', Task::VALID_PRIORITIES) . ' のいずれかを指定してください。',
            
            // 期限関連
            'due_date.date' => '期限は有効な日付形式で入力してください。',
            'due_date.after' => '期限は現在より未来の日時を指定してください。',
        ];
    }

    /**
     * バリデーション属性名のカスタマイズ
     * 
     * エラーメッセージで使用される属性名を日本語化します。
     *
     * @return array<string, string> 属性名の連想配列
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'description' => '説明',
            'status' => 'ステータス',
            'priority' => '優先度',
            'due_date' => '期限',
        ];
    }
}
