<?php

declare(strict_types=1);

use App\Controller\TaskController;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Validator\TaskValidator;

/**
 * Simple Dependency Injection Container
 */
final class Container
{
    private array $services = [];

    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->services[$id])) {
            throw new Exception("Service {$id} not found in container");
        }

        return $this->services[$id]($this);
    }
}

// Configure container
$container = new Container();

// Database connection
$container->set(PDO::class, function () {
    $config = require __DIR__ . '/database.php';
    $driver = $config['driver'];

    if ($driver === 'sqlite') {
        $pdo = new PDO('sqlite:' . $config['sqlite']['path']);
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['mysql']['host'],
            $config['mysql']['port'],
            $config['mysql']['database'],
            $config['mysql']['charset']
        );
        $pdo = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password']);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
});

// Repository
$container->set(TaskRepository::class, function (Container $c) {
    return new TaskRepository($c->get(PDO::class));
});

// Validator
$container->set(TaskValidator::class, function () {
    return new TaskValidator();
});

// Service
$container->set(TaskService::class, function (Container $c) {
    return new TaskService(
        $c->get(TaskRepository::class),
        $c->get(TaskValidator::class)
    );
});

// Controller
$container->set(TaskController::class, function (Container $c) {
    return new TaskController($c->get(TaskService::class));
});

return $container;
