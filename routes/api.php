<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\ClientApiController;

Route::prefix('clients')->group(function () {
    Route::get('/', [ClientApiController::class, 'index']);
    Route::post('/import', [ClientApiController::class, 'import']);
    Route::get('/export/file', [ClientApiController::class, 'exportFile']);
    Route::get('/import/status', [ClientApiController::class, 'importStatus']);
    Route::delete('/clients/{id}', [ClientApiController::class, 'delete'])->name('clients.delete');

});
