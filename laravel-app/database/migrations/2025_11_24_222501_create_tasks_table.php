<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * マイグレーションを実行
     * 
     * tasksテーブルを作成します。
     * - ユーザーとの外部キー制約
     * - ステータスと優先度のENUM型
     * - パフォーマンス向上のための複合インデックス
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            // 主キー
            $table->id();
            
            // 外部キー：ユーザーID（ユーザー削除時にタスクも削除）
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade')
                  ->comment('タスクの所有者');
            
            // タスクの基本情報
            $table->string('title')->comment('タスクのタイトル');
            $table->text('description')->nullable()->comment('タスクの詳細説明');
            
            // ステータス（デフォルト：pending）
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                  ->default('pending')
                  ->comment('タスクのステータス');
            
            // 優先度（デフォルト：medium）
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                  ->default('medium')
                  ->comment('タスクの優先度');
            
            // 期限（任意）
            $table->timestamp('due_date')
                  ->nullable()
                  ->comment('タスクの期限');
            
            // タイムスタンプ（created_at, updated_at）
            $table->timestamps();
            
            // パフォーマンス最適化のためのインデックス
            // ユーザーIDとステータスの複合インデックス（よく一緒に検索される）
            $table->index(['user_id', 'status'], 'tasks_user_status_index');
            
            // 期限のインデックス（期限切れタスクの検索に使用）
            $table->index('due_date', 'tasks_due_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
