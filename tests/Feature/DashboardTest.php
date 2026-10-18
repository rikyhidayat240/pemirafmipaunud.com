<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('admin can view events from different years on events index', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $this->actingAs($admin);

    $eventPast = \App\Models\Kegiatan::create([
        'nama'          => 'Pemira 2025 Past Event',
        'ruang_lingkup' => 'fakultas',
        'tahun'         => 2025,
        'waktu_mulai'   => '2025-01-01 08:00:00',
        'waktu_selesai' => '2025-01-01 16:00:00',
        'foto'          => 'foto-kegiatan/dummy.png',
    ]);

    $eventCurrent = \App\Models\Kegiatan::create([
        'nama'          => 'Pemira 2026 Current Event',
        'ruang_lingkup' => 'fakultas',
        'tahun'         => 2026,
        'waktu_mulai'   => '2026-01-01 08:00:00',
        'waktu_selesai' => '2026-01-01 16:00:00',
        'foto'          => 'foto-kegiatan/dummy.png',
    ]);

    $response = $this->get(route('events.index'));
    $response->assertStatus(200);

    $kegiatanList = $response->viewData('page')['props']['kegiatan'];
    $ids = collect($kegiatanList)->pluck('id')->all();

    expect($ids)->toContain($eventPast->id);
    expect($ids)->toContain($eventCurrent->id);
    // Verified ordered by tahun desc
    expect($kegiatanList[0]['id'])->toBe($eventCurrent->id);
});

test('admin can view candidates index with events across years', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $this->actingAs($admin);

    $eventPast = \App\Models\Kegiatan::create([
        'nama'          => 'Pemira 2025 Past Event For Candidates',
        'ruang_lingkup' => 'fakultas',
        'tahun'         => 2025,
        'waktu_mulai'   => '2025-01-01 08:00:00',
        'waktu_selesai' => '2025-01-01 16:00:00',
        'foto'          => 'foto-kegiatan/dummy.png',
    ]);

    $response = $this->get(route('candidates.index'));
    $response->assertStatus(200);

    $kegiatanDropdown = $response->viewData('page')['props']['kegiatan'];
    $ids = collect($kegiatanDropdown)->pluck('id')->all();

    expect($ids)->toContain($eventPast->id);
});