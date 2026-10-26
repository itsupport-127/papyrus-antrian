<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'service_category_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->optional()->safeEmail(),
            'booking_date' => today()->addDay(),
            'booking_time' => fake()->randomElement(['09:00', '10:00', '11:00', '13:00', '14:00', '15:00']),
            'status' => 'pending',
            'booking_code' => fn (array $attributes) =>
                'BK-' . (Location::find($attributes['location_id'])?->code ?? 'XX') . '-' . fake()->unique()->numberBetween(1, 999),
            'ticket_id' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function forToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => today(),
        ]);
    }
}
