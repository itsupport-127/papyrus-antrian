<?php

namespace Database\Factories;

use App\Models\Counter;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'counter_id' => null,
            'served_by' => null,
            'ticket_number' => fn (array $attributes) =>
                Location::find($attributes['location_id'])?->code . '-' . fake()->unique()->numberBetween(1, 999),
            'customer_name' => fake()->optional()->name(),
            'customer_phone' => fake()->optional()->phoneNumber(),
            'status' => 'waiting',
            'called_at' => null,
            'served_at' => null,
            'completed_at' => null,
            'service_time' => null,
            'notes' => null,
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
        ]);
    }

    public function calling(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'calling',
            'counter_id' => Counter::factory(),
            'served_by' => User::factory()->create(['role' => 'operator'])->id,
            'called_at' => now(),
        ]);
    }

    public function serving(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'serving',
            'counter_id' => Counter::factory(),
            'served_by' => User::factory()->create(['role' => 'operator'])->id,
            'called_at' => now()->subMinutes(2),
            'served_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'counter_id' => Counter::factory(),
            'served_by' => User::factory()->create(['role' => 'operator'])->id,
            'called_at' => now()->subMinutes(10),
            'served_at' => now()->subMinutes(8),
            'completed_at' => now(),
            'service_time' => 480,
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'skipped',
            'completed_at' => now(),
        ]);
    }
}
