<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'external_import_id' => $this->faker->unique()->word() . '-' . $this->faker->randomNumber(5),
            'sent_at' => $this->faker->dateTime(),
            'status' => 'pending',
            'total_offers' => $this->faker->numberBetween(1, 50),
            'error_message' => null,
           // 'ip_address' => $this->faker->ipv4(),
        ];
    }

    /**
     * Завершённый импорт
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    /**
     * Импорт с ошибкой
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => 'Processing error',
            ];
        });
    }

    /**
     * Обрабатывается
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
            ];
        });
    }
}
