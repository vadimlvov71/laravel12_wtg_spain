<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаём тестового поставщика
        $this->supplier = Supplier::factory()->create([
            'code' => 'supplier-a',
        ]);
    }

    /**
     * Вспомогательный метод для получения корректной даты
     */
    private function formatDate($date = null): string
    {
        if ($date === null) {
            $date = now();
        }
        
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Вспомогательный метод для создания валидного payload оффера
     */
    private function createValidOffer(int $index = 1): array
    {
        return [
            'external_id' => "offer-{$index}",
            'property' => [
                'code' => "prop-{$index}",
                'name' => "Beautiful Apartment {$index}",
                'city' => 'Barcelona'
            ],
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 2,
            'price' => 100 + $index,
            'currency' => 'EUR',
            'available_units' => 5,
            'expires_at' => $this->formatDate(now()->addDays(30)),
        ];
    }

    /**
     * Успешное создание импорта
     */
    public function test_can_create_import_with_valid_data(): void
    {
        Queue::fake();

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
                $this->createValidOffer(2),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(202);
        $response->assertJsonStructure([
            'data' => ['id', 'status']
        ]);
        $response->assertJsonPath('data.status', 'pending');

        // Проверяем, что импорт создан в БД
        $this->assertDatabaseHas('imports', [
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-123',
            'status' => 'pending',
            'total_offers' => 2,
        ]);

        // Проверяем, что джоб отправлен в очередь
        Queue::assertPushed(ProcessImportJob::class, function ($job) {
            return $job->import->external_import_id === 'import-123';
        });
    }

    /**
     * Возвращает 202, если импорт уже существует
     */
    public function test_returns_202_if_import_already_exists(): void
    {
        Queue::fake();

        // Создаём существующий импорт
        $existingImport = Import::factory()->create([
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-123',
            'status' => 'completed',
        ]);

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(409);
        $response->assertJsonPath('data.id', $existingImport->id);
        $response->assertJsonPath('data.status', 'completed');

        // Джоб не должен быть отправлен повторно
        Queue::assertNotPushed(ProcessImportJob::class);
    }

    /**
     * Ошибка валидации, если поставщик не существует
     */
    public function test_validation_fails_if_supplier_not_found(): void
    {
        Queue::fake();

        $payload = [
            'supplier' => 'non-existent-supplier',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        // Возвращается 422 (Validation Error), а не 404
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('supplier');

        // Импорт не создан
        $this->assertDatabaseMissing('imports', [
            'external_import_id' => 'import-123',
        ]);

        // Джоб не отправлен
        Queue::assertNotPushed(ProcessImportJob::class);
    }

    /**
     * Валидация: отсутствует supplier
     */
    public function test_validation_fails_if_supplier_missing(): void
    {
        $payload = [
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('supplier');
    }

    /**
     * Валидация: отсутствует external_import_id
     */
    public function test_validation_fails_if_external_import_id_missing(): void
    {
        $payload = [
            'supplier' => 'supplier-a',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('external_import_id');
    }

    /**
     * Валидация: offers не массив
     */
    public function test_validation_fails_if_offers_not_array(): void
    {
        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => 'not-an-array'
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers');
    }

    /**
     * Валидация: offers пустой массив
     */
    public function test_validation_fails_if_offers_empty(): void
    {
        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => []
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers');
    }

    /**
     * Валидация: sent_at некорректный формат
     */
    public function test_validation_fails_if_sent_at_invalid_format(): void
    {
        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => 'invalid-date',
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sent_at');
    }

    /**
     * Валидация: отсутствует property.code
     */
    public function test_validation_fails_if_property_code_missing(): void
    {
        $offer = $this->createValidOffer(1);
        unset($offer['property']['code']);

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.property.code');
    }

    /**
     * Валидация: отсутствует property.name
     */
    public function test_validation_fails_if_property_name_missing(): void
    {
        $offer = $this->createValidOffer(1);
        unset($offer['property']['name']);

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.property.name');
    }

    /**
     * Валидация: отсутствует property.city
     */
    public function test_validation_fails_if_property_city_missing(): void
    {
        $offer = $this->createValidOffer(1);
        unset($offer['property']['city']);

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.property.city');
    }

    /**
     * Валидация: check_out должен быть после check_in
     */
    public function test_validation_fails_if_check_out_before_check_in(): void
    {
        $offer = $this->createValidOffer(1);
        $offer['check_in'] = '2026-10-15';
        $offer['check_out'] = '2026-10-10';

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.check_out');
    }

    /**
     * Валидация: currency должен быть 3 символа
     */
    public function test_validation_fails_if_currency_invalid_length(): void
    {
        $offer = $this->createValidOffer(1);
        $offer['currency'] = 'EURO'; // 4 символа вместо 3

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.currency');
    }

    /**
     * Валидация: price не может быть отрицательным
     */
    public function test_validation_fails_if_price_negative(): void
    {
        $offer = $this->createValidOffer(1);
        $offer['price'] = -50;

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-123',
            'sent_at' => $this->formatDate(),
            'offers' => [$offer]
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('offers.0.price');
    }

    /**
     * Получить статус импорта
     */
    public function test_can_get_import_status(): void
    {
        $import = Import::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => 'processing',
        ]);

        $import->update([
            'total_offers' => 10,
            'processed_offers' => 4,
        ]);
        $import->refresh();

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $import->id);
        $response->assertJsonPath('data.status', 'processing');
        $response->assertJsonPath('data.total_offers', 10);
        $response->assertJsonPath('data.processed_offers', 4);
    }

    /**
     * 404 при получении несуществующего импорта
     */
    public function test_returns_404_when_import_not_found(): void
    {
        $response = $this->getJson('/api/imports/999999');

        $response->assertStatus(404);
    }

    /**
     * Импорт с большим количеством offers
     */
    public function test_can_create_import_with_many_offers(): void
    {
        Queue::fake();

        $offers = array_map(function ($i) {
            return $this->createValidOffer($i);
        }, range(1, 50));

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-large',
            'sent_at' => $this->formatDate(),
            'offers' => $offers
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(202);
        $response->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('imports', [
            'external_import_id' => 'import-large',
            'total_offers' => 50,
        ]);

        Queue::assertPushed(ProcessImportJob::class);
    }

    /**
     * Множественные импорты от одного поставщика
     */
    public function test_can_create_multiple_imports_from_same_supplier(): void
    {
        Queue::fake();

        // Первый импорт
        $payload1 = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-1',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(1),
            ]
        ];

        $response1 = $this->postJson('/api/imports', $payload1);
        $response1->assertStatus(202);

        // Второй импорт
        $payload2 = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2',
            'sent_at' => $this->formatDate(),
            'offers' => [
                $this->createValidOffer(2),
            ]
        ];

        $response2 = $this->postJson('/api/imports', $payload2);
        $response2->assertStatus(202);

        // Оба импорта должны быть созданы
        $this->assertDatabaseHas('imports', [
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-1',
        ]);

        $this->assertDatabaseHas('imports', [
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-2',
        ]);

        // Два джоба должны быть отправлены
        Queue::assertPushed(ProcessImportJob::class, 2);
    }
}
