<?php

namespace App\Jobs;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReservation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * コンストラクタ
     */
    public function __construct(
        public Reservation $reservation
    ) {
    }

    /**
     * ジョブの実行
     */
    public function handle(): void
    {
        Log::info("Processing reservation: {$this->reservation->id}");

        // 実際の処理をシミュレート
        sleep(1);

        // イベントを発火
        event(new \App\Events\ReservationProcessed($this->reservation));

        Log::info("Reservation processed: {$this->reservation->id}");
    }

    /**
     * ジョブ失敗時の処理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Reservation processing failed: {$this->reservation->id}", [
            'error' => $exception->getMessage(),
        ]);

        // 通知を送信（実装例）
        // Notification::send($user, new ReservationProcessingFailed($this->reservation));
    }
}

