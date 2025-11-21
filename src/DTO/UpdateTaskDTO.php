<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object for updating tasks
 */
final class UpdateTaskDTO
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
        public readonly ?string $priority = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null
        );
    }
}
