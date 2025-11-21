<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Model\Task;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Validator\TaskValidator;
use App\DTO\CreateTaskDTO;
use App\DTO\UpdateTaskDTO;

echo "=== Task Management API Demo ===\n\n";

// Setup
$pdo = new PDO('sqlite:' . __DIR__ . '/demo.sqlite');
$repository = new TaskRepository($pdo);
$repository->createSchema();

$validator = new TaskValidator();
$service = new TaskService($repository, $validator);

// Clean start
$pdo->exec('DELETE FROM task_tags');
$pdo->exec('DELETE FROM tasks');

echo "1. Creating tasks...\n";
$task1 = $service->createTask(CreateTaskDTO::fromArray([
    'title' => 'Implement REST API endpoints',
    'description' => 'Create CRUD operations for task management',
    'priority' => 'high',
    'tags' => ['backend', 'api'],
]));
echo "   Created: {$task1->getTitle()} (ID: {$task1->getId()})\n";

$task2 = $service->createTask(CreateTaskDTO::fromArray([
    'title' => 'Write unit tests',
    'description' => 'Achieve 80% code coverage',
    'priority' => 'medium',
    'due_date' => (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s'),
    'tags' => ['testing', 'quality'],
]));
echo "   Created: {$task2->getTitle()} (ID: {$task2->getId()})\n";

$task3 = $service->createTask(CreateTaskDTO::fromArray([
    'title' => 'Deploy to production',
    'description' => 'Deploy the application to production environment',
    'priority' => 'urgent',
    'due_date' => (new DateTimeImmutable('+3 days'))->format('Y-m-d H:i:s'),
    'tags' => ['deployment', 'urgent'],
]));
echo "   Created: {$task3->getTitle()} (ID: {$task3->getId()})\n\n";

echo "2. Updating task status...\n";
$updated = $service->updateTask($task1->getId(), UpdateTaskDTO::fromArray([
    'status' => 'in_progress',
]));
echo "   Updated: {$updated->getTitle()} -> {$updated->getStatus()}\n\n";

echo "3. Adding tags...\n";
$withTags = $service->addTagsToTask($task2->getId(), ['important', 'code-review']);
echo "   Added tags to: {$withTags->getTitle()}\n";
echo "   Current tags: " . implode(', ', $withTags->getTags()) . "\n\n";

echo "4. Retrieving all tasks...\n";
$allTasks = $service->getAllTasks();
foreach ($allTasks as $task) {
    echo sprintf(
        "   [%s] %s - Priority: %s, Status: %s\n",
        $task->getId(),
        $task->getTitle(),
        $task->getPriority(),
        $task->getStatus()
    );
}
echo "\n";

echo "5. Filtering by status...\n";
$pendingTasks = $service->getTasksByStatus('pending');
echo "   Pending tasks: " . count($pendingTasks) . "\n";
foreach ($pendingTasks as $task) {
    echo "   - {$task->getTitle()}\n";
}
echo "\n";

echo "6. Filtering by tag...\n";
$backendTasks = $service->getTasksByTag('backend');
echo "   Tasks tagged 'backend': " . count($backendTasks) . "\n";
foreach ($backendTasks as $task) {
    echo "   - {$task->getTitle()}\n";
}
echo "\n";

echo "7. Getting statistics...\n";
$stats = $service->getStatistics();
echo "   Total tasks: {$stats['total']}\n";
echo "   By status:\n";
foreach ($stats['by_status'] as $status => $count) {
    echo "     - {$status}: {$count}\n";
}
echo "   By priority:\n";
foreach ($stats['by_priority'] as $priority => $count) {
    echo "     - {$priority}: {$count}\n";
}
echo "\n";

echo "8. Testing validation...\n";
try {
    $service->createTask(CreateTaskDTO::fromArray([
        'title' => 'AB', // Too short
        'description' => 'Test',
    ]));
} catch (Exception $e) {
    echo "   Validation error (expected): {$e->getMessage()}\n";
}
echo "\n";

echo "9. Testing status transition validation...\n";
try {
    $pending = $service->getTask($task2->getId());
    // Try invalid transition: pending -> completed (should fail)
    $service->updateTask($pending->getId(), UpdateTaskDTO::fromArray([
        'status' => 'completed',
    ]));
} catch (Exception $e) {
    echo "   Status transition error (expected): {$e->getMessage()}\n";
}
echo "\n";

echo "10. Demonstrating immutability...\n";
$original = $service->getTask($task1->getId());
echo "   Original title: {$original->getTitle()}\n";
$modified = $original->updateTitle('New title for demonstration');
echo "   Modified title: {$modified->getTitle()}\n";
echo "   Original title (unchanged): {$original->getTitle()}\n";
echo "   Objects are different: " . ($original !== $modified ? 'Yes' : 'No') . "\n\n";

echo "11. Testing JSON serialization...\n";
$task = $service->getTask($task1->getId());
$json = json_encode($task, JSON_PRETTY_PRINT);
echo "   Task as JSON:\n";
echo preg_replace('/^/m', '   ', $json) . "\n\n";

echo "12. Completing a task...\n";
// Task1 is already in_progress, so just complete it
$completed = $service->updateTask($task1->getId(), UpdateTaskDTO::fromArray([
    'status' => 'completed',
]));
echo "   Task '{$completed->getTitle()}' is now: {$completed->getStatus()}\n";
echo "   Is completed: " . ($completed->isCompleted() ? 'Yes' : 'No') . "\n\n";

echo "=== Demo completed successfully! ===\n";
echo "\nYou can now start the API server with:\n";
echo "  php -S localhost:8000 -t public\n";
echo "\nAnd try the API endpoints:\n";
echo "  curl http://localhost:8000/api/tasks\n";
echo "  curl http://localhost:8000/api/statistics\n";
