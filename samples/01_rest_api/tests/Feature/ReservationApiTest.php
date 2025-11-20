<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_reservations(): void
    {
        Reservation::factory()->create([
            'user_name' => 'Alice',
            'resource_name' => 'Room-A',
        ]);

        $response = $this->getJson('/api/v1/reservations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'user_name',
                    'resource_name',
                    'starts_at',
                    'ends_at',
                    'status',
                ],
            ]);
    }

    public function test_can_create_reservation(): void
    {
        $payload = [
            'user_name' => 'Alice',
            'resource_name' => 'Room-A',
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHour()->toIso8601String(),
        ];

        $response = $this->postJson('/api/v1/reservations', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'user_name' => 'Alice',
                'resource_name' => 'Room-A',
            ]);

        $this->assertDatabaseHas('reservations', [
            'user_name' => 'Alice',
            'resource_name' => 'Room-A',
        ]);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_name', 'resource_name', 'starts_at', 'ends_at']);
    }

    public function test_rejects_overlapping_reservations(): void
    {
        $existing = Reservation::factory()->create([
            'resource_name' => 'Room-A',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        $payload = [
            'user_name' => 'Bob',
            'resource_name' => 'Room-A',
            'starts_at' => $existing->starts_at->addMinutes(30)->toIso8601String(),
            'ends_at' => $existing->ends_at->addMinutes(30)->toIso8601String(),
        ];

        $response = $this->postJson('/api/v1/reservations', $payload);

        $response->assertStatus(409)
            ->assertJsonFragment([
                'error' => 'Time slot overlaps with existing reservation',
            ]);
    }

    public function test_can_cancel_reservation(): void
    {
        $reservation = Reservation::factory()->create();

        $response = $this->deleteJson("/api/v1/reservations/{$reservation->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_returns_404_for_unknown_reservation(): void
    {
        $response = $this->deleteJson('/api/v1/reservations/999');

        $response->assertStatus(404);
    }
}

