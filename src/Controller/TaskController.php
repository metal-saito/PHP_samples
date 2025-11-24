<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateTaskDTO;
use App\DTO\UpdateTaskDTO;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Service\TaskService;

/**
 * REST API Controller for Task operations
 */
final class TaskController
{
    public function __construct(private TaskService $service)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function index(): array
    {
        $limit = (int) ($_GET['limit'] ?? 100);
        $offset = (int) ($_GET['offset'] ?? 0);

        $tasks = $this->service->getAllTasks($limit, $offset);

        return [
            'status' => 'success',
            'data' => $tasks,
            'meta' => [
                'count' => count($tasks),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(int $id): array
    {
        try {
            $task = $this->service->getTask($id);

            return [
                'status' => 'success',
                'data' => $task,
            ];
        } catch (NotFoundException $e) {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function store(): array
    {
        try {
            $input = file_get_contents('php://input');
            if ($input === false) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Failed to read input',
                ];
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Invalid JSON payload',
                ];
            }

            $dto = CreateTaskDTO::fromArray($data);
            $task = $this->service->createTask($dto);

            http_response_code(201);
            return [
                'status' => 'success',
                'data' => $task,
                'message' => 'Task created successfully',
            ];
        } catch (ValidationException $e) {
            http_response_code(422);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id): array
    {
        try {
            $input = file_get_contents('php://input');
            if ($input === false) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Failed to read input',
                ];
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Invalid JSON payload',
                ];
            }

            $dto = UpdateTaskDTO::fromArray($data);
            $task = $this->service->updateTask($id, $dto);

            return [
                'status' => 'success',
                'data' => $task,
                'message' => 'Task updated successfully',
            ];
        } catch (NotFoundException $e) {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        } catch (ValidationException $e) {
            http_response_code(422);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function destroy(int $id): array
    {
        try {
            $this->service->deleteTask($id);

            http_response_code(204);
            return [
                'status' => 'success',
                'message' => 'Task deleted successfully',
            ];
        } catch (NotFoundException $e) {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(): array
    {
        $stats = $this->service->getStatistics();

        return [
            'status' => 'success',
            'data' => $stats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overdue(): array
    {
        $tasks = $this->service->getOverdueTasks();

        return [
            'status' => 'success',
            'data' => $tasks,
            'meta' => [
                'count' => count($tasks),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function byStatus(string $status): array
    {
        $tasks = $this->service->getTasksByStatus($status);

        return [
            'status' => 'success',
            'data' => $tasks,
            'meta' => [
                'count' => count($tasks),
                'filter' => $status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function byTag(string $tag): array
    {
        $tasks = $this->service->getTasksByTag($tag);

        return [
            'status' => 'success',
            'data' => $tasks,
            'meta' => [
                'count' => count($tasks),
                'tag' => $tag,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addTags(int $id): array
    {
        try {
            $input = file_get_contents('php://input');
            if ($input === false) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Failed to read input',
                ];
            }
            
            $data = json_decode($input, true);
            
            if (!isset($data['tags']) || !is_array($data['tags'])) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Tags array is required',
                ];
            }

            $task = $this->service->addTagsToTask($id, $data['tags']);

            return [
                'status' => 'success',
                'data' => $task,
                'message' => 'Tags added successfully',
            ];
        } catch (NotFoundException $e) {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function removeTag(int $id, string $tag): array
    {
        try {
            $task = $this->service->removeTagFromTask($id, $tag);

            return [
                'status' => 'success',
                'data' => $task,
                'message' => 'Tag removed successfully',
            ];
        } catch (NotFoundException $e) {
            http_response_code(404);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
