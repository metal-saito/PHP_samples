<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

final class InMemoryReservationRepository implements ReservationRepositoryInterface
{
    /**
     * @var array<string, Reservation>
     */
    private array $items = [];

    public function save(Reservation $reservation): void
    {
        $this->items[$reservation->id()] = $reservation;
    }

    public function findById(string $id): ?Reservation
    {
        return $this->items[$id] ?? null;
    }

    public function findOverlapping(string $resourceName, TimeSlot $timeSlot): array
    {
        return array_values(
            array_filter(
                $this->items,
                static function (Reservation $reservation) use ($resourceName, $timeSlot): bool {
                    return $reservation->resourceName() === $resourceName
                        && $reservation->overlaps($timeSlot);
                }
            )
        );
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

