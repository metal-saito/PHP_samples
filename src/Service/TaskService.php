<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateTaskDTO;
use App\DTO\UpdateTaskDTO;
use App\Model\Task;
use App\Repository\TaskRepository;
use App\Validator\TaskValidator;

/**
 * Service layer for business logic
 */
final class TaskService
{
    public function __construct(
        private TaskRepository $repository,
        private TaskValidator $validator
    ) {
    }

    public function createTask(CreateTaskDTO $dto): Task
    {
        $this->validator->validateCreate($dto);

        $task = Task::create(
            title: $dto->title,
            description: $dto->description,
            priority: $dto->priority ?? 'medium',
            dueDate: $dto->dueDate,
            tags: $dto->tags ?? []
        );

        return $this->repository->save($task);
    }

    public function updateTask(int $id, UpdateTaskDTO $dto): Task
    {
        $task = $this->repository->findById($id);
        $this->validator->validateUpdate($dto);

        if ($dto->title !== null) {
            $task = $task->updateTitle($dto->title);
        }

        if ($dto->description !== null) {
            $task = $task->updateDescription($dto->description);
        }

        if ($dto->status !== null) {
            $task = $task->changeStatus($dto->status);
        }

        if ($dto->priority !== null) {
            $task = $task->changePriority($dto->priority);
        }

        return $this->repository->save($task);
    }

    /**
     * @param array<string> $tags
     */
    public function addTagsToTask(int $id, array $tags): Task
    {
        $task = $this->repository->findById($id);

        foreach ($tags as $tag) {
            $task = $task->addTag($tag);
        }

        return $this->repository->save($task);
    }

    public function removeTagFromTask(int $id, string $tag): Task
    {
        $task = $this->repository->findById($id);
        $task = $task->removeTag($tag);

        return $this->repository->save($task);
    }

    public function getTask(int $id): Task
    {
        return $this->repository->findById($id);
    }

    /**
     * @return array<Task>
     */
    public function getAllTasks(int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findAll($limit, $offset);
    }

    /**
     * @return array<Task>
     */
    public function getTasksByStatus(string $status): array
    {
        return $this->repository->findByStatus($status);
    }

    /**
     * @return array<Task>
     */
    public function getTasksByTag(string $tag): array
    {
        return $this->repository->findByTag($tag);
    }

    /**
     * @return array<Task>
     */
    public function getOverdueTasks(): array
    {
        return $this->repository->findOverdue();
    }

    public function deleteTask(int $id): void
    {
        $this->repository->delete($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $allTasks = $this->repository->findAll(10000);
        
        $stats = [
            'total' => count($allTasks),
            'by_status' => [],
            'by_priority' => [],
            'overdue' => 0,
        ];

        foreach ($allTasks as $task) {
            $status = $task->getStatus();
            $priority = $task->getPriority();

            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            $stats['by_priority'][$priority] = ($stats['by_priority'][$priority] ?? 0) + 1;

            if ($task->isOverdue()) {
                $stats['overdue']++;
            }
        }

        return $stats;
    }
}
