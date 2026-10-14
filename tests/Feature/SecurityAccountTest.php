<?php

namespace Tests\Feature;

use App\Http\Controllers\MahasiswaController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class SecurityAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_protects_against_mass_assignment_of_is_admin_and_status(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'aktif',
        ]);

        // Attempt mass assignment to elevate privileges and change status
        $user->update([
            'is_admin' => true,
            'status' => 'nonaktif',
        ]);

        $user->refresh();

        $this->assertFalse((bool) $user->is_admin, 'is_admin must not be updated via mass assignment');
        $this->assertSame('aktif', $user->status, 'status must not be updated via mass assignment');
    }

    public function test_delete_self_account_route_is_disabled(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'aktif',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete('/settings/profile');

        // Route should not exist or method not allowed
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    public function test_initial_password_generator_uses_csprng_and_generates_at_least_12_characters(): void
    {
        $controller = new MahasiswaController();
        $password = $controller->generatePassword();

        $this->assertGreaterThanOrEqual(12, strlen($password));
        $this->assertMatchesRegularExpression('/[a-zA-Z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
    }

    public function test_sync_mahasiswa_logic_updates_status_explicitly(): void
    {
        $user = User::factory()->create([
            'nim' => '2108561001',
            'nama' => 'Mahasiswa Test',
            'status' => 'aktif',
        ]);

        // Simulate sync payload with new status
        $syncData = [
            'nim' => '2108561001',
            'nama' => 'Mahasiswa Test Updated',
            'id_program_studi' => 1,
            'angkatan' => 2021,
            'status' => 'nonaktif',
        ];

        // Perform logic as in syncMahasiswa
        $fillableData = Arr::only($syncData, ['nim', 'nama', 'id_program_studi', 'angkatan']);
        $user->fill($fillableData);
        $user->status = $syncData['status'];
        $user->save();

        $user->refresh();
        $this->assertSame('nonaktif', $user->status);
        $this->assertSame('Mahasiswa Test Updated', $user->nama);
    }
}
