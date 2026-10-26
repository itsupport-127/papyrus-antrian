<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Counter>
 */
class CounterFactory extends Factory
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
            'name' => 'Loket ' . fake()->numberBetween(1, 10),
            // Default: loket CS & Kasir (registrasi/order digabung dengan pembayaran & cetak).
            'stages' => ['registration', 'payment'],
            'is_active' => true,
            'current_operator_id' => null,
        ];
    }

    public function csKasir(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'CS & Kasir',
            'stages' => ['registration', 'payment'],
        ]);
    }

    public function fotografer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Fotografer',
            'stages' => ['photo_session'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_operator_id' => User::factory()->create(['role' => 'operator'])->id,
        ]);
    }
}
