<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Reservation\Exception\ReservationValidationException;
use App\Domain\Reservation\ReservationPolicy;
use App\Domain\Reservation\TimeSlot;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservationPolicyTest extends TestCase
{
    private ReservationPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ReservationPolicy(
            maxDurationMinutes: 60,
            maxAdvanceDays: 10,
            timeSlotStepMinutes: 15,
            maxDailyReservationsPerUser: 2
        );
    }

    public function testRejectsPastStart(): void
    {
        $this->expectException(ReservationValidationException::class);

        $slot = TimeSlot::fromIso8601(
            '2024-12-31T10:00:00Z',
            '2024-12-31T10:30:00Z'
        );
        $now = new DateTimeImmutable('2025-01-01T10:00:00Z');

        $this->policy->assertAcceptable($slot, $now);
    }

    public function testRejectsDailyLimit(): void
    {
        $this->expectException(ReservationValidationException::class);

        $slot = TimeSlot::fromIso8601(
            '2025-01-02T10:00:00Z',
            '2025-01-02T10:30:00Z'
        );
        $now = new DateTimeImmutable('2025-01-01T09:00:00Z');

        $this->policy->assertAcceptable($slot, $now, existingDailyReservations: 2);
    }
}


