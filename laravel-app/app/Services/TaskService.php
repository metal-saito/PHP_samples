<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * タスクサービスクラス
 * 
 * タスクに関するビジネスロジックを集約します。
 * コントローラーから複雑なロジックを分離し、再利用性とテスタビリティを向上させます。
 */
class TaskService
{
    /**
     * ユーザーのタスク統計情報を取得
     * 
     * 効率的なクエリでユーザーのタスク統計情報を計算します。
     * 複数のクエリを最小限に抑えることでパフォーマンスを向上させています。
     *
     * @param User $user 対象ユーザー
     * @return array<string, mixed> 統計情報の配列
     */
    public function getStatistics(User $user): array
    {
        $userId = $user->id;

        // ステータス別カウントを一度のクエリで取得
        $statusCounts = Task::forUser($userId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 優先度別カウントを一度のクエリで取得
        $priorityCounts = Task::forUser($userId)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // 統計情報を構築
        return [
            'total' => Task::forUser($userId)->count(),
            'by_status' => [
                Task::STATUS_PENDING => $statusCounts[Task::STATUS_PENDING] ?? 0,
                Task::STATUS_IN_PROGRESS => $statusCounts[Task::STATUS_IN_PROGRESS] ?? 0,
                Task::STATUS_COMPLETED => $statusCounts[Task::STATUS_COMPLETED] ?? 0,
                Task::STATUS_CANCELLED => $statusCounts[Task::STATUS_CANCELLED] ?? 0,
            ],
            'by_priority' => [
                Task::PRIORITY_LOW => $priorityCounts[Task::PRIORITY_LOW] ?? 0,
                Task::PRIORITY_MEDIUM => $priorityCounts[Task::PRIORITY_MEDIUM] ?? 0,
                Task::PRIORITY_HIGH => $priorityCounts[Task::PRIORITY_HIGH] ?? 0,
                Task::PRIORITY_URGENT => $priorityCounts[Task::PRIORITY_URGENT] ?? 0,
            ],
            'overdue' => Task::forUser($userId)->overdue()->count(),
            'due_soon' => Task::forUser($userId)->dueSoon()->count(),
        ];
    }

    /**
     * タスクのクエリビルダーを構築
     * 
     * リクエストパラメータに基づいてタスクのクエリを構築します。
     * フィルタリング、ソート、ページネーションをサポートします。
     *
     * @param User $user 対象ユーザー
     * @param array<string, mixed> $filters フィルター条件
     * @return Builder<Task> クエリビルダー
     */
    public function buildTaskQuery(User $user, array $filters = []): Builder
    {
        $query = Task::query()
            ->with('user')
            ->forUser($user->id);

        // ステータスでフィルタリング
        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        // 優先度でフィルタリング
        if (!empty($filters['priority'])) {
            $query->priority($filters['priority']);
        }

        // 期限切れフィルター
        if (!empty($filters['overdue'])) {
            $query->overdue();
        }

        // 期限が近いタスク
        if (!empty($filters['due_soon'])) {
            $days = (int) ($filters['due_soon_days'] ?? 7);
            $query->dueSoon($days);
        }

        // ソート
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $allowedSortFields = ['created_at', 'updated_at', 'due_date', 'priority', 'status', 'title'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }

    /**
     * タスクの一括ステータス更新
     * 
     * 複数のタスクのステータスを一括で更新します。
     * トランザクション内で実行されるため、すべて成功または失敗します。
     *
     * @param array<int> $taskIds タスクIDの配列
     * @param string $status 新しいステータス
     * @param User $user 所有者ユーザー
     * @return int 更新されたタスクの数
     * @throws \InvalidArgumentException 無効なステータスの場合
     */
    public function bulkUpdateStatus(array $taskIds, string $status, User $user): int
    {
        // ステータスの妥当性チェック
        if (!in_array($status, Task::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return DB::transaction(function () use ($taskIds, $status, $user) {
            return Task::forUser($user->id)
                ->whereIn('id', $taskIds)
                ->update(['status' => $status]);
        });
    }

    /**
     * 期限切れタスクの取得
     * 
     * ユーザーの期限切れタスク一覧を取得します。
     *
     * @param User $user 対象ユーザー
     * @return Collection<Task> 期限切れタスクのコレクション
     */
    public function getOverdueTasks(User $user): Collection
    {
        return Task::query()
            ->with('user')
            ->forUser($user->id)
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * 期限が近いタスクの取得
     * 
     * 指定日数以内に期限が来るタスク一覧を取得します。
     *
     * @param User $user 対象ユーザー
     * @param int $days 日数（デフォルト: 7日）
     * @return Collection<Task> 期限が近いタスクのコレクション
     */
    public function getUpcomingTasks(User $user, int $days = 7): Collection
    {
        return Task::query()
            ->with('user')
            ->forUser($user->id)
            ->dueSoon($days)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * タスクの複製
     * 
     * 既存のタスクを複製して新しいタスクを作成します。
     * created_at, updated_at, id以外の属性がコピーされます。
     *
     * @param Task $task 複製元のタスク
     * @return Task 複製されたタスク
     */
    public function duplicateTask(Task $task): Task
    {
        $attributes = $task->attributesToArray();
        
        // 複製しない属性を削除
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at']);
        
        // タイトルに「（コピー）」を追加
        $attributes['title'] = $attributes['title'] . ' (コピー)';
        
        // ステータスをpendingにリセット
        $attributes['status'] = Task::STATUS_PENDING;

        return Task::create($attributes);
    }

    /**
     * 完了タスクのアーカイブ（論理削除）
     * 
     * 完了したタスクを特定のステータスに変更します。
     * 将来的にソフトデリート実装時の準備。
     *
     * @param User $user 対象ユーザー
     * @param int $daysOld 何日前に完了したタスクをアーカイブするか
     * @return int アーカイブされたタスクの数
     */
    public function archiveCompletedTasks(User $user, int $daysOld = 30): int
    {
        return Task::forUser($user->id)
            ->where('status', Task::STATUS_COMPLETED)
            ->where('updated_at', '<', now()->subDays($daysOld))
            ->update(['status' => Task::STATUS_CANCELLED]);
    }
}
