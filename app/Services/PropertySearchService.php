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

        $query = DB::query()
        ->fromSub(function ($subQuery) use ($checkIn, $checkOut, $guests, $now) {
            $subQuery->from('offers')
                ->selectRaw('
                    offers.id,
                    offers.property_id,
                    offers.supplier_id,
                    offers.price,
                    offers.currency,
                    offers.available_units,
                    offers.expires_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY offers.property_id
                        ORDER BY offers.price ASC, offers.id ASC
                    ) as rn
                ')
                ->where('offers.check_in', '<=', $checkIn)
                ->where('offers.check_out', '>=', $checkOut)
                ->where('offers.max_guests', '>=', $guests)
                ->where('offers.available_units', '>', 0)
                ->where('offers.expires_at', '>', $now);
        }, 'ranked_offers')
        ->join('properties', 'ranked_offers.property_id', '=', 'properties.id')
        ->join('suppliers', 'ranked_offers.supplier_id', '=', 'suppliers.id')
        ->where('ranked_offers.rn', 1)
        ->select(
            'properties.id as property_id',
            'properties.code',
            'properties.name',
            'properties.city',
            'ranked_offers.id as offer_id',
            'suppliers.code as supplier_code',
            'ranked_offers.price',
            'ranked_offers.currency',
            'ranked_offers.available_units',
            'ranked_offers.expires_at'
        );

        // Фильтр по городу
        if (!empty($filters['city'])) {
            $query->where('properties.city', $filters['city']);
        }

        // Сортируем по цене (на уровне БД)
        $query->orderBy('price', 'asc');

        // Пагинация на уровне БД!
        return $query->paginate($perPage);
    }
}
