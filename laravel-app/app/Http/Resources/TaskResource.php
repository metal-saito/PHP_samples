<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * リソースを配列に変換
     * 
     * タスクモデルをAPIレスポンス用のJSON構造に変換します。
     * - 日時は ISO8601 形式（例: 2024-12-31T23:59:59+09:00）
     * - 期限切れ判定を含む
     * - 関連するユーザー情報も含む
     *
     * @param Request $request HTTPリクエスト
     * @return array<string, mixed> タスクデータの配列
     */
    public function toArray(Request $request): array
    {
        return [
            // タスクの基本情報
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            
            // ステータスと優先度
            'status' => $this->status,
            'priority' => $this->priority,
            
            // 日時情報（ISO8601形式）
            'due_date' => $this->due_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // 計算プロパティ
            // タスクが期限切れかどうか（完了済み・キャンセル済みは除く）
            'is_overdue' => $this->isOverdue(),
            'is_completed' => $this->isCompleted(),
            
            // 関連するユーザー情報
            // ユーザーがロードされている場合のみ含める（N+1問題回避）
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
