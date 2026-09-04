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

class ImportController extends Controller
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        Log::info('Import request received', ['supplier' => $request->supplier]);

        // 1. Получаем поставщика
        $supplier = Supplier::where('code', $request->supplier)->firstOrFail();

        // 2. Проверяем, не был ли уже обработан этот импорт
        $existingImport = Import::where('supplier_id', $supplier->id)
            ->where('external_import_id', $request->external_import_id)
            ->first();

        if ($existingImport) {
            // Импорт уже существует
            return response()->json([
                'data' => [
                    'id' => $existingImport->id,
                    'status' => $existingImport->status
                ]
            ], 202);
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
        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status
            ]
        ], 202);
    }

    /**
     * Получить статус импорта
     */
    public function show(Import $import): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status,
                'offers_count' => $import->offers_count,
                'error' => $import->error_message,
                'created_at' => $import->created_at,
                'updated_at' => $import->updated_at,
            ]
        ]);
    }
}