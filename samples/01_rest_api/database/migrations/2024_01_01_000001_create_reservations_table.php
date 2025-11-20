<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーション実行
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('resource_name');
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->string('status')->default('booked');
            $table->timestamps();

            $table->index(['resource_name', 'starts_at', 'ends_at']);
            $table->index('status');
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

