<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->get('/settings/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->put('/settings/profile', [
            'nim' => $user->nim,
            'nama' => 'Test User',
            'email' => 'test@student.unud.ac.id',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/verify-email');

    $user->refresh();

    expect($user->nama)->toBe('Test User');
    expect($user->email)->toBe('test@student.unud.ac.id');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create(['email' => 'test@student.unud.ac.id', 'email_verified_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->from('/settings/profile')
        ->put('/settings/profile', [
            'nim' => $user->nim,
            'nama' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('self account deletion is disabled and returns 404 or 405', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this
        ->actingAs($user)
        ->delete('/settings/profile', [
            'password' => 'password',
        ]);

    expect(in_array($response->getStatusCode(), [404, 405]))->toBeTrue();
    expect($user->fresh())->not->toBeNull();
});