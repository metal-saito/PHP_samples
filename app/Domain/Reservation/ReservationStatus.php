<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

enum ReservationStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function isActive(): bool
    {
        return $this === self::Booked;
    }
}


