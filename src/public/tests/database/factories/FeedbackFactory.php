<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(4, 5),
            'comment' => fake()->randomElement([
                'Pelayanan sangat baik!',
                'Cepat dan ramah.',
                'Sangat puas dengan pelayanannya.',
            ]),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(1, 2),
            'comment' => fake()->randomElement([
                'Pelayanan lambat.',
                'Kurang ramah.',
                'Perlu diperbaiki.',
            ]),
        ]);
    }
}
