<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PropertySearchService
{
    /**
     * Поиск квартир - ВСЕ операции на уровне БД!
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $checkIn = isset($filters['check_in']) ? Carbon::parse($filters['check_in']) : null;
        $checkOut = isset($filters['check_out']) ? Carbon::parse($filters['check_out']) : null;
        $guests = $filters['guests'] ?? 1;
        $now = Carbon::now();

        // Строим query которая:
        // 1. Находит все валидные offers
        // 2. Группирует по property_id
        // 3. Берёт минимальную цену по каждой property
        // 4. Сортирует по цене
        // 5. Применяет пагинацию на уровне БД

        $query = DB::table('offers')
            ->select(
                'properties.id as property_id',
                'properties.code',
                'properties.name',
                'properties.city',
                'offers.id as offer_id',
                'suppliers.code as supplier_code',
                'offers.price',
                'offers.currency',
                'offers.available_units',
                'offers.expires_at'
            )
            ->join('properties', 'offers.property_id', '=', 'properties.id')
            ->join('suppliers', 'offers.supplier_id', '=', 'suppliers.id')
            ->whereIn('offers.id', function ($subQuery) use ($checkIn, $checkOut, $guests, $now) {
                // Подзапрос: для каждой property берём offer с минимальной ценой
                $subQuery->selectRaw('MIN(id) OVER (PARTITION BY property_id ORDER BY price ASC)')
                    ->from('offers')
                    ->where('check_in', '<=', $checkIn)
                    ->where('check_out', '>=', $checkOut)
                    ->where('max_guests', '>=', $guests)
                    ->where('available_units', '>', 0)
                    ->where('expires_at', '>', $now);
            });

        // Фильтр по городу
        if (!empty($filters['city'])) {
            $query->where('properties.city', $filters['city']);
        }

        // Сортируем по цене (на уровне БД)
        $query->orderBy('offers.price', 'asc');

        // Пагинация на уровне БД!
        return $query->paginate($perPage);
    }
}
