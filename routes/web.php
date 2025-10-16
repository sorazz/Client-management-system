<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\ClientController;

Route::get('/',fn()=>redirect()->route('clients.index'));
Route::get('/clients',[ClientController::class,'index'])->name('clients.index');
Route::get('/clients/upload',[ClientController::class,'upload'])->name('clients.upload');
Route::get('/clients/importStatus',[ClientController::class,'importStatus'])->name('clients.importStatus');
Route::post('/clients/import',[ClientController::class,'import'])->name('clients.import');
Route::get('/clients/export',[ClientController::class,'export'])->name('clients.export');
