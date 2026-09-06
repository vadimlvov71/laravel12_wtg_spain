<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'name' => $this->faker->company(),
           // 'email' => $this->faker->companyEmail(),
           // 'is_blocked' => false,
        ];
    }

    /**
     * Заблокированный поставщик
     */
    public function blocked(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_blocked' => true,
            ];
        });
    }
}
