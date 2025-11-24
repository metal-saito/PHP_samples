<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Simple rate limiting middleware
 */
final class RateLimitMiddleware
{
    private const MAX_REQUESTS = 100;
    private const TIME_WINDOW = 3600; // 1 hour

    public function __construct(private string $storageFile = '/tmp/rate_limits.json')
    {
    }

    public function handle(callable $next): mixed
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $currentTime = time();

        $limits = $this->loadLimits();
        
        // Clean old entries
        $limits = array_filter($limits, fn($data) => $data['reset_time'] > $currentTime);

        if (!isset($limits[$clientIp])) {
            $limits[$clientIp] = [
                'count' => 0,
                'reset_time' => $currentTime + self::TIME_WINDOW,
            ];
        }

        $clientData = $limits[$clientIp];

        if ($clientData['count'] >= self::MAX_REQUESTS) {
            $retryAfter = $clientData['reset_time'] - $currentTime;
            header("Retry-After: {$retryAfter}");
            header('X-RateLimit-Limit: ' . self::MAX_REQUESTS);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . $clientData['reset_time']);
            
            http_response_code(429);
            return [
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ];
        }

        // Increment counter
        $limits[$clientIp]['count']++;
        $this->saveLimits($limits);

        // Add rate limit headers
        $remaining = self::MAX_REQUESTS - $limits[$clientIp]['count'];
        header('X-RateLimit-Limit: ' . self::MAX_REQUESTS);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . $clientData['reset_time']);

        return $next();
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLimits(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        $content = file_get_contents($this->storageFile);
        return $content ? json_decode($content, true) : [];
    }

    /**
     * @param array<string, mixed> $limits
     */
    private function saveLimits(array $limits): void
    {
        file_put_contents($this->storageFile, json_encode($limits));
    }
}
