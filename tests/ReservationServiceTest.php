<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Reservation\InMemoryReservationRepository;
use App\Domain\Reservation\ReservationPolicy;
use App\ReservationService;
use App\Support\Clock\FrozenClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Time slot overlaps');
        $this->service->createReservation([
            'user_name'     => 'Bob',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-01T10:30:00Z',
            'ends_at'       => '2025-01-01T11:30:00Z',
        ]);
    }

    public function testPolicyRejectsInvalidDuration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->createReservation([
            'user_name'     => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at'     => '2025-01-02T10:00:00Z',
            'ends_at'       => '2025-01-02T16:30:00Z',
        ]);
    }
}
