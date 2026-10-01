<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Wobqqq\Aegis\Http\Controllers\OverviewController;
use Wobqqq\Aegis\Http\Controllers\ScanController;
use Wobqqq\Aegis\Http\Controllers\SettingsController;

Route::get('/overview', [OverviewController::class, 'show'])->name('nova.aegis.overview');
Route::post('/audit', [OverviewController::class, 'audit'])->middleware('throttle:6,1')->name('nova.aegis.audit');

Route::get('/settings', [SettingsController::class, 'index'])->name('nova.aegis.settings');
Route::put('/settings/{section}', [SettingsController::class, 'update'])
    ->where('section', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('nova.aegis.settings.update');

Route::middleware('throttle:20,1')->group(static function (): void {
    Route::post('/scans/sensitive-files', [ScanController::class, 'sensitiveFiles'])->name('nova.aegis.scans.sensitive-files');
    Route::post('/scans/tcp-ports', [ScanController::class, 'tcpPorts'])->name('nova.aegis.scans.tcp-ports');
    Route::post('/scans/tls-certificates', [ScanController::class, 'tlsCertificates'])->name('nova.aegis.scans.tls-certificates');
});
