<?php

namespace App\Services;

use App\Models\Share;
use App\Models\ShareToken;
use Illuminate\Support\Str;

class ShareTokenService
{
    /**
     * @return array{token: ShareToken, plain_token: string}
     */
    public function createForShare(Share $share): array
    {
        $plainToken = Str::random(64);
        $expiresAt = now()->addMinutes(config('otshare.token_expiry_minutes'));

        $token = $share->tokens()->create([
            'token_hash' => $this->hashToken($plainToken),
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plain_token' => $plainToken];
    }

    /**
     * HMAC-based token hash so tokens are bound to app key (no offline guessing).
     */
    public function hashToken(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, config('app.key'));
    }

    public function findValidByPlainToken(string $plainToken): ?ShareToken
    {
        $hash = $this->hashToken($plainToken);

        return ShareToken::where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();
    }

    /** Find token by plain token (any use state) to get share for confirm. */
    public function findByPlainToken(string $plainToken): ?ShareToken
    {
        $hash = $this->hashToken($plainToken);

        return ShareToken::where('token_hash', $hash)->where('expires_at', '>', now())->first();
    }
}
