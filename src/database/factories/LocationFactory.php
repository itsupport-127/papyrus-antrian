<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            ['name' => 'Customer Service', 'code' => 'CS'],
            ['name' => 'Payment', 'code' => 'PAY'],
            ['name' => 'Registration', 'code' => 'REG'],
            ['name' => 'Information', 'code' => 'INF'],
            ['name' => 'Consultation', 'code' => 'CON'],
        ];

        $service = fake()->randomElement($services);

        return [
            'name' => $service['name'],
            'code' => $service['code'] . fake()->unique()->numberBetween(1, 99),
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'is_active' => true,
            'average_service_time' => fake()->numberBetween(3, 15),
            'open_time' => '08:00',
            'close_time' => '17:00',
            'booking_mode' => 'free',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
