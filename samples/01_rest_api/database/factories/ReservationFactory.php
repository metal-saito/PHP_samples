<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $endsAt = (clone $startsAt)->modify('+1 hour');

        return [
            'user_name' => $this->faker->name(),
            'resource_name' => 'Room-' . $this->faker->randomLetter(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'booked',
        ];
    }
}

