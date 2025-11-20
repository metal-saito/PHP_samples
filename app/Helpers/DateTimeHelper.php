<?php

namespace App\Helpers;

class DateTimeHelper
{
    /**
     * ISO8601形式の文字列をDateTimeに変換
     */
    public static function parse(string $dateTime): \DateTime
    {
        $dt = \DateTime::createFromFormat(\DateTime::ATOM, $dateTime);
        if ($dt === false) {
            throw new \InvalidArgumentException("Invalid date format: {$dateTime}");
        }
        return $dt;
    }

    /**
     * DateTimeをISO8601形式の文字列に変換
     */
    public static function format(\DateTime $dateTime): string
    {
        return $dateTime->format(\DateTime::ATOM);
    }

    /**
     * 時間範囲のバリデーション
     */
    public static function validateRange(\DateTime $startsAt, \DateTime $endsAt): void
    {
        if ($endsAt <= $startsAt) {
            throw new \InvalidArgumentException('ends_at must be after starts_at');
        }
    }
}

