<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

interface ReservationRepositoryInterface
{
    public function save(Reservation $reservation): void;

    /**
     * @return list<Reservation>
     */
    public function findOverlapping(string $resourceName, TimeSlot $timeSlot): array;

    /**
     * @return list<Reservation>
     */
    public function all(): array;
}

