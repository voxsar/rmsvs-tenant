<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\ScanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Guest routes
Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guest.show');

// Scanner routes
Route::get('/scan/{scanner}', [ScanController::class, 'scanPage'])->name('scanner.scan');
Route::post('/scan/{scanner}/process', [ScanController::class, 'processQrScan'])->name('scanner.process');

// Consumables routes
Route::get('/consumables/{guest}/{room}', [ScanController::class, 'consumablesPage'])->name('consumables.page');
Route::post('/consumables/request/{checkIn}', [ScanController::class, 'requestConsumable'])->name('consumables.request');
