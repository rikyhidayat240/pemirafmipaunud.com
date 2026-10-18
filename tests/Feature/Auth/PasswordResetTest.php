<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('reset password link screen is disabled and redirects or returns 404', function () {
    $response = $this->get('/forgot-password');

    if ($response->status() === 302) {
        $response->assertRedirect(route('login'));
    } else {
        $response->assertNotFound();
    }
});

test('reset password link can not be requested because password reset is disabled', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertNothingSent();
    expect(in_array($response->status(), [404, 405]))->toBeTrue();
});

test('reset password screen is disabled and redirects or returns 404', function () {
    $response = $this->get('/reset-password/sample-token');

    if ($response->status() === 302) {
        $response->assertRedirect(route('login'));
    } else {
        $response->assertNotFound();
    }
});

test('password can not be reset with token because password reset is disabled', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'token' => 'sample-token',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Notification::assertNothingSent();
    expect(in_array($response->status(), [404, 405]))->toBeTrue();
});