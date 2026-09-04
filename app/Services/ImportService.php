<?php

namespace App\Services;

use App\Models\Import;
use App\Models\SupplierImportLog;
use App\Jobs\ProcessImportJob;
use Illuminate\Support\Facades\Log;

class ImportService
{
    public function __construct(
        private SupplierValidationService $supplierValidation
    ) {}

    /**
     * Обработать импорт
     */
    public function process(array $requestData, ?string $ipAddress = null): array
    {
        $supplierCode = $requestData['supplier'] ?? null;
        
        // 1. Валидируем поставщика
        $validation = $this->supplierValidation->validate($supplierCode);

        if (!$validation['valid']) {
            $this->logImportError(
                $supplierCode,
                $validation['error_code'],
                $validation['error'],
                $requestData,
                $ipAddress
            );

            return [
                'success' => false,
                'status_code' => 403,
                'error' => $validation['error'],
                'error_code' => $validation['error_code']
            ];
        }

        $supplier = $validation['supplier'];

        // 2. Проверяем дублирование импорта
        $existingImport = Import::where('supplier_id', $supplier->id)
            ->where('external_import_id', $requestData['external_import_id'])
            ->first();

        if ($existingImport) {
            Log::info('Import already exists', ['id' => $existingImport->id]);
            
            return [
                'success' => true,
                'status_code' => 202,
                'import_id' => $existingImport->id,
                'status' => $existingImport->status,
                'message' => 'Импорт уже существует'
            ];
        }

        try {
            // 3. Создаем новый импорт
            $import = Import::create([
                'supplier_id' => $supplier->id,
                'external_import_id' => $requestData['external_import_id'],
                'sent_at' => $requestData['sent_at'],
                'status' => 'pending',
                'offers_count' => count($requestData['offers'])
            ]);

            Log::info('Import created', ['id' => $import->id]);

            // 4. Запускаем Job в очередь
            ProcessImportJob::dispatch($import, $requestData['offers']);
            Log::info('Job dispatched', ['import_id' => $import->id]);

            // 5. Логируем успешное создание
            SupplierImportLog::create([
                'supplier_code' => $supplierCode,
                'status' => 'success',
                'external_import_id' => $requestData['external_import_id'],
                'ip_address' => $ipAddress,
            ]);

            return [
                'success' => true,
                'status_code' => 202,
                'import_id' => $import->id,
                'status' => $import->status,
                'message' => 'Импорт принят на обработку'
            ];

        } catch (\Exception $e) {
            Log::error('Import processing error', [
                'supplier_code' => $supplierCode,
                'error' => $e->getMessage()
            ]);

            $this->logImportError(
                $supplierCode,
                'ERROR',
                $e->getMessage(),
                $requestData,
                $ipAddress
            );

            return [
                'success' => false,
                'status_code' => 500,
                'error' => 'Ошибка при обработке импорта',
                'error_code' => 'IMPORT_ERROR'
            ];
        }
    }

    /**
     * Логировать ошибку импорта
     */
    private function logImportError(
        string $supplierCode,
        string $status,
        string $errorMessage,
        array $requestData,
        ?string $ipAddress
    ): void {
        SupplierImportLog::create([
            'supplier_code' => $supplierCode,
            'status' => $this->mapStatusToLog($status),
            'external_import_id' => $requestData['external_import_id'] ?? null,
            'error_message' => $errorMessage,
            'request_data' => $requestData,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Преобразовать код ошибки в статус лога
     */
    private function mapStatusToLog(string $errorCode): string
    {
        return match($errorCode) {
            'SUPPLIER_NOT_FOUND' => 'not_found',
            'SUPPLIER_BLOCKED' => 'blocked',
            default => 'error'
        };
    }
}
