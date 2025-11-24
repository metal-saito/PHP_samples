<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * タスク管理APIコントローラー
 * 
 * タスクのCRUD操作と統計情報の取得を提供します。
 * すべてのエンドポイントは認証が必要で、ユーザーは自分のタスクのみ操作できます。
 * ビジネスロジックはTaskServiceに委譲しています。
 */
class TaskController extends Controller
{
    /**
     * TaskServiceのインスタンス
     */
    public function __construct(
        private readonly TaskService $taskService
    ) {
    }
    /**
     * タスク一覧の取得
     * 
     * 認証済みユーザーのタスク一覧を取得します。
     * ステータス、優先度、期限切れなどでフィルタリングが可能で、
     * ソート順やページネーションもカスタマイズできます。
     *
     * @param Request $request リクエスト（クエリパラメータ：status, priority, overdue, sort_by, sort_order, per_page）
     * @return AnonymousResourceCollection タスクのコレクション
     * 
     * @example GET /api/tasks?status=pending&priority=high&per_page=20
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // 認証済みユーザーのタスクをベースクエリとして構築
        // with('user')でN+1問題を回避
        $query = Task::query()
            ->with('user')
            ->forUser($request->user()->id);

        // フィルター：ステータスでの絞り込み
        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        // フィルター：優先度での絞り込み
        if ($request->filled('priority')) {
            $query->priority($request->input('priority'));
        }

        // フィルター：期限切れタスクのみ表示
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // ソート設定（デフォルト：作成日時の降順）
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // 許可されたソート項目のみ使用（セキュリティ対策）
        $allowedSortFields = ['created_at', 'updated_at', 'due_date', 'priority', 'status', 'title'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // ページネーション（デフォルト：15件/ページ、最大100件）
        $perPage = min((int) $request->input('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    /**
     * タスクの作成
     * 
     * 新しいタスクを作成します。
     * タイトルは必須で、説明・ステータス・優先度・期限は任意です。
     *
     * @param StoreTaskRequest $request バリデーション済みリクエスト
     * @return TaskResource 作成されたタスクのリソース
     * 
     * @example POST /api/tasks {"title": "新規タスク", "priority": "high", "due_date": "2024-12-31"}
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        // バリデーション済みデータからタスクを作成
        // user_idは認証済みユーザーから自動設定
        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => $request->input('status', Task::STATUS_PENDING),
            'priority' => $request->input('priority', Task::PRIORITY_MEDIUM),
            'due_date' => $request->input('due_date'),
        ]);

        // 作成したタスクをユーザー情報と共にロードして返却
        return new TaskResource($task->load('user'));
    }

    /**
     * タスクの詳細取得
     * 
     * 指定されたタスクの詳細情報を取得します。
     * ポリシーにより、所有者のみがアクセス可能です。
     *
     * @param Request $request リクエスト
     * @param Task $task 取得するタスク（ルートモデルバインディング）
     * @return TaskResource タスクのリソース
     * @throws \Illuminate\Auth\Access\AuthorizationException 権限がない場合
     * 
     * @example GET /api/tasks/1
     */
    public function show(Request $request, Task $task): TaskResource
    {
        // ポリシーで所有権をチェック（所有者以外は403エラー）
        $this->authorize('view', $task);
        
        // タスクをユーザー情報と共に返却
        return new TaskResource($task->load('user'));
    }

    /**
     * タスクの更新
     * 
     * 既存のタスクを更新します。
     * すべてのフィールドは任意で、指定されたフィールドのみ更新されます。
     *
     * @param UpdateTaskRequest $request バリデーション済みリクエスト
     * @param Task $task 更新するタスク（ルートモデルバインディング）
     * @return TaskResource 更新されたタスクのリソース
     * @throws \Illuminate\Auth\Access\AuthorizationException 権限がない場合
     * 
     * @example PUT /api/tasks/1 {"status": "completed"}
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        // ポリシーで所有権をチェック
        $this->authorize('update', $task);

        // バリデーション済みデータで更新
        $task->update($request->validated());

        // 更新後のタスクをユーザー情報と共に返却
        return new TaskResource($task->load('user'));
    }

    /**
     * タスクの削除
     * 
     * 指定されたタスクを削除します。
     * 削除は物理削除で、復元はできません。
     *
     * @param Request $request リクエスト
     * @param Task $task 削除するタスク（ルートモデルバインディング）
     * @return JsonResponse 削除成功メッセージ
     * @throws \Illuminate\Auth\Access\AuthorizationException 権限がない場合
     * 
     * @example DELETE /api/tasks/1
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // ポリシーで所有権をチェック
        $this->authorize('delete', $task);

        // タスクを削除
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * タスクの統計情報取得
     * 
     * 認証済みユーザーのタスクに関する統計情報を取得します。
     * - 総タスク数
     * - ステータス別カウント
     * - 優先度別カウント
     * - 期限切れタスク数
     *
     * @param Request $request リクエスト
     * @return JsonResponse 統計情報のJSON
     * 
     * @example GET /api/tasks-statistics
     * @example Response:
     * {
     *   "total": 15,
     *   "by_status": {"pending": 5, "in_progress": 3, "completed": 6, "cancelled": 1},
     *   "by_priority": {"low": 2, "medium": 8, "high": 4, "urgent": 1},
     *   "overdue": 2
     * }
     */
    public function statistics(Request $request): JsonResponse
    {
        // TaskServiceに統計情報の取得を委譲
        // ビジネスロジックをサービス層に集約することで、
        // コントローラーをシンプルに保ち、再利用性とテスタビリティを向上
        $stats = $this->taskService->getStatistics($request->user());

        return response()->json($stats);
    }
}
