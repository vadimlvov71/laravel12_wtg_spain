<?php
// app/Http/Controllers/Api/ImportController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Jobs\ProcessImportJob;
use App\Models\Supplier;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\ImportService;
use App\Http\Resources\ImportResource;

class ImportController extends Controller
{
     public function __construct(private ImportService $importService)
    {
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        Log::info('Import request received', ['supplier' => $request->supplier]);
       
        Log::info('ImportService initialized');
        // 1. Получаем поставщика
        $supplier = Supplier::where('code', $request->supplier)->firstOrFail();

        if (!$supplier) {
            //TO DO
            //we can create handle with insert in database
            $this->importService->process($request->all(), $request->ip());
        }
        if ($supplier->is_blocked) {
            //TO DO
            //we can create handle with insert in database
        }
        // 2. Проверяем, не был ли уже обработан этот импорт
        $existingImport = Import::where('supplier_id', $supplier->id)
            ->where('external_import_id', $request->external_import_id)
            ->first();

        if ($existingImport) {
            // Импорт уже существует
            Log::info('Import exists');
            return (new ImportResource($existingImport))
            ->additional([
                'message' => 'Import already exists. Re-import is not allowed.',
            ])
            ->response()
            ->setStatusCode(409);
        }

        // 3. Создаем новый импорт со статусом pending
        $import = Import::create([
            'supplier_id' => $supplier->id,
            'external_import_id' => $request->external_import_id,
            'sent_at' => $request->sent_at,
            'status' => 'pending',
            'offers_count' => count($request->offers)
        ]);
        Log::info('Import created', ['id' => $import->id]);
        // 4. Отправляем обработку в очередь
        ProcessImportJob::dispatch($import, $request->offers);
        Log::info('Job dispatched', ['import_id' => $import->id]);
        // 5. Возвращаем 202 Accepted
        return (new ImportResource($import))
            ->response()
            ->setStatusCode(202);
    }

    /**
     * Получить статус импорта
     */
    public function show(Import $import): ImportResource
    {
        return new ImportResource($import);
    }
}