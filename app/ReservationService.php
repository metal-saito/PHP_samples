<?php

declare(strict_types=1);

namespace App;

use App\Domain\Reservation\Exception\ReservationConflictException;
use App\Domain\Reservation\Exception\ReservationNotFoundException;
use App\Domain\Reservation\Exception\ReservationValidationException;
use App\Domain\Reservation\InMemoryReservationRepository;
use App\Domain\Reservation\Reservation;
use App\Domain\Reservation\ReservationPolicy;
use App\Domain\Reservation\ReservationRepositoryInterface;
use App\Domain\Reservation\ReservationStatus;
use App\Domain\Reservation\TimeSlot;
use App\Support\Clock\Clock;
use App\Support\Clock\SystemClock;
use Ramsey\Uuid\Uuid;
use ValueError;

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
        $this->assertRequired($payload, ['user_name', 'resource_name', 'starts_at', 'ends_at']);

        $timeSlot = $this->buildTimeSlot($payload);
        $now = $this->clock->now();
        $existingDailyReservations = $this->countDailyReservations(
            $payload['user_name'],
            $timeSlot->dayKey()
        );
        $this->policy->assertAcceptable($timeSlot, $now, $existingDailyReservations);

        $this->assertNoOverlap($payload['resource_name'], $timeSlot);

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
     * @param array<string, mixed> $data
     */
    public function rescheduleReservation(string $reservationId, array $data): array
    {
        $reservation = $this->getReservationOrFail($reservationId);
        $payload = $this->sanitize($data);
        $this->assertRequired($payload, ['starts_at', 'ends_at']);

        $timeSlot = $this->buildTimeSlot($payload);
        $now = $this->clock->now();

        $existingDailyReservations = $this->countDailyReservations(
            $reservation->userName(),
            $timeSlot->dayKey(),
            $reservation->id()
        );
        $this->policy->assertAcceptable($timeSlot, $now, $existingDailyReservations);

        $this->assertNoOverlap($reservation->resourceName(), $timeSlot, $reservation->id());

        $updated = $reservation->reschedule($timeSlot, $now);
        $this->reservations->save($updated);

        return $updated->toArray();
    }

    public function cancelReservation(string $reservationId): array
    {
        $reservation = $this->getReservationOrFail($reservationId);
        $cancelled = $reservation->cancel($this->clock->now());
        $this->reservations->save($cancelled);

        return $cancelled->toArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listReservations(?string $resourceName = null, ?string $status = null): array
    {
        $statusFilter = $this->normalizeStatus($status);

        return array_values(array_map(
            static fn (Reservation $reservation): array => $reservation->toArray(),
            array_filter(
                $this->reservations->all(),
                static function (Reservation $reservation) use ($resourceName, $statusFilter): bool {
                    if ($resourceName !== null && $reservation->resourceName() !== $resourceName) {
                        return false;
                    }

                    if ($statusFilter !== null && $reservation->status() !== $statusFilter) {
                        return false;
                    }

                    return true;
                }
            )
        ));
    }

    public function statistics(): array
    {
        $reservations = $this->reservations->all();

        $statusBuckets = [
            ReservationStatus::Booked->value => 0,
            ReservationStatus::Cancelled->value => 0,
            ReservationStatus::Completed->value => 0,
        ];

        $resources = [];
        $nextReservationAt = null;

        foreach ($reservations as $reservation) {
            $statusBuckets[$reservation->status()->value] ??= 0;
            $statusBuckets[$reservation->status()->value]++;

            $resourceName = $reservation->resourceName();
            $resources[$resourceName] ??= [
                'resource_name' => $resourceName,
                'total_reservations' => 0,
                'active_reservations' => 0,
                'upcoming_slots' => [],
            ];

            $resources[$resourceName]['total_reservations']++;
            if ($reservation->status()->isActive()) {
                $resources[$resourceName]['active_reservations']++;
                $resources[$resourceName]['upcoming_slots'][] = $reservation->timeSlot()->toArray();

                $startsAt = $reservation->timeSlot()->startsAt();
                if ($nextReservationAt === null || $startsAt < $nextReservationAt) {
                    $nextReservationAt = $startsAt;
                }
            }
        }

        $resourceStats = array_values($resources);
        usort(
            $resourceStats,
            static fn (array $left, array $right): int => $right['active_reservations'] <=> $left['active_reservations']
        );

        return [
            'totals' => [
                'reservations' => count($reservations),
                'status_breakdown' => $statusBuckets,
                'next_reservation_at' => $nextReservationAt?->format(DATE_ATOM),
            ],
            'resources' => array_map(
                static function (array $resource): array {
                    $resource['upcoming_slots'] = array_slice($resource['upcoming_slots'], 0, 3);
                    return $resource;
                },
                $resourceStats
            ),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertRequired(array $payload, array $required): void
    {
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new ReservationValidationException("{$field} is required");
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

    /**
     * @param array<string, mixed> $payload
     */
    private function buildTimeSlot(array $payload): TimeSlot
    {
        return TimeSlot::fromIso8601(
            (string) $payload['starts_at'],
            (string) $payload['ends_at']
        );
    }

    private function getReservationOrFail(string $reservationId): Reservation
    {
        $reservation = $this->reservations->findById($reservationId);

        if ($reservation === null) {
            throw new ReservationNotFoundException("Reservation {$reservationId} not found.");
        }

        return $reservation;
    }

    private function assertNoOverlap(string $resourceName, TimeSlot $timeSlot, ?string $ignoreReservationId = null): void
    {
        $conflicts = array_filter(
            $this->reservations->findOverlapping($resourceName, $timeSlot),
            static function (Reservation $reservation) use ($ignoreReservationId): bool {
                if (!$reservation->status()->isActive()) {
                    return false;
                }

                if ($ignoreReservationId !== null && $reservation->id() === $ignoreReservationId) {
                    return false;
                }

                return true;
            }
        );

        if ($conflicts !== []) {
            throw new ReservationConflictException('Time slot overlaps with existing reservation');
        }
    }

    private function countDailyReservations(
        string $userName,
        string $dayKey,
        ?string $ignoreReservationId = null
    ): int {
        return count(
            array_filter(
                $this->reservations->all(),
                static function (Reservation $reservation) use ($userName, $dayKey, $ignoreReservationId): bool {
                    if ($reservation->userName() !== $userName) {
                        return false;
                    }

                    if ($reservation->timeSlot()->dayKey() !== $dayKey) {
                        return false;
                    }

                    if (!$reservation->status()->isActive()) {
                        return false;
                    }

                    if ($ignoreReservationId !== null && $reservation->id() === $ignoreReservationId) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    private function normalizeStatus(?string $status): ?ReservationStatus
    {
        if ($status === null) {
            return null;
        }

        try {
            return ReservationStatus::from($status);
        } catch (ValueError $exception) {
            throw new ReservationValidationException($exception->getMessage(), previous: $exception);
        }
    }
}
