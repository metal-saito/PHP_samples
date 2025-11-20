<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    /**
     * リクエストの処理
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey || $apiKey !== config('app.api_key')) {
            return response()->json([
                'error' => 'Unauthorized. Valid API key required.'
            ], 401);
        }

        return $next($request);
    }
}

