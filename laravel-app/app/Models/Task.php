<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * タスクモデル
 * 
 * ユーザーが作成・管理するタスクを表現するモデル。
 * ステータス、優先度、期限などの情報を保持し、
 * 様々な条件でのフィルタリングをサポートします。
 *
 * @property int $id タスクID
 * @property int $user_id 所有者のユーザーID
 * @property string $title タスクのタイトル
 * @property string|null $description タスクの詳細説明
 * @property string $status ステータス（pending, in_progress, completed, cancelled）
 * @property string $priority 優先度（low, medium, high, urgent）
 * @property \Illuminate\Support\Carbon|null $due_date 期限日時
 * @property \Illuminate\Support\Carbon $created_at 作成日時
 * @property \Illuminate\Support\Carbon $updated_at 更新日時
 * @property-read User $user 所有者のユーザー
 */
class Task extends Model
{
    use HasFactory;

    /**
     * タスクのステータス定数
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * タスクの優先度定数
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * 有効なステータスの配列
     *
     * @var array<string>
     */
    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * 有効な優先度の配列
     *
     * @var array<string>
     */
    public const VALID_PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    /**
     * 完了とみなされるステータス
     *
     * @var array<string>
     */
    public const COMPLETED_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // リレーションシップ
    // ========================================

    /**
     * このタスクを所有するユーザーを取得
     *
     * @return BelongsTo<User, Task>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // クエリスコープ
    // ========================================

    /**
     * 特定のステータスのタスクのみを取得するスコープ
     *
     * @param Builder<Task> $query
     * @param string $status ステータス（pending, in_progress, completed, cancelled）
     * @return Builder<Task>
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 期限切れタスクのみを取得するスコープ
     * 
     * 期限が現在時刻より前で、かつ完了していない（pending または in_progress）タスクを返します。
     *
     * @param Builder<Task> $query
     * @return Builder<Task>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
                     ->whereNotIn('status', self::COMPLETED_STATUSES);
    }

    /**
     * 特定のユーザーのタスクのみを取得するスコープ
     *
     * @param Builder<Task> $query
     * @param int $userId ユーザーID
     * @return Builder<Task>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 特定の優先度のタスクのみを取得するスコープ
     *
     * @param Builder<Task> $query
     * @param string $priority 優先度（low, medium, high, urgent）
     * @return Builder<Task>
     */
    public function scopePriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * 期限が近いタスクを取得するスコープ（指定日数以内）
     *
     * @param Builder<Task> $query
     * @param int $days 日数（デフォルト: 7日）
     * @return Builder<Task>
     */
    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
                     ->whereNotIn('status', self::COMPLETED_STATUSES);
    }

    // ========================================
    // アクセサ・ヘルパーメソッド
    // ========================================

    /**
     * タスクが期限切れかどうかを判定
     *
     * @return bool 期限切れの場合true
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date || in_array($this->status, self::COMPLETED_STATUSES)) {
            return false;
        }

        return $this->due_date < now();
    }

    /**
     * タスクが完了しているかどうかを判定
     *
     * @return bool 完了している場合true
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, self::COMPLETED_STATUSES);
    }

    /**
     * タスクが進行中かどうかを判定
     *
     * @return bool 進行中の場合true
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * タスクが保留中かどうかを判定
     *
     * @return bool 保留中の場合true
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
