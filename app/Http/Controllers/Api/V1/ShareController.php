<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Services\PickupCodeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ShareController extends Controller
{
    public function __construct(
        protected PickupCodeService $pickupCode
    ) {}

    /**
     * Free tier: create a text share — UTF-8 body + expiry + max downloads (same lifecycle as file shares).
     */
    public function storeText(Request $request): JsonResponse
    {
        $maxBytes = max(1, (int) config('otshare.max_file_size'));

        $validated = $request->validate([
            'text' => ['required', 'string'],
            'expires_at' => ['required', 'date'],
            'max_downloads' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $text = $validated['text'];
        if (strlen($text) > $maxBytes) {
            throw ValidationException::withMessages([
                'text' => ['Text exceeds maximum size allowed.'],
            ]);
        }
        if ($text === '' || trim($text) === '') {
            throw ValidationException::withMessages([
                'text' => ['Text cannot be empty.'],
            ]);
        }

        $expiresAt = Carbon::parse($validated['expires_at']);
        $this->assertExpiryWindow($expiresAt);

        $pickupCode = $this->pickupCode->generate();
        $shortId = $this->pickupCode->shortIdFromCode($pickupCode);
        $disk = config('otshare.storage_disk');

        $share = DB::transaction(function () use ($expiresAt, $validated, $pickupCode, $shortId, $text, $disk) {
            $share = Share::create([
                'short_id' => $shortId,
                'pickup_hash' => $this->pickupCode->hash($pickupCode),
                'expires_at' => $expiresAt,
                'max_downloads' => $validated['max_downloads'],
                'kdf' => null,
                'crypto_meta' => null,
            ]);

            $relativePath = 'shares/'.$share->id.'/shared.txt';
            Storage::disk($disk)->put($relativePath, $text);

            $sizeBytes = strlen($text);

            $share->update([
                'object_key' => $relativePath,
                'crypto_meta' => [],
                'kdf' => null,
                'original_name' => 'shared.txt',
                'mime' => 'text/plain; charset=utf-8',
                'size_bytes' => $sizeBytes,
            ]);

            return $share->fresh();
        });

        return response()->json([
            'id' => $share->id,
            'pickup_code' => $pickupCode,
            'expires_at' => $share->expires_at->toIso8601String(),
            'size_bytes' => $share->size_bytes,
            'original_name' => $share->original_name,
        ], 201);
    }

    /**
     * Free tier: create a share in one step — multipart file + expiry + max downloads (plaintext storage).
     */
    public function store(Request $request): JsonResponse
    {
        $maxKb = max(1, (int) ceil(config('otshare.max_file_size') / 1024));

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb],
            'expires_at' => ['required', 'date'],
            'max_downloads' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $expiresAt = Carbon::parse($validated['expires_at']);
        $this->assertExpiryWindow($expiresAt);

        $file = $request->file('file');
        $originalName = $this->sanitizeOriginalFilename($file->getClientOriginalName());
        $mime = $file->getClientMimeType() ?: $file->getMimeType();
        if (is_string($mime) && $mime !== '' && ! preg_match('/^[a-z0-9+\/-]+(\.[a-z0-9+\/-]+)*$/i', $mime)) {
            $mime = 'application/octet-stream';
        }

        $pickupCode = $this->pickupCode->generate();
        $shortId = $this->pickupCode->shortIdFromCode($pickupCode);
        $disk = config('otshare.storage_disk');

        $share = DB::transaction(function () use ($expiresAt, $validated, $pickupCode, $shortId, $file, $originalName, $mime, $disk) {
            $share = Share::create([
                'short_id' => $shortId,
                'pickup_hash' => $this->pickupCode->hash($pickupCode),
                'expires_at' => $expiresAt,
                'max_downloads' => $validated['max_downloads'],
                'kdf' => null,
                'crypto_meta' => null,
            ]);

            $path = 'shares/'.$share->id;
            $stored = Storage::disk($disk)->putFile($path, $file);

            $share->update([
                'object_key' => $stored,
                'crypto_meta' => [],
                'kdf' => null,
                'original_name' => $originalName,
                'mime' => $mime ?: null,
                'size_bytes' => $file->getSize(),
            ]);

            return $share->fresh();
        });

        return response()->json([
            'id' => $share->id,
            'pickup_code' => $pickupCode,
            'expires_at' => $share->expires_at->toIso8601String(),
            'size_bytes' => $share->size_bytes,
            'original_name' => $share->original_name,
        ], 201);
    }

    private function assertExpiryWindow(Carbon $expiresAt): void
    {
        $now = now();
        if ($expiresAt->lessThanOrEqualTo($now->copy()->addMinute())) {
            throw ValidationException::withMessages([
                'expires_at' => ['Expiry must be at least 1 minute from now.'],
            ]);
        }
        if ($expiresAt->greaterThan($now->copy()->addDays(7))) {
            throw ValidationException::withMessages([
                'expires_at' => ['Expiry cannot be more than 7 days from now.'],
            ]);
        }
    }

    private function sanitizeOriginalFilename(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }
        $base = basename($name);
        $safe = preg_replace('/[\x00-\x1f\\\\\/:*?"<>|]/u', '_', $base);
        $safe = preg_replace('/[^\p{L}\p{N}\s._\-()\[\],+\'~]/u', '_', $safe);
        $safe = trim(preg_replace('/_+/', '_', $safe), ' _');
        if ($safe === '') {
            return null;
        }

        return strlen($safe) > 255 ? substr($safe, 0, 255) : $safe;
    }
}
