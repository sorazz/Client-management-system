<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\ClientApiController;

Route::prefix('clients')->group(function () {
    Route::get('/', [ClientApiController::class, 'index']);
    Route::get('/{id}', [ClientApiController::class, 'show']);
    Route::post('/import', [ClientApiController::class, 'import']);
    Route::get('/export/file', [ClientApiController::class, 'exportFile']);
    Route::get('/import/status', [ClientApiController::class, 'importStatus']);
});
