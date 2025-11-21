<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Reservation\TimeSlot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TimeSlotTest extends TestCase
{
    public function testDetectsOverlap(): void
    {
        $slotA = TimeSlot::fromIso8601('2025-01-01T10:00:00Z', '2025-01-01T11:00:00Z');
        $slotB = TimeSlot::fromIso8601('2025-01-01T10:30:00Z', '2025-01-01T11:30:00Z');

        self::assertTrue($slotA->overlaps($slotB));
        self::assertTrue($slotB->overlaps($slotA));
    }

    public function testRejectsInvalidRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeSlot::fromIso8601('2025-01-01T11:00:00Z', '2025-01-01T10:00:00Z');
    }
}

