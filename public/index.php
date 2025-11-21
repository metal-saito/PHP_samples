<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\TaskController;
use App\Middleware\JsonMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Repository\TaskRepository;

// Load container
$container = require __DIR__ . '/../config/container.php';

// Initialize database schema
$repository = $container->get(TaskRepository::class);
$repository->createSchema();

// Simple router
class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match
                return call_user_func_array($route['handler'], $matches);
            }
        }

        http_response_code(404);
        return [
            'status' => 'error',
            'message' => 'Route not found',
        ];
    }
}

// Configure routes
$router = new Router();
$controller = $container->get(TaskController::class);

// Task routes
$router->add('GET', '#^/api/tasks$#', [$controller, 'index']);
$router->add('GET', '#^/api/tasks/(\d+)$#', [$controller, 'show']);
$router->add('POST', '#^/api/tasks$#', [$controller, 'store']);
$router->add('PUT', '#^/api/tasks/(\d+)$#', [$controller, 'update']);
$router->add('PATCH', '#^/api/tasks/(\d+)$#', [$controller, 'update']);
$router->add('DELETE', '#^/api/tasks/(\d+)$#', [$controller, 'destroy']);

// Statistics route
$router->add('GET', '#^/api/statistics$#', [$controller, 'statistics']);

// Filter routes
$router->add('GET', '#^/api/tasks/overdue/list$#', [$controller, 'overdue']);
$router->add('GET', '#^/api/tasks/status/([a-z_]+)$#', [$controller, 'byStatus']);
$router->add('GET', '#^/api/tasks/tag/([a-zA-Z0-9_-]+)$#', [$controller, 'byTag']);

// Tag management routes
$router->add('POST', '#^/api/tasks/(\d+)/tags$#', [$controller, 'addTags']);
$router->add('DELETE', '#^/api/tasks/(\d+)/tags/([a-zA-Z0-9_-]+)$#', [$controller, 'removeTag']);

// Health check
$router->add('GET', '#^/api/health$#', function () {
    return [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
    ];
});

// API documentation
$router->add('GET', '#^/$#', function () {
    return [
        'name' => 'Task Management API',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /api/health' => 'Health check',
            'GET /api/tasks' => 'List all tasks (supports ?limit=N&offset=N)',
            'GET /api/tasks/{id}' => 'Get task by ID',
            'POST /api/tasks' => 'Create new task',
            'PUT /api/tasks/{id}' => 'Update task',
            'DELETE /api/tasks/{id}' => 'Delete task',
            'GET /api/statistics' => 'Get task statistics',
            'GET /api/tasks/overdue/list' => 'Get overdue tasks',
            'GET /api/tasks/status/{status}' => 'Get tasks by status',
            'GET /api/tasks/tag/{tag}' => 'Get tasks by tag',
            'POST /api/tasks/{id}/tags' => 'Add tags to task',
            'DELETE /api/tasks/{id}/tags/{tag}' => 'Remove tag from task',
        ],
    ];
});

// Apply middleware and dispatch
$jsonMiddleware = new JsonMiddleware();
$rateLimitMiddleware = new RateLimitMiddleware();

$jsonMiddleware->handle(function () use ($rateLimitMiddleware, $router) {
    return $rateLimitMiddleware->handle(function () use ($router) {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        return $router->dispatch($method, $path);
    });
});
