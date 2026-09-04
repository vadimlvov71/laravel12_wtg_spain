<?php
// routes/api.php
use App\Http\Controllers\Api\ImportController;

Route::prefix('imports')->group(function () {
    Route::post('/', [ImportController::class, 'store']); // POST /api/imports
    Route::get('/{import}', [ImportController::class, 'show']); // GET /api/imports/1
});