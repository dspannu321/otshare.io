<?php

namespace App\Support\Admin;

use App\Models\Admin;

/**
 * Resolved admin after admin key verification (must match a row in {@see Admin}).
 */
final class AdminCredential
{
    public function __construct(
        public readonly string $adminId,
        public readonly string $fingerprint,
        public readonly ?string $totpSecretBase32,
    ) {}

    public static function fromAdmin(Admin $admin, string $keyFingerprint): self
    {
        return new self(
            $admin->id,
            $keyFingerprint,
            is_string($admin->totp_secret) && $admin->totp_secret !== '' ? $admin->totp_secret : null,
        );
    }

    public function totpIsConfigured(): bool
    {
        return $this->totpSecretBase32 !== null && $this->totpSecretBase32 !== '';
    }
}
