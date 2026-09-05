<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPropertiesRequest;
use App\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __construct(
        private PropertySearchService $propertySearchService
    ) {}

    /**
     * Поиск квартир
     * GET /api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2&page=1
     */
    public function search(SearchPropertiesRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', (int) config('pagination.per_page', 15));
        
        // Получаем результаты поиска
        $results = $this->propertySearchService->search(
            $request->validated(),
            $perPage
        );
        // Добавляем параметры фильтра в ссылки пагинации
        $results->appends($request->validated());
        // Форматируем результаты
        $formatted = $this->propertySearchService->formatResults($results);

        return response()->json($formatted, 200);
    }
}
