<?php

namespace App\Listeners;

use App\Events\ReservationProcessed;
use Illuminate\Support\Facades\Log;

class SendReservationNotification
{
    /**
     * イベントの処理
     */
    public function handle(ReservationProcessed $event): void
    {
        Log::info("Sending notification for reservation: {$event->reservation->id}");

        // 実際の通知処理（メール、SMS等）
        // Mail::to($user)->send(new ReservationConfirmed($event->reservation));
    }
}

