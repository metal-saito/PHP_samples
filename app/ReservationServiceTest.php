<?php

namespace App;

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
        $this->assertEquals('Room-A', $result['resource_name']);
        $this->assertEquals('booked', $result['status']);
    }

    public function testCreateReservationWithMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('user_name is required');

        $data = [
            'resource_name' => 'Room-A',
            'starts_at' => '2025-01-02T09:00:00Z',
            'ends_at' => '2025-01-02T10:00:00Z',
        ];

        $this->service->createReservation($data);
    }
}

