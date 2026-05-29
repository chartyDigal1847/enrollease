<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SsoToken — single-use SSO tokens issued by the DEORIS portal.
 * Exact copy of EntryEase's SsoToken model.
 */
class SsoToken extends Model
{
    protected $table      = 'sso_tokens';
    protected $primaryKey = 'token';
    public    $incrementing = false;
    public    $keyType      = 'string';

    protected $fillable = [
        'token',
        'sso_id',
        'sso_role',
        'sso_name',
        'sso_email',
        'portal_signature',
        'portal_issued_at',
        'exchanged_at',
    ];

    protected $casts = [
        'portal_issued_at' => 'datetime',
        'exchanged_at'     => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public $timestamps = true;

    public function isValid(): bool
    {
        if ($this->exchanged_at !== null) {
            return false;
        }

        if ($this->portal_issued_at && now()->diffInMinutes($this->portal_issued_at) > 5) {
            return false;
        }

        if (empty($this->portal_signature)) {
            return false;
        }

        return true;
    }

    public function markExchanged(): void
    {
        $this->update(['exchanged_at' => now()]);
    }

    public function validateSignature(string $portalPublicKey): bool
    {
        try {
            $payload = json_encode([
                'sso_id'           => $this->sso_id,
                'sso_role'         => $this->sso_role,
                'sso_name'         => $this->sso_name,
                'sso_email'        => $this->sso_email,
                'portal_issued_at' => $this->portal_issued_at->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return openssl_verify(
                $payload,
                base64_decode($this->portal_signature),
                $portalPublicKey,
                OPENSSL_ALGO_SHA256
            ) === 1;
        } catch (\Exception $e) {
            \Log::warning('[EnrollEase][SSO] Signature validation error', [
                'token' => substr($this->token, 0, 8),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function findValid(string $tokenString): ?self
    {
        $token = self::where('token', $tokenString)->first();
        return ($token && $token->isValid()) ? $token : null;
    }

    public static function cleanupExpired(): int
    {
        return self::where('portal_issued_at', '<', now()->subMinutes(10))->delete();
    }
}
