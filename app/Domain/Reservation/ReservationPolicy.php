<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use DateInterval;
use DateTimeImmutable;
use RuntimeException;

final class ReservationPolicy
{
    public function __construct(
        private readonly int $maxDurationMinutes = 240,
        private readonly int $maxAdvanceDays = 30,
        private readonly int $timeSlotStepMinutes = 15
    ) {
    }

    public function assertAcceptable(TimeSlot $timeSlot, DateTimeImmutable $now): void
    {
        if ($timeSlot->durationInMinutes() > $this->maxDurationMinutes) {
            throw new RuntimeException('Reservation exceeds maximum duration.');
        }

        $diffDue = $now->diff($timeSlot->startsAt());
        $daysDifference = (int) $diffDue->format('%r%a');
        if ($daysDifference > $this->maxAdvanceDays) {
            throw new RuntimeException('Reservation is too far in the future.');
        }

        if ($daysDifference < 0) {
            throw new RuntimeException('Reservation start time must be in the future.');
        }

        $minute = (int) $timeSlot->startsAt()->format('i');
        $minuteRemainder = $minute % $this->timeSlotStepMinutes;
        if ($minuteRemainder !== 0) {
            throw new RuntimeException('Reservation must align with 15-minute increments.');
        }
    }
}

