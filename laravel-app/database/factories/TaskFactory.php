<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * モデルのデフォルト状態を定義
     * 
     * テストやシーディングで使用されるタスクのダミーデータを生成します。
     *
     * @return array<string, mixed> タスクの属性配列
     */
    public function definition(): array
    {
        return [
            // 関連するユーザー（未指定の場合は新規作成）
            'user_id' => \App\Models\User::factory(),
            
            // ランダムなタイトル（例：「This is a sample sentence.」）
            'title' => fake()->sentence(),
            
            // ランダムな説明文（パラグラフ）
            'description' => fake()->paragraph(),
            
            // ランダムなステータス
            'status' => fake()->randomElement(\App\Models\Task::VALID_STATUSES),
            
            // ランダムな優先度
            'priority' => fake()->randomElement(\App\Models\Task::VALID_PRIORITIES),
            
            // 期限（70%の確率で設定、未来30日以内のランダムな日時）
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * 期限切れのタスクを生成するファクトリー状態
     *
     * @return static
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => fake()->randomElement([\App\Models\Task::STATUS_PENDING, \App\Models\Task::STATUS_IN_PROGRESS]),
        ]);
    }

    /**
     * 完了したタスクを生成するファクトリー状態
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Task::STATUS_COMPLETED,
        ]);
    }

    /**
     * 高優先度のタスクを生成するファクトリー状態
     *
     * @return static
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => \App\Models\Task::PRIORITY_HIGH,
        ]);
    }
}
