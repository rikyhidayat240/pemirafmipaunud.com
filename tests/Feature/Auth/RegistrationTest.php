<?php

test('registration screen is disabled and does not display registration form', function () {
    $response = $this->get('/register');

    if ($response->status() === 200) {
        $response->assertInertia(fn ($page) => $page->component('auth/Login'));
    } else {
        expect(in_array($response->status(), [302, 404]))->toBeTrue();
    }
});

test('registration is disabled and new users can not register', function () {
    $response = $this->post('/register', [
        'nim' => '123456789',
        'nama' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    expect(in_array($response->status(), [404, 405]))->toBeTrue();
});