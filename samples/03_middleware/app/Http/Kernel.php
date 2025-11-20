<?php

namespace App\Http;

use App\Http\Middleware\ApiKeyAuthentication;
use App\Http\Middleware\RequestLogging;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * グローバルミドルウェア
     */
    protected $middleware = [
        RequestLogging::class,
    ];

    /**
     * ミドルウェアグループ
     */
    protected $middlewareGroups = [
        'api' => [
            ApiKeyAuthentication::class,
        ],
    ];
}

