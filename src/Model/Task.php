<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\ValidationException;
use DateTimeImmutable;
use JsonSerializable;

/**
 * Task Entity with rich domain logic
 */
final class Task implements JsonSerializable
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    private const PRIORITY_LOW = 'low';
    private const PRIORITY_MEDIUM = 'medium';
    private const PRIORITY_HIGH = 'high';
    private const PRIORITY_URGENT = 'urgent';

    private const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    private const VALID_PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    private const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_PENDING],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [self::STATUS_PENDING],
    ];

    public function __construct(
        private ?int $id,
        private string $title,
        private string $description,
        private string $status,
        private string $priority,
        private ?DateTimeImmutable $dueDate,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $tags = []
    ) {
        $this->validateStatus($status);
        $this->validatePriority($priority);
        $this->validateTitle($title);
    }

    public static function create(
        string $title,
        string $description,
        string $priority = self::PRIORITY_MEDIUM,
        ?DateTimeImmutable $dueDate = null,
        array $tags = []
    ): self {
        $now = new DateTimeImmutable();
        
        return new self(
            id: null,
            title: $title,
            description: $description,
            status: self::STATUS_PENDING,
            priority: $priority,
            dueDate: $dueDate,
            createdAt: $now,
            updatedAt: $now,
            tags: $tags
        );
    }

    public function updateTitle(string $title): self
    {
        $this->validateTitle($title);
        
        return new self(
            id: $this->id,
            title: $title,
            description: $this->description,
            status: $this->status,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: $this->tags
        );
    }

    public function updateDescription(string $description): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $description,
            status: $this->status,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: $this->tags
        );
    }

    public function changeStatus(string $newStatus): self
    {
        $this->validateStatus($newStatus);
        
        if (!$this->canTransitionTo($newStatus)) {
            throw new ValidationException(
                sprintf(
                    'Cannot transition from status "%s" to "%s"',
                    $this->status,
                    $newStatus
                )
            );
        }

        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            status: $newStatus,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: $this->tags
        );
    }

    public function changePriority(string $priority): self
    {
        $this->validatePriority($priority);

        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            status: $this->status,
            priority: $priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: $this->tags
        );
    }

    public function addTag(string $tag): self
    {
        if (in_array($tag, $this->tags, true)) {
            return $this;
        }

        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            status: $this->status,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: [...$this->tags, $tag]
        );
    }

    public function removeTag(string $tag): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            status: $this->status,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            tags: array_values(array_filter($this->tags, fn($t) => $t !== $tag))
        );
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable() && !$this->isCompleted();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            title: $this->title,
            description: $this->description,
            status: $this->status,
            priority: $this->priority,
            dueDate: $this->dueDate,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            tags: $this->tags
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'tags' => $this->tags,
            'is_overdue' => $this->isOverdue(),
        ];
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new ValidationException(
                sprintf(
                    'Invalid status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }

    private function validatePriority(string $priority): void
    {
        if (!in_array($priority, self::VALID_PRIORITIES, true)) {
            throw new ValidationException(
                sprintf(
                    'Invalid priority "%s". Valid priorities are: %s',
                    $priority,
                    implode(', ', self::VALID_PRIORITIES)
                )
            );
        }
    }

    private function validateTitle(string $title): void
    {
        $title = trim($title);
        
        if ($title === '') {
            throw new ValidationException('Title cannot be empty');
        }

        if (mb_strlen($title) < 3) {
            throw new ValidationException('Title must be at least 3 characters long');
        }

        if (mb_strlen($title) > 255) {
            throw new ValidationException('Title cannot exceed 255 characters');
        }
    }

    private function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }
}
