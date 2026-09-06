<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $reservationService
    ) {}

    /**
     * Создать бронирование
     * POST /api/offers/{offer}/reservations
     */
    public function store(Offer $offer, CreateReservationRequest $request): JsonResponse
    {
        try {
            // Создаём бронирование через сервис (с защитой от двойного бронирования)
            $reservation = $this->reservationService->create(
                $offer,
                $request->validated()
            );

            Log::info('Reservation created', [
                'offer_id' => $offer->id,
                'reservation_id' => $reservation->id,
                'client_reference' => $request->client_reference
            ]);

            return response()->json(
                new ReservationResource($reservation),
                201
            );
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 400;

            Log::warning('Reservation creation failed', [
                'offer_id' => $offer->id,
                'client_reference' => $request->client_reference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'error_code' => match ($statusCode) {
                    409 => 'NO_AVAILABLE_UNITS',
                    410 => 'OFFER_EXPIRED',
                    default => 'RESERVATION_FAILED'
                }
            ], $statusCode);
        }
    }

    /**
     * Получить бронирование
     */
    public function show(Offer $offer, $reservation): JsonResponse
    {
        $reservation = $offer->reservations()->findOrFail($reservation);
        return response()->json(new ReservationResource($reservation));
    }

    /**
     * Подтвердить бронирование
     */
    public function confirm(Offer $offer, $reservation): JsonResponse
    {
        $reservation = $offer->reservations()->findOrFail($reservation);
        $confirmed = $this->reservationService->confirm($reservation);

        return response()->json(new ReservationResource($confirmed));
    }

    /**
     * Отменить бронирование
     */
    public function cancel(Offer $offer, $reservation): JsonResponse
    {
        $reservation = $offer->reservations()->findOrFail($reservation);
        $cancelled = $this->reservationService->cancel($reservation);

        return response()->json(new ReservationResource($cancelled));
    }
}
