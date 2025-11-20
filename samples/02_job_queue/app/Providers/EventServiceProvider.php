<?php

namespace App\Providers;

use App\Events\ReservationProcessed;
use App\Listeners\SendReservationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * イベントとリスナーのマッピング
     */
    protected $listen = [
        ReservationProcessed::class => [
            SendReservationNotification::class,
        ],
    ];

    /**
     * サービスプロバイダーの登録
     */
    public function boot(): void
    {
        //
    }
}

