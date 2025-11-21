<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use App\Exception\NotFoundException;
use App\Model\Task;
use DateTimeImmutable;
use PDO;
use PDOException;

/**
 * Repository pattern implementation for Task persistence
 */
final class TaskRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function save(Task $task): Task
    {
        try {
            if ($task->getId() === null) {
                return $this->insert($task);
            }
            
            return $this->update($task);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to save task: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findById(int $id): Task
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT t.*, GROUP_CONCAT(tt.tag) as tags
                FROM tasks t
                LEFT JOIN task_tags tt ON t.id = tt.task_id
                WHERE t.id = ?
                GROUP BY t.id
            ');
            
            $stmt->execute([$id]);
            $data = $stmt->fetch();

            if ($data === false) {
                throw new NotFoundException("Task with ID {$id} not found");
            }

            return $this->hydrate($data);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find task: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT t.*, GROUP_CONCAT(tt.tag) as tags
                FROM tasks t
                LEFT JOIN task_tags tt ON t.id = tt.task_id
                GROUP BY t.id
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ');
            
            $stmt->execute([$limit, $offset]);
            
            return array_map(
                fn($data) => $this->hydrate($data),
                $stmt->fetchAll()
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find tasks: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByStatus(string $status, int $limit = 100): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT t.*, GROUP_CONCAT(tt.tag) as tags
                FROM tasks t
                LEFT JOIN task_tags tt ON t.id = tt.task_id
                WHERE t.status = ?
                GROUP BY t.id
                ORDER BY t.priority DESC, t.created_at DESC
                LIMIT ?
            ');
            
            $stmt->execute([$status, $limit]);
            
            return array_map(
                fn($data) => $this->hydrate($data),
                $stmt->fetchAll()
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find tasks by status: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByTag(string $tag, int $limit = 100): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT t.*, GROUP_CONCAT(tt.tag) as tags
                FROM tasks t
                INNER JOIN task_tags tt ON t.id = tt.task_id
                WHERE tt.tag = ?
                GROUP BY t.id
                ORDER BY t.created_at DESC
                LIMIT ?
            ');
            
            $stmt->execute([$tag, $limit]);
            
            return array_map(
                fn($data) => $this->hydrate($data),
                $stmt->fetchAll()
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find tasks by tag: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findOverdue(): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT t.*, GROUP_CONCAT(tt.tag) as tags
                FROM tasks t
                LEFT JOIN task_tags tt ON t.id = tt.task_id
                WHERE t.due_date < ? AND t.status NOT IN (?, ?)
                GROUP BY t.id
                ORDER BY t.due_date ASC
            ');
            
            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $stmt->execute([$now, 'completed', 'cancelled']);
            
            return array_map(
                fn($data) => $this->hydrate($data),
                $stmt->fetchAll()
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to find overdue tasks: ' . $e->getMessage(), 0, $e);
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->pdo->beginTransaction();

            // Delete tags first
            $stmt = $this->pdo->prepare('DELETE FROM task_tags WHERE task_id = ?');
            $stmt->execute([$id]);

            // Delete task
            $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                throw new NotFoundException("Task with ID {$id} not found");
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new DatabaseException('Failed to delete task: ' . $e->getMessage(), 0, $e);
        }
    }

    public function count(): int
    {
        try {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM tasks');
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to count tasks: ' . $e->getMessage(), 0, $e);
        }
    }

    public function createSchema(): void
    {
        try {
            $this->pdo->exec('
                CREATE TABLE IF NOT EXISTS tasks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    priority VARCHAR(50) NOT NULL,
                    due_date DATETIME,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                )
            ');

            $this->pdo->exec('
                CREATE TABLE IF NOT EXISTS task_tags (
                    task_id INTEGER NOT NULL,
                    tag VARCHAR(100) NOT NULL,
                    PRIMARY KEY (task_id, tag),
                    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
                )
            ');

            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_priority ON tasks(priority)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_task_tags_tag ON task_tags(tag)');
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to create schema: ' . $e->getMessage(), 0, $e);
        }
    }

    private function insert(Task $task): Task
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO tasks (title, description, status, priority, due_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $task->getTitle(),
                $task->getDescription(),
                $task->getStatus(),
                $task->getPriority(),
                $task->getDueDate()?->format('Y-m-d H:i:s'),
                $task->getCreatedAt()->format('Y-m-d H:i:s'),
                $task->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $taskWithId = $task->withId($id);

            $this->saveTags($id, $task->getTags());

            $this->pdo->commit();

            return $taskWithId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function update(Task $task): Task
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('
                UPDATE tasks
                SET title = ?, description = ?, status = ?, priority = ?, 
                    due_date = ?, updated_at = ?
                WHERE id = ?
            ');

            $stmt->execute([
                $task->getTitle(),
                $task->getDescription(),
                $task->getStatus(),
                $task->getPriority(),
                $task->getDueDate()?->format('Y-m-d H:i:s'),
                $task->getUpdatedAt()->format('Y-m-d H:i:s'),
                $task->getId(),
            ]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                throw new NotFoundException("Task with ID {$task->getId()} not found");
            }

            // Update tags
            $deleteStmt = $this->pdo->prepare('DELETE FROM task_tags WHERE task_id = ?');
            $deleteStmt->execute([$task->getId()]);

            $this->saveTags($task->getId(), $task->getTags());

            $this->pdo->commit();

            return $task;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function saveTags(int $taskId, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO task_tags (task_id, tag) VALUES (?, ?)');
        
        foreach ($tags as $tag) {
            $stmt->execute([$taskId, $tag]);
        }
    }

    private function hydrate(array $data): Task
    {
        $tags = !empty($data['tags']) ? explode(',', $data['tags']) : [];

        return new Task(
            id: (int) $data['id'],
            title: $data['title'],
            description: $data['description'],
            status: $data['status'],
            priority: $data['priority'],
            dueDate: $data['due_date'] ? new DateTimeImmutable($data['due_date']) : null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            tags: $tags
        );
    }
}
