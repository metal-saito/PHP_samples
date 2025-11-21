<?php

declare(strict_types=1);

namespace App\DTO;

use DateTimeImmutable;

/**
 * Data Transfer Object for creating tasks
 */
final class CreateTaskDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $priority = null,
        public readonly ?DateTimeImmutable $dueDate = null,
        public readonly ?array $tags = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $dueDate = null;
        if (!empty($data['due_date'])) {
            $dueDate = new DateTimeImmutable($data['due_date']);
        }

        return new self(
            title: $data['title'] ?? '',
            description: $data['description'] ?? '',
            priority: $data['priority'] ?? null,
            dueDate: $dueDate,
            tags: $data['tags'] ?? null
        );
    }
}
