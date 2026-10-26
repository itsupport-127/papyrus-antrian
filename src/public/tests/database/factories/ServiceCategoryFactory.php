<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->randomElement([
                'Informasi Umum',
                'Komplain',
                'Pembukaan Rekening',
                'Pembayaran Tagihan',
                'Pendaftaran Baru',
                'Konsultasi',
            ]),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
