<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use App\Domain\Reservation\Exception\ReservationValidationException;
use DateTimeImmutable;

final class ReservationPolicy
{
    public function __construct(
        private readonly int $maxDurationMinutes = 240,
        private readonly int $maxAdvanceDays = 30,
        private readonly int $timeSlotStepMinutes = 15,
        private readonly int $maxDailyReservationsPerUser = 3
    ) {
    }

    public function assertAcceptable(
        TimeSlot $timeSlot,
        DateTimeImmutable $now,
        int $existingDailyReservations = 0
    ): void {
        if ($timeSlot->durationInMinutes() > $this->maxDurationMinutes) {
            throw new ReservationValidationException('最大予約時間（' . $this->maxDurationMinutes . "分）を超過しています。");
        }

        $diffDue = $now->diff($timeSlot->startsAt());
        $daysDifference = (int) $diffDue->format('%r%a');
        if ($daysDifference > $this->maxAdvanceDays) {
            throw new ReservationValidationException('予約可能期間（' . $this->maxAdvanceDays . "日）を超えています。");
        }

        if ($daysDifference < 0) {
            throw new ReservationValidationException('開始時刻は現在時刻より未来である必要があります。');
        }

        $minute = (int) $timeSlot->startsAt()->format('i');
        $minuteRemainder = $minute % $this->timeSlotStepMinutes;
        if ($minuteRemainder !== 0) {
            throw new ReservationValidationException("開始時刻は{$this->timeSlotStepMinutes}分単位で指定してください。");
        }

        if ($existingDailyReservations >= $this->maxDailyReservationsPerUser) {
            throw new ReservationValidationException('1日に作成できる予約数（' . $this->maxDailyReservationsPerUser . "件）を超えています。");
        }
    }
}

