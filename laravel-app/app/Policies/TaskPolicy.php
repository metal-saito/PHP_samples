<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * すべてのタスクを表示できるか判定
     * 
     * 認証済みユーザーは自分のタスク一覧を表示できます。
     * コントローラー側で自分のタスクのみにフィルタリングします。
     *
     * @param User $user 認証済みユーザー
     * @return bool 常にtrue（認証済みなら許可）
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 特定のタスクを表示できるか判定
     * 
     * ユーザーは自分が所有するタスクのみ表示できます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool タスクの所有者である場合true
     */
    public function view(User $user, Task $task): bool
    {
        return $this->isOwner($user, $task);
    }

    /**
     * タスクを作成できるか判定
     * 
     * すべての認証済みユーザーはタスクを作成できます。
     *
     * @param User $user 認証済みユーザー
     * @return bool 常にtrue（認証済みなら許可）
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * タスクを更新できるか判定
     * 
     * ユーザーは自分が所有するタスクのみ更新できます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool タスクの所有者である場合true
     */
    public function update(User $user, Task $task): bool
    {
        return $this->isOwner($user, $task);
    }

    /**
     * タスクを削除できるか判定
     * 
     * ユーザーは自分が所有するタスクのみ削除できます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool タスクの所有者である場合true
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->isOwner($user, $task);
    }

    /**
     * タスクを復元できるか判定
     * 
     * ソフトデリート使用時のみ利用。
     * ユーザーは自分が所有するタスクのみ復元できます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool タスクの所有者である場合true
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->isOwner($user, $task);
    }

    /**
     * タスクを完全削除できるか判定
     * 
     * ソフトデリート使用時のみ利用。
     * ユーザーは自分が所有するタスクのみ完全削除できます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool タスクの所有者である場合true
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $this->isOwner($user, $task);
    }

    /**
     * ユーザーがタスクの所有者かどうかを判定
     * 
     * 所有権チェックロジックを一箇所に集約することで、
     * メンテナンス性を向上させます。
     *
     * @param User $user 認証済みユーザー
     * @param Task $task 対象のタスク
     * @return bool 所有者である場合true
     */
    private function isOwner(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
