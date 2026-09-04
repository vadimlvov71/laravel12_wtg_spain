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
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Количество попыток
    public $backoff = [60, 300, 600]; // Интервалы повторов (сек)
    public $timeout = 300; // Timeout 5 минут

    private array $offers;

    public function __construct(
        private Import $import,
        array $offers
    ) {
        $this->offers = $offers;
    }

    public function handle(): void
    {
        try {
            // Обновляем статус на processing
            $this->import->update(['status' => 'processing']);

            $successCount = 0;
            $failedOffers = [];

            foreach ($this->offers as $offerData) {
                try {
                    // 1. Находим или создаем Property по коду
                    $property = Property::firstOrCreate(
                        ['code' => $offerData['property']['code']],
                        [
                            'name' => $offerData['property']['name'],
                            'city' => $offerData['property']['city'],
                        ]
                    );

                    // 2. Находим или обновляем Offer
                    $offer = Offer::updateOrCreate(
                        [
                            'supplier_id' => $this->import->supplier_id,
                            'external_id' => $offerData['external_id']
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
                        'external_id' => $offerData['external_id']
                    ]);

                } catch (Throwable $e) {
                    $failedOffers[] = [
                        'external_id' => $offerData['external_id'],
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error("Failed to process offer", [
                        'import_id' => $this->import->id,
                        'external_id' => $offerData['external_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Обновляем статус импорта
            if (empty($failedOffers)) {
                $this->import->update(['status' => 'completed']);
                Log::info("Import completed", ['import_id' => $this->import->id]);
            } else {
                // Если были ошибки, но некоторые успешно обработаны
                $this->import->update([
                    'status' => 'completed',
                    'error_message' => json_encode([
                        'success' => $successCount,
                        'failed' => count($failedOffers),
                        'failed_offers' => $failedOffers
                    ])
                ]);
            }

        } catch (Throwable $e) {
            Log::error("Import processing failed", [
                'import_id' => $this->import->id,
                'error' => $e->getMessage()
            ]);

            // Обновляем статус на failed
            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->import->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);

        Log::error("Import job failed permanently", [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage()
        ]);
    }
}