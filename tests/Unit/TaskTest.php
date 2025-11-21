<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exception\ValidationException;
use App\Model\Task;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testCreateTask(): void
    {
        $task = Task::create(
            title: 'Test Task',
            description: 'Test Description',
            priority: 'high'
        );

        $this->assertNull($task->getId());
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('Test Description', $task->getDescription());
        $this->assertEquals('pending', $task->getStatus());
        $this->assertEquals('high', $task->getPriority());
        $this->assertInstanceOf(DateTimeImmutable::class, $task->getCreatedAt());
    }

    public function testUpdateTitle(): void
    {
        $task = Task::create('Original Title', 'Description');
        $updatedTask = $task->updateTitle('New Title');

        $this->assertEquals('Original Title', $task->getTitle());
        $this->assertEquals('New Title', $updatedTask->getTitle());
    }

    public function testChangeStatus(): void
    {
        $task = Task::create('Task', 'Description');
        
        $inProgress = $task->changeStatus('in_progress');
        $this->assertEquals('in_progress', $inProgress->getStatus());

        $completed = $inProgress->changeStatus('completed');
        $this->assertEquals('completed', $completed->getStatus());
    }

    public function testInvalidStatusTransition(): void
    {
        $this->expectException(ValidationException::class);
        
        $task = Task::create('Task', 'Description');
        $task->changeStatus('completed'); // Cannot go from pending to completed directly
    }

    public function testChangePriority(): void
    {
        $task = Task::create('Task', 'Description', 'low');
        $updated = $task->changePriority('urgent');

        $this->assertEquals('urgent', $updated->getPriority());
    }

    public function testInvalidPriority(): void
    {
        $this->expectException(ValidationException::class);
        
        Task::create('Task', 'Description', 'invalid_priority');
    }

    public function testAddAndRemoveTags(): void
    {
        $task = Task::create('Task', 'Description');
        
        $withTag1 = $task->addTag('urgent');
        $this->assertContains('urgent', $withTag1->getTags());

        $withTag2 = $withTag1->addTag('backend');
        $this->assertCount(2, $withTag2->getTags());

        $withoutTag = $withTag2->removeTag('urgent');
        $this->assertCount(1, $withoutTag->getTags());
        $this->assertNotContains('urgent', $withoutTag->getTags());
    }

    public function testIsOverdue(): void
    {
        $pastDate = new DateTimeImmutable('-1 day');
        $task = Task::create('Task', 'Description', dueDate: $pastDate);

        $this->assertTrue($task->isOverdue());

        $completed = $task->changeStatus('in_progress')->changeStatus('completed');
        $this->assertFalse($completed->isOverdue());
    }

    public function testValidationForShortTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least 3 characters');
        
        Task::create('AB', 'Description');
    }

    public function testValidationForEmptyTitle(): void
    {
        $this->expectException(ValidationException::class);
        
        Task::create('', 'Description');
    }

    public function testJsonSerialize(): void
    {
        $task = Task::create('Task', 'Description', 'high', tags: ['urgent', 'bug']);
        $json = $task->jsonSerialize();

        $this->assertArrayHasKey('title', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('priority', $json);
        $this->assertArrayHasKey('tags', $json);
        $this->assertArrayHasKey('is_overdue', $json);
        $this->assertEquals('Task', $json['title']);
        $this->assertEquals(['urgent', 'bug'], $json['tags']);
    }

    public function testImmutability(): void
    {
        $task = Task::create('Original', 'Description');
        $modified = $task->updateTitle('Modified');

        $this->assertEquals('Original', $task->getTitle());
        $this->assertEquals('Modified', $modified->getTitle());
        $this->assertNotSame($task, $modified);
    }
}
