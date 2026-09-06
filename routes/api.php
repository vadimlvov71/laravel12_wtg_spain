<?php
// routes/api.php
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\ReservationController;

Route::prefix('imports')->group(function () {
    Route::post('/', [ImportController::class, 'store']); // POST /api/imports
    Route::get('/{import}', [ImportController::class, 'show']); // GET /api/imports/1

});

Route::get('/properties', [PropertyController::class, 'search']);
Route::post('/offers/{offer}/reservations', [ReservationController::class, 'store']); // POST /api/offers
Route::prefix('offers')->group(function () {
    Route::post('/{offer}/reservations', [ReservationController::class, 'store']); // POST /api/offers
    Route::get('/{offer}/reservations/{reservation}', [ReservationController::class, 'show']); // GET /api/offers/1
    Route::post('/{offer}/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
    Route::post('/{offer}/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
});
