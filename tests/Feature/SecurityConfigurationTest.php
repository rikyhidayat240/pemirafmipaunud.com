<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_and_strictly_configured(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_student_avatar_upload_uses_random_hash_filename(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('my_sensitive_name.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($admin)->post('/users', [
            'nim' => '2208561099',
            'nama' => 'Test Random Name Upload',
            'id_program_studi' => 1,
            'angkatan' => 2022,
            'avatar' => $file,
        ]);

        $createdUser = User::where('nim', '2208561099')->first();
        $this->assertNotNull($createdUser);
        $this->assertNotNull($createdUser->avatar);
        $this->assertStringNotContainsString('my_sensitive_name', $createdUser->avatar);
        Storage::disk('public')->assertExists($createdUser->avatar);
    }
}
