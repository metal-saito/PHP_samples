<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Exception\NotFoundException;
use App\Model\Task;
use App\Repository\TaskRepository;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

final class TaskRepositoryTest extends TestCase
{
    private PDO $pdo;
    private TaskRepository $repository;

    /**
     * 各テストの前に実行
     * インメモリSQLiteデータベースを作成し、スキーマを初期化
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->repository = new TaskRepository($this->pdo);
        $this->repository->createSchema();
    }

    public function testSaveAndFindTask(): void
    {
        $task = Task::create(
            title: 'Test Task',
            description: 'Test Description',
            priority: 'high',
            tags: ['urgent', 'bug']
        );

        $saved = $this->repository->save($task);
        
        $this->assertNotNull($saved->getId());

        $found = $this->repository->findById($saved->getId());
        
        $this->assertEquals($saved->getTitle(), $found->getTitle());
        $this->assertEquals($saved->getDescription(), $found->getDescription());
        $this->assertEquals($saved->getPriority(), $found->getPriority());
        $this->assertCount(2, $found->getTags());
    }

    public function testUpdateTask(): void
    {
        $task = Task::create('Original', 'Description');
        $saved = $this->repository->save($task);

        $updated = $saved->updateTitle('Updated Title');
        $this->repository->save($updated);

        $id = $saved->getId();
        $this->assertNotNull($id);
        $found = $this->repository->findById($id);
        $this->assertEquals('Updated Title', $found->getTitle());
    }

    public function testDeleteTask(): void
    {
        $task = Task::create('To Delete', 'Description');
        $saved = $this->repository->save($task);

        $id = $saved->getId();
        $this->assertNotNull($id);
        $this->repository->delete($id);

        $this->expectException(NotFoundException::class);
        $this->repository->findById($id);
    }

    public function testFindAll(): void
    {
        $this->repository->save(Task::create('Task 1', 'Desc 1'));
        $this->repository->save(Task::create('Task 2', 'Desc 2'));
        $this->repository->save(Task::create('Task 3', 'Desc 3'));

        $tasks = $this->repository->findAll();
        
        $this->assertCount(3, $tasks);
    }

    public function testFindByStatus(): void
    {
        $task1 = Task::create('Task 1', 'Desc 1');
        $this->repository->save($task1);

        $task2 = Task::create('Task 2', 'Desc 2');
        $saved2 = $this->repository->save($task2);
        
        $inProgress = $saved2->changeStatus('in_progress');
        $this->repository->save($inProgress);

        $pendingTasks = $this->repository->findByStatus('pending');
        $inProgressTasks = $this->repository->findByStatus('in_progress');

        $this->assertCount(1, $pendingTasks);
        $this->assertCount(1, $inProgressTasks);
    }

    public function testFindByTag(): void
    {
        $task1 = Task::create('Task 1', 'Desc 1', tags: ['urgent', 'bug']);
        $task2 = Task::create('Task 2', 'Desc 2', tags: ['urgent']);
        $task3 = Task::create('Task 3', 'Desc 3', tags: ['feature']);

        $this->repository->save($task1);
        $this->repository->save($task2);
        $this->repository->save($task3);

        $urgentTasks = $this->repository->findByTag('urgent');
        $featureTasks = $this->repository->findByTag('feature');

        $this->assertCount(2, $urgentTasks);
        $this->assertCount(1, $featureTasks);
    }

    public function testFindOverdue(): void
    {
        $pastDate = new DateTimeImmutable('-1 day');
        $futureDate = new DateTimeImmutable('+1 day');

        $overdue = Task::create('Overdue', 'Desc', dueDate: $pastDate);
        $upcoming = Task::create('Upcoming', 'Desc', dueDate: $futureDate);

        $this->repository->save($overdue);
        $this->repository->save($upcoming);

        $overdueTasks = $this->repository->findOverdue();

        $this->assertCount(1, $overdueTasks);
        $this->assertEquals('Overdue', $overdueTasks[0]->getTitle());
    }

    public function testCount(): void
    {
        $this->assertEquals(0, $this->repository->count());

        $this->repository->save(Task::create('Task 1', 'Desc'));
        $this->assertEquals(1, $this->repository->count());

        $this->repository->save(Task::create('Task 2', 'Desc'));
        $this->assertEquals(2, $this->repository->count());
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->save(Task::create("Task {$i}", "Description {$i}"));
        }

        $page1 = $this->repository->findAll(2, 0);
        $page2 = $this->repository->findAll(2, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertNotEquals($page1[0]->getId(), $page2[0]->getId());
    }
}
