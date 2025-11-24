<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Reservation\Exception\ReservationConflictException;
use App\Domain\Reservation\Exception\ReservationNotFoundException;
use App\Domain\Reservation\Exception\ReservationValidationException;
use App\Domain\Reservation\InMemoryReservationRepository;
use App\Domain\Reservation\ReservationPolicy;
use App\Domain\Reservation\ReservationStatus;
use App\ReservationService;
use App\Support\Clock\FrozenClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservationServiceTest extends TestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2025-01-01T09:00:00Z'));
        $this->service = new ReservationService(
            new InMemoryReservationRepository(),
            new ReservationPolicy(),
            $clock
        );
    }

    public function testCreateReservationPersistsEntity(): void
    {
        $result = $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);

        self::assertSame('Alice', $result['user_name']);
        self::assertSame('Room-A', $result['resource_name']);
        self::assertSame('booked', $result['status']);
        self::assertArrayHasKey('id', $result);

        $reservations = $this->service->listReservations();
        self::assertCount(1, $reservations);
    }

    public function testOverlappingReservationThrowsException(): void
    {
        $payload = [
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ];
        $this->service->createReservation($payload);

        $this->expectException(ReservationConflictException::class);
        $this->expectExceptionMessage('Time slot overlaps with existing reservation');
        $this->service->createReservation([
            'user_name'     => 'Bob',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:30:00Z',
            'ends_at'       => '2025-01-01T11:30:00Z',
        ]);
    }

    public function testPolicyRejectsInvalidDuration(): void
    {
        $this->expectException(ReservationValidationException::class);
        $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-02T10:00:00Z',
            'ends_at'       => '2025-01-02T16:30:00Z',
        ]);
    }

    public function testDailyLimitIsEnforced(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->createReservation([
                'user_name'     => 'Alice',
                'resource_name' => 'Room-A',
                'starts_at'     => sprintf('2025-01-01T1%d:00:00Z', $i),
                'ends_at'       => sprintf('2025-01-01T1%d:30:00Z', $i),
            ]);
        }

        $this->expectException(ReservationValidationException::class);
        $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T14:00:00Z',
            'ends_at'       => '2025-01-01T14:30:00Z',
        ]);
    }

    public function testCancelReservation(): void
    {
        $reservation = $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);

        $result = $this->service->cancelReservation($reservation['id']);

        self::assertSame(ReservationStatus::Cancelled->value, $result['status']);
    }

    public function testRescheduleReservation(): void
    {
        $reservation = $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);

        $result = $this->service->rescheduleReservation($reservation['id'], [
            'starts_at' => '2025-01-01T12:00:00Z',
            'ends_at'   => '2025-01-01T13:00:00Z',
        ]);

        self::assertSame('2025-01-01T12:00:00+00:00', $result['starts_at']);
    }

    public function testRescheduleValidatesConflicts(): void
    {
        $first = $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);
        $second = $this->service->createReservation([
            'user_name'     => 'Bob',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T12:00:00Z',
            'ends_at'       => '2025-01-01T13:00:00Z',
        ]);

        $this->expectException(ReservationConflictException::class);
        $this->service->rescheduleReservation($second['id'], [
            'starts_at' => '2025-01-01T10:30:00Z',
            'ends_at'   => '2025-01-01T11:30:00Z',
        ]);
    }

    public function testListReservationsFiltersByStatus(): void
    {
        $reservation = $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);
        $this->service->cancelReservation($reservation['id']);

        $active = $this->service->listReservations(status: 'booked');
        self::assertCount(0, $active);

        $cancelled = $this->service->listReservations(status: 'cancelled');
        self::assertCount(1, $cancelled);
    }

    public function testStatisticsSummarizeReservations(): void
    {
        $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:00:00Z',
            'ends_at'       => '2025-01-01T11:00:00Z',
        ]);

        $stats = $this->service->statistics();

        self::assertSame(1, $stats['totals']['reservations']);
        self::assertArrayHasKey('resources', $stats);
        self::assertSame(1, $stats['resources'][0]['total_reservations']);
    }

    public function testCancelUnknownReservationThrowsException(): void
    {
        $this->expectException(ReservationNotFoundException::class);
        $this->service->cancelReservation('unknown');
    }
}
