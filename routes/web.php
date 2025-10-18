<?php

use App\Http\Controllers\GuestController;
use App\Http\Controllers\ScanController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

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
    $tenant = Tenant::current();

    if (! $tenant) {
        return redirect()->to('admin');
    }

    $currentTenant = $tenant;

    if (! $currentTenant) {
        return redirect()->to('super');
    }

    return $currentTenant->name === 'landlord'
        ? redirect()->to('super')
        : redirect()->to('admin');
});

Route::middleware('tenant')->group(function () {
    // Guest routes
    Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guest.show');

    // Scanner routes
    Route::get('/scan/{scanner}', [ScanController::class, 'scanPage'])->name('scanner.scan');
    Route::post('/scan/{scanner}/process', [ScanController::class, 'processQrScan'])->name('scanner.process');

    // QR Code routes
    Route::post('/generate-qr-code/{checkIn}', [ScanController::class, 'generateQrCode'])->name('generate.qr.code');

    // Consumables routes
    Route::get('/consumables/{guest}/{room}', [ScanController::class, 'consumablesPage'])->name('consumables.page');
    Route::post('/consumables/request/{checkIn}', [ScanController::class, 'requestConsumable'])->name('consumables.request');
});
