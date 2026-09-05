<?php

namespace App\Services;

use App\Models\Import;
use App\Models\SupplierImportLog;
use App\Jobs\ProcessImportJob;
use Illuminate\Support\Facades\Log;

class ImportService
{
    

    /**
     * Обработать импорт
     */
    public function process(array $requestData, ?string $ipAddress = null): array
    {
        $supplierCode = $requestData['supplier'] ?? null;
         // TO DO
        Log::warning('Supplier not found', ['supplier_code' => $supplierCode]);
        // 1. Валидируем поставщика
       
    }
}
