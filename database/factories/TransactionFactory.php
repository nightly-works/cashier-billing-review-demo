<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booked_on' => fake()->dateTimeBetween('-3 months'),
            'label' => fake()->words(3, true),
            'amount_cents' => fake()->numberBetween(-50000, 150000),
        ];
    }
}
