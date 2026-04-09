<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Services\PickupCodeService;
use App\Services\ShareTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RedeemController extends Controller
{
    public function __construct(
        protected PickupCodeService $pickupCode,
        protected ShareTokenService $tokenService
    ) {}

    /**
     * Redeem pickup code; returns one-time download token and crypto metadata.
     */
    public function redeem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9]{1,10}-[0-9]{1,10}$/'],
        ]);

        $pickupCode = preg_replace('/\s+/', '', $validated['pickup_code']);
        $shortId = $this->pickupCode->shortIdFromCode($pickupCode);

        if (! $shortId) {
            $this->failRedeem();
        }

        $share = Share::where('short_id', $shortId)->first();

        if (! $share || ! $this->pickupCode->verify($pickupCode, $share->pickup_hash)) {
            $this->recordFailedAttempt($share ?? null, $shortId);
            $this->failRedeem();
        }

        if ($share->isLocked()) {
            throw ValidationException::withMessages([
                'pickup_code' => ['Share is temporarily locked. Try again later.'],
            ]);
        }

        if ($share->isExpired()) {
            throw ValidationException::withMessages([
                'pickup_code' => ['Share has expired.'],
            ]);
        }

        if ($share->isConsumed()) {
            throw ValidationException::withMessages([
                'pickup_code' => ['Share has already been downloaded.'],
            ]);
        }

        if ($share->object_key === null) {
            $msg = $share->isPasscodeExhausted()
                ? 'This share was permanently deleted after too many wrong passcode attempts.'
                : 'No file has been uploaded for this share.';
            throw ValidationException::withMessages([
                'pickup_code' => [$msg],
            ]);
        }

        $share->update(['failed_attempts' => 0]);

        ['token' => $tokenModel, 'plain_token' => $plainToken] = $this->tokenService->createForShare($share);

        return response()->json([
            'download_token' => $plainToken,
            /** Short-lived session for GET /download + POST /download/confirm (see config otshare.token_expiry_minutes). */
            'expires_at' => $tokenModel->expires_at->toIso8601String(),
            /** When the share itself expires (pickup code / file availability). */
            'share_expires_at' => $share->expires_at->toIso8601String(),
            'crypto_meta' => $share->crypto_meta,
            'kdf' => $share->kdf,
            'original_name' => $share->original_name,
            'mime' => $share->mime,
            'size_bytes' => $share->size_bytes,
        ]);
    }

    protected function failRedeem(): void
    {
        throw ValidationException::withMessages([
            'pickup_code' => ['Invalid or expired pickup code.'],
        ]);
    }

    protected function recordFailedAttempt(?Share $share, string $shortId): void
    {
        if ($share) {
            $share->increment('failed_attempts');
            $lockAfter = config('otshare.redeem_lock_after_attempts');
            if ($share->failed_attempts >= $lockAfter) {
                $share->update([
                    'locked_until' => now()->addMinutes(config('otshare.redeem_lock_minutes')),
                ]);
            }
        }
    }
}
