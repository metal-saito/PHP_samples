<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use DateTimeImmutable;

final class Reservation
{
    private function __construct(
        private string $id,
        private string $userName,
        private string $resourceName,
        private TimeSlot $timeSlot,
        private string $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {
    }

    public static function book(
        string $id,
        string $userName,
        string $resourceName,
        TimeSlot $timeSlot,
        DateTimeImmutable $now
    ): self {
        return new self(
            $id,
            $userName,
            $resourceName,
            $timeSlot,
            'booked',
            $now,
            $now
        );
    }

    public function overlaps(TimeSlot $slot): bool
    {
        return $this->timeSlot->overlaps($slot);
    }

    public function resourceName(): string
    {
        return $this->resourceName;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'user_name'     => $this->userName,
            'resource_name' => $this->resourceName,
            'status'        => $this->status,
            'created_at'    => $this->createdAt->format(DATE_ATOM),
            'updated_at'    => $this->updatedAt->format(DATE_ATOM),
        ] + $this->timeSlot->toArray();
    }

    public function timeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }
}

