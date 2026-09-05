<?php

namespace App\Services;

use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertySearchService
{
    /**
     * Поиск квартир с фильтрацией и сортировкой
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['offers' => function ($query) use ($filters) {
                $this->applyOfferFilters($query, $filters);
            }])
            ->whereHas('offers', function ($query) use ($filters) {
                $this->applyOfferFilters($query, $filters);
            });

        // Фильтр по городу
        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        // Получаем страницу
        return $query->paginate($perPage);
    }

    /**
     * Применить фильтры к offers запросу
     */
    private function applyOfferFilters($query, array $filters): void
    {
        $now = Carbon::now();
        $checkIn = isset($filters['check_in']) ? Carbon::parse($filters['check_in']) : null;
        $checkOut = isset($filters['check_out']) ? Carbon::parse($filters['check_out']) : null;
        $guests = $filters['guests'] ?? 1;

        // Дата заезда совпадает или позже
        if ($checkIn) {
            $query->where('check_in', '<=', $checkIn);
        }

        // Дата выезда совпадает или раньше
        if ($checkOut) {
            $query->where('check_out', '>=', $checkOut);
        }

        // Максимальное количество гостей
        $query->where('max_guests', '>=', $guests);

        // Доступные единицы > 0
        $query->where('available_units', '>', 0);

        // Срок действия предложения
        $query->where('expires_at', '>', $now);

        // Сортируем по цене (самое дешевое первым)
        $query->orderBy('price', 'asc');
    }

    /**
     * Форматировать результаты поиска
     */
    public function formatResults(LengthAwarePaginator $results): array
    {
        $data = $results->map(function ($property) {
            // Получаем самое дешевое предложение (первое в отсортированном массиве)
            $bestOffer = $property->offers->sortBy('price')->first();

            return [
                'code' => $property->code,
                'name' => $property->name,
                'city' => $property->city,
                'best_offer' => $bestOffer ? [
                    'id' => $bestOffer->id,
                    'supplier' => $bestOffer->supplier->code,
                    'price' => $bestOffer->price,
                    'currency' => $bestOffer->currency,
                    'available_units' => $bestOffer->available_units,
                    'expires_at' => $bestOffer->expires_at->toIso8601String(),
                ] : null,
            ];
        })->filter(fn($item) => $item['best_offer'] !== null)->values();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
                'next' => $results->nextPageUrl(),
                'prev' => $results->previousPageUrl(),
            ]
        ];
    }
}
