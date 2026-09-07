<?php
// app/Jobs/ProcessImportJob.php
namespace App\Jobs;

use App\Models\Import;
use App\Models\Property;
use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Количество попыток
    public $backoff = [60, 300, 600]; // Интервалы повторов (сек)
    public $timeout = 300; // Timeout 5 минут

    public function __construct(
        public Import $import,
        public array $offers
    ) {}

    public function handle(): void
    {
        Log::info("ProcessImportJob started", ['import_id' => $this->import->id]);

        try {
            DB::transaction(function () {
                $total = count($this->offers);

                // Обновляем статус на processing + счетчики
                $this->import->update([
                    'status' => 'processing',
                    'total_offers' => $total,
                    'processed_offers' => 0,
                    'error_message' => null,
                ]);

                $successCount = 0;
                $failedOffers = [];
                $processed = 0;

                foreach ($this->offers as $offerData) {
                    try {
                        // 1) Находим или создаем Property по коду
                        $property = Property::firstOrCreate(
                            ['code' => $offerData['property']['code']],
                            [
                                'name' => $offerData['property']['name'] ?? null,
                                'city' => $offerData['property']['city'] ?? null,
                            ]
                        );

                        // 2) Upsert Offer по уникальному ключу supplier_id + external_id
                        $offer = Offer::updateOrCreate(
                            [
                                'supplier_id' => $this->import->supplier_id,
                                'external_id' => $offerData['external_id'],
                            ],
                            [
                                'import_id' => $this->import->id,
                                'property_id' => $property->id,
                                'check_in' => $offerData['check_in'],
                                'check_out' => $offerData['check_out'],
                                'max_guests' => $offerData['max_guests'],
                                'price' => $offerData['price'],
                                'currency' => $offerData['currency'],
                                'available_units' => $offerData['available_units'],
                                'expires_at' => $offerData['expires_at'],
                            ]
                        );

                        $successCount++;

                        Log::info("Offer processed", [
                            'import_id' => $this->import->id,
                            'offer_id' => $offer->id,
                            'external_id' => $offerData['external_id'],
                        ]);
                    } catch (Throwable $e) {
                        $failedOffers[] = [
                            'external_id' => $offerData['external_id'] ?? null,
                            'error' => $e->getMessage(),
                        ];

                        Log::error("Failed to process offer", [
                            'import_id' => $this->import->id,
                            'external_id' => $offerData['external_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $processed++;
                    $this->import->update(['processed_offers' => $processed]);
                }

                // 3) Завершаем импорт
                $this->import->update([
                    'status' => 'completed',
                    'error_message' => empty($failedOffers) ? null : json_encode([
                        'success' => $successCount,
                        'failed' => count($failedOffers),
                        'failed_offers' => $failedOffers,
                    ], JSON_UNESCAPED_UNICODE),
                    'completed_at' => now(),
                ]);

                Log::info("Import completed", [
                    'import_id' => $this->import->id,
                    'success' => $successCount,
                    'failed' => count($failedOffers),
                ]);
            });
        } catch (Throwable $e) {
            Log::error("Import processing failed", [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            // Обновляем статус на failed
            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->import->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);

        Log::error("Import job failed permanently", [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage(),
        ]);
    }
}