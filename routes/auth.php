<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BerandaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [BerandaController::class, 'guest'])
        ->name('home');

    // Self-registration is DISABLED — accounts are created by Admin only.
    // Route::get('register', [RegisteredUserController::class, 'create'])
    //     ->name('register');
    // Route::post('check-student', [RegisteredUserController::class, 'checkStudent'])
    //     ->name('check.student');
    // Route::post('register', [RegisteredUserController::class, 'store']);

    // Keep the route name 'register' pointing to login to avoid broken links
    Route::get('register', [AuthenticatedSessionController::class, 'create'])->name('register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Forgot/Reset password via EMAIL is DISABLED to avoid hitting the 500/day email limit.
    // Students can still change their password from their Profile page (no email needed).
    // Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    //     ->name('password.request');
    // Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    //     ->name('password.email');
    // Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    //     ->name('password.reset');
    // Route::post('reset-password', [NewPasswordController::class, 'store'])
    //     ->name('password.store');

    // Keep route names to avoid broken links in any views that reference them
    Route::get('forgot-password', fn() => redirect()->route('login'))->name('password.request');
    Route::get('reset-password/{token}', fn() => redirect()->route('login'))->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
