<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\KandidatController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\SuratSuaraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(BerandaController::class)->group(function () {
        Route::get('dashboard', 'index')->name('dashboard');
        Route::get('terms', 'terms')->name('terms');
        Route::get('candidates/{slug}', 'candidates')->name('candidates');
        Route::get('result-bem', 'resultBem')->name('result-bem');
        Route::get('result-hima', 'resultHima')->name('result-hima');
    });

    Route::controller(SuratSuaraController::class)->middleware('throttle:15,1')->group(function () {
        Route::get('vote', 'show')->name('vote.show');
        Route::post('vote', 'store')->name('vote.store')->middleware('throttle:5,1'); // stricter on submit
    });

    Route::middleware('admin')->group(function () {
        Route::match(['post', 'delete'], 'users/bulk-delete', [MahasiswaController::class, 'bulkDestroy'])
            ->name('users.bulk-destroy');

        Route::resource('users', MahasiswaController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    
        Route::post('users/sync-data/{year}', [MahasiswaController::class, 'syncMahasiswa'])
            ->name('users.sync-data');

        Route::post('users/send-bulk-credentials', [MahasiswaController::class, 'sendBulkCredentials'])
            ->name('users.send-bulk-credentials');

        Route::post('users/{nim}/send-credentials', [MahasiswaController::class, 'sendCredentials'])
            ->name('users.send-credentials');

    
        Route::resource('events', KegiatanController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::get('events/{id}/print', [KegiatanController::class, 'print'])
            ->name('events.print');
    
        Route::resource('candidates', KandidatController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        if (app()->environment() !== 'production') {
            Route::get('staging/proxy-diagnostic', function (\Illuminate\Http\Request $request) {
                if (app()->environment('production')) {
                    abort(404);
                }

                return response()->json([
                    'client_ip'         => $request->ip(),
                    'scheme'            => $request->getScheme(),
                    'is_secure'         => $request->isSecure(),
                    'remote_addr'       => $request->server('REMOTE_ADDR'),
                    'x_forwarded_for'   => $request->header('x-forwarded-for'),
                    'x_forwarded_proto' => $request->header('x-forwarded-proto'),
                    'x_forwarded_host'  => $request->header('x-forwarded-host'),
                    'x_forwarded_port'  => $request->header('x-forwarded-port'),
                ]);
            })->name('staging.proxy-diagnostic');
        }
    });
});
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
