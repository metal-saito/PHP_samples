<?php

declare(strict_types=1);

namespace App\Domain\Reservation;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final class TimeSlot
{
    public function __construct(
        private readonly DateTimeImmutable $startsAt,
        private readonly DateTimeImmutable $endsAt
    ) {
        if ($endsAt <= $startsAt) {
            throw new InvalidArgumentException('ends_at must be after starts_at');
        }
    }

    public static function fromIso8601(string $startsAt, string $endsAt): self
    {
        return new self(
            self::createImmutable($startsAt),
            self::createImmutable($endsAt)
        );
    }

    public function overlaps(self $other): bool
    {
        return $this->startsAt < $other->endsAt && $other->startsAt < $this->endsAt;
    }

    public function durationInMinutes(): int
    {
        $interval = $this->startsAt->diff($this->endsAt);
        return (int) $interval->format('%r%a') * 24 * 60 + $interval->h * 60 + $interval->i;
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function dayKey(): string
    {
        return $this->startsAt->format('Y-m-d');
    }

    public function toArray(): array
    {
        return [
            'starts_at' => $this->startsAt->format(DATE_ATOM),
            'ends_at' => $this->endsAt->format(DATE_ATOM),
        ];
    }

    private static function createImmutable(string $value): DateTimeImmutable
    {
        $dateTime = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $value);
        if ($dateTime === false) {
            throw new InvalidArgumentException("Invalid ISO8601 datetime: {$value}");
        }

        return $dateTime;
    }
}

