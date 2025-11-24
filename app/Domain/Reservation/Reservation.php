<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use App\Domain\Reservation\Exception\ReservationValidationException;
use DateTimeImmutable;

final class Reservation
{
    private function __construct(
        private string $id,
        private string $userName,
        private string $resourceName,
        private TimeSlot $timeSlot,
        private ReservationStatus $status,
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
            ReservationStatus::Booked,
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

    public function userName(): string
    {
        return $this->userName;
    }

    public function status(): ReservationStatus
    {
        return $this->status;
    }

    public function timeSlot(): TimeSlot
    {
        return $this->timeSlot;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function cancel(DateTimeImmutable $now): self
    {
        $this->assertActive('予約はすでにキャンセル済みです。');

        return new self(
            $this->id,
            $this->userName,
            $this->resourceName,
            $this->timeSlot,
            ReservationStatus::Cancelled,
            $this->createdAt,
            $now
        );
    }

    public function reschedule(TimeSlot $timeSlot, DateTimeImmutable $now): self
    {
        $this->assertActive('キャンセル済みの予約は変更できません。');

        return new self(
            $this->id,
            $this->userName,
            $this->resourceName,
            $timeSlot,
            $this->status,
            $this->createdAt,
            $now
        );
    }

    public function complete(DateTimeImmutable $now): self
    {
        return new self(
            $this->id,
            $this->userName,
            $this->resourceName,
            $this->timeSlot,
            ReservationStatus::Completed,
            $this->createdAt,
            $now
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'user_name'     => $this->userName,
            'resource_name' => $this->resourceName,
            'status'        => $this->status->value,
            'created_at'    => $this->createdAt->format(DATE_ATOM),
            'updated_at'    => $this->updatedAt->format(DATE_ATOM),
        ] + $this->timeSlot->toArray();
    }

    private function assertActive(string $message): void
    {
        if (!$this->status->isActive()) {
            throw new ReservationValidationException($message);
        }
    }
}

