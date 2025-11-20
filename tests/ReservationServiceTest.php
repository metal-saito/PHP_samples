<?php

namespace Tests;

use App\ReservationService;
use PHPUnit\Framework\TestCase;

class ReservationServiceTest extends TestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        $this->service = new ReservationService();
    }

    public function testCreateReservation(): void
    {
        $data = [
            'user_name' => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at' => '2025-01-02T09:00:00Z',
            'ends_at' => '2025-01-02T10:00:00Z',
        ];

        $result = $this->service->createReservation($data);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('Alice', $result['user_name']);
        $this->assertEquals('booked', $result['status']);
    }
}

