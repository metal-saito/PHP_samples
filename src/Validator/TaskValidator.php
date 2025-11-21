<?php

declare(strict_types=1);

namespace App\Validator;

use App\DTO\CreateTaskDTO;
use App\DTO\UpdateTaskDTO;
use App\Exception\ValidationException;

/**
 * Validator for task-related operations
 */
final class TaskValidator
{
    private const VALID_PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    private const VALID_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];

    public function validateCreate(CreateTaskDTO $dto): void
    {
        $errors = [];

        if (trim($dto->title) === '') {
            $errors[] = 'Title is required';
        } elseif (mb_strlen($dto->title) < 3) {
            $errors[] = 'Title must be at least 3 characters';
        } elseif (mb_strlen($dto->title) > 255) {
            $errors[] = 'Title cannot exceed 255 characters';
        }

        if (trim($dto->description) === '') {
            $errors[] = 'Description is required';
        }

        if ($dto->priority !== null && !in_array($dto->priority, self::VALID_PRIORITIES, true)) {
            $errors[] = sprintf(
                'Invalid priority. Valid values: %s',
                implode(', ', self::VALID_PRIORITIES)
            );
        }

        if ($dto->tags !== null) {
            foreach ($dto->tags as $tag) {
                if (!is_string($tag) || trim($tag) === '') {
                    $errors[] = 'Tags must be non-empty strings';
                    break;
                }
                if (mb_strlen($tag) > 50) {
                    $errors[] = 'Tags cannot exceed 50 characters';
                    break;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }

    public function validateUpdate(UpdateTaskDTO $dto): void
    {
        $errors = [];

        if ($dto->title !== null) {
            if (trim($dto->title) === '') {
                $errors[] = 'Title cannot be empty';
            } elseif (mb_strlen($dto->title) < 3) {
                $errors[] = 'Title must be at least 3 characters';
            } elseif (mb_strlen($dto->title) > 255) {
                $errors[] = 'Title cannot exceed 255 characters';
            }
        }

        if ($dto->description !== null && trim($dto->description) === '') {
            $errors[] = 'Description cannot be empty';
        }

        if ($dto->status !== null && !in_array($dto->status, self::VALID_STATUSES, true)) {
            $errors[] = sprintf(
                'Invalid status. Valid values: %s',
                implode(', ', self::VALID_STATUSES)
            );
        }

        if ($dto->priority !== null && !in_array($dto->priority, self::VALID_PRIORITIES, true)) {
            $errors[] = sprintf(
                'Invalid priority. Valid values: %s',
                implode(', ', self::VALID_PRIORITIES)
            );
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('; ', $errors));
        }
    }
}
