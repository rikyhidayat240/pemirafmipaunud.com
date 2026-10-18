<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Str;

class AuditLogService
{
    /**
     * Forbidden keys that must NEVER be recorded in audit log metadata.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'plain_password',
        'plainpassword',
        'token',
        'remember_token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'sheet_data',
        'import_data',
        'file_content',
        'rows',
        'id_kandidat_bem',
        'id_kandidat_hima',
        'pilihan',
        'candidate_choice',
        'vote',
        'ballot',
    ];

    /**
     * Record an admin action to the audit log.
     */
    public static function log(
        string $aksi,
        ?string $targetType = null,
        ?string $targetId = null,
        array $metadata = []
    ): AuditLog {
        $admin = auth('web')->user();
        $adminNim = $admin ? $admin->nim : null;

        $request = request();
        $ip = $request ? $request->ip() : null;
        $userAgent = $request ? Str::limit((string) $request->userAgent(), 250, '') : null;

        $safeMetadata = self::sanitizeMetadata($metadata);

        return AuditLog::create([
            'admin_nim'   => $adminNim,
            'aksi'        => $aksi,
            'target_type' => $targetType,
            'target_id'   => $targetId ? (string) $targetId : null,
            'ip'          => $ip,
            'user_agent'  => $userAgent,
            'metadata'    => !empty($safeMetadata) ? $safeMetadata : null,
        ]);
    }

    /**
     * Sanitize metadata to strip any sensitive keys, passwords, tokens, or vote choices.
     */
    public static function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Reject sensitive keys
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    continue 2;
                }
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeMetadata($value);
            } elseif (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
