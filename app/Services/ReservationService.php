<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Создать бронирование с защитой от двойного бронирования
     * Использует DATABASE LOCK для гарантии безопасности при параллельных запросах
     */
    public function create(Offer $offer, array $data): Reservation
    {
        return DB::transaction(function () use ($offer, $data) {
            // 1. LOCK FOR UPDATE - блокируем строку offer на время транзакции
            // Другие процессы будут ждать, пока мы завершим операцию
            $lockedOffer = Offer::lockForUpdate()->find($offer->id);

            if (!$lockedOffer) {
                throw new \Exception('Offer not found');
            }

            // 2. Проверяем, есть ли активные бронирования
            $activeReservations = Reservation::where('offer_id', $lockedOffer->id)
                ->where('status', '!=', 'cancelled')
                ->count();

            // 3. Проверяем, достаточно ли доступных единиц
            if ($activeReservations >= $lockedOffer->available_units) {
                throw new \Exception('No available units for this offer', 409);
            }

            // 4. Проверяем, не истекло ли предложение
            if ($lockedOffer->expires_at < Carbon::now()) {
                throw new \Exception('Offer has expired', 410);
            }

            // 5. Проверяем дублирование по client_reference
            $existingReservation = Reservation::where('client_reference', $data['client_reference'])
                ->first();

            if ($existingReservation) {
                // Если бронирование уже существует, возвращаем его (идемпотентность)
                return $existingReservation;
            }

            // 6. Создаём новое бронирование
            $reservation = Reservation::create([
                'offer_id' => $lockedOffer->id,
                'client_reference' => $data['client_reference'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'status' => 'pending',
                'expires_at' => Carbon::now()->addHours(1), // бронь действует 1 час
            ]);

            // 7. Уменьшаем available_units (опционально, если нужно отслеживать)
            // $lockedOffer->decrement('available_units');

            return $reservation;
            // LOCK будет автоматически снят в конце транзакции
        });
    }

    /**
     * Подтвердить бронирование
     */
    public function confirm(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => 'confirmed']);
        return $reservation;
    }

    /**
     * Отменить бронирование
     */
    public function cancel(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => 'cancelled']);
        return $reservation;
    }
}
