<?php

namespace App\Helpers;

use PHPUnit\Framework\TestCase;

class DateTimeHelperTest extends TestCase
{
    public function testParse(): void
    {
        $dateStr = '2025-01-02T09:00:00Z';
        $dt = DateTimeHelper::parse($dateStr);
        
        $this->assertEquals(2025, (int)$dt->format('Y'));
        $this->assertEquals(1, (int)$dt->format('m'));
        $this->assertEquals(2, (int)$dt->format('d'));
    }

    public function testFormat(): void
    {
        $dt = new \DateTime('2025-01-02T09:00:00Z');
        $formatted = DateTimeHelper::format($dt);
        
        $this->assertStringContainsString('2025-01-02', $formatted);
    }

    public function testValidateRange(): void
    {
        $startsAt = new \DateTime('2025-01-02T09:00:00Z');
        $endsAt = new \DateTime('2025-01-02T10:00:00Z');
        
        DateTimeHelper::validateRange($startsAt, $endsAt);
        $this->assertTrue(true); // No exception thrown
    }

    public function testValidateRangeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $startsAt = new \DateTime('2025-01-02T10:00:00Z');
        $endsAt = new \DateTime('2025-01-02T09:00:00Z');
        
        DateTimeHelper::validateRange($startsAt, $endsAt);
    }
}

