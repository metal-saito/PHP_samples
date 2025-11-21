<?php

declare(strict_types=1);

namespace App;

use App\Domain\Reservation\InMemoryReservationRepository;
use App\Domain\Reservation\Reservation;
use App\Domain\Reservation\ReservationPolicy;
use App\Domain\Reservation\ReservationRepositoryInterface;
use App\Domain\Reservation\TimeSlot;
use App\Support\Clock\Clock;
use App\Support\Clock\SystemClock;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $reservations = new InMemoryReservationRepository(),
        private ReservationPolicy $policy = new ReservationPolicy(),
        private Clock $clock = new SystemClock()
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createReservation(array $data): array
    {
        $payload = $this->sanitize($data);
        $this->assertRequired($payload);

        $timeSlot = TimeSlot::fromIso8601($payload['starts_at'], $payload['ends_at']);
        $now = $this->clock->now();
        $this->policy->assertAcceptable($timeSlot, $now);

        $overlaps = $this->reservations->findOverlapping($payload['resource_name'], $timeSlot);
        if ($overlaps !== []) {
            throw new RuntimeException('Time slot overlaps with existing reservation');
        }

        $reservation = Reservation::book(
            Uuid::uuid7()->toString(),
            $payload['user_name'],
            $payload['resource_name'],
            $timeSlot,
            $now
        );

        $this->reservations->save($reservation);

        return $reservation->toArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listReservations(): array
    {
        return array_map(
            static fn (Reservation $reservation): array => $reservation->toArray(),
            $this->reservations->all()
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertRequired(array $payload): void
    {
        $required = ['user_name', 'resource_name', 'starts_at', 'ends_at'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new RuntimeException("{$field} is required");
            }
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function sanitize(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $sanitized;
    }
}
