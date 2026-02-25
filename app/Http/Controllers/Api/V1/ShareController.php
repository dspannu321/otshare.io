<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Share;
use App\Services\PickupCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ShareController extends Controller
{
    public function __construct(
        protected PickupCodeService $pickupCode
    ) {}

    /**
     * Create a new share (no file yet). Returns share id and pickup code.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expires_in_minutes' => 'sometimes|integer|min:1|max:10080', // max 7 days
            'max_downloads' => 'sometimes|integer|min:1|max:10',
        ]);

        $expiresAt = now()->addMinutes(
            $validated['expires_in_minutes'] ?? config('otshare.default_expiry_minutes')
        );
        $maxDownloads = $validated['max_downloads'] ?? config('otshare.default_max_downloads');

        $pickupCode = $this->pickupCode->generate();
        $shortId = $this->pickupCode->shortIdFromCode($pickupCode);

        $share = Share::create([
            'short_id' => $shortId,
            'pickup_hash' => $this->pickupCode->hash($pickupCode),
            'expires_at' => $expiresAt,
            'max_downloads' => $maxDownloads,
            'kdf' => null,
            'crypto_meta' => null,
        ]);

        return response()->json([
            'id' => $share->id,
            'pickup_code' => $pickupCode,
            'expires_at' => $share->expires_at->toIso8601String(),
            'upload_url' => route('api.shares.upload', ['share' => $share->id]),
        ], 201);
    }

    /**
     * Upload encrypted file and metadata for an existing share.
     * Accepts multipart/form-data: ciphertext (file), crypto_meta (JSON string), kdf (JSON string), etc.
     */
    public function upload(Request $request, Share $share): JsonResponse
    {
        if ($share->object_key !== null) {
            throw ValidationException::withMessages(['share' => ['Share already has a file.']]);
        }

        $maxSize = config('otshare.max_file_size');

        // Multipart form sends crypto_meta/kdf as JSON strings; decode before validate
        $cryptoMeta = $request->input('crypto_meta');
        if (is_string($cryptoMeta)) {
            $decoded = json_decode($cryptoMeta, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages(['crypto_meta' => ['crypto_meta must be valid JSON.']]);
            }
            $request->merge(['crypto_meta' => $decoded]);
        }
        $kdfInput = $request->input('kdf');
        if (is_string($kdfInput)) {
            $decoded = json_decode($kdfInput, true);
            $request->merge(['kdf' => is_array($decoded) ? $decoded : null]);
        }

        $validated = $request->validate([
            'ciphertext' => 'required',
            'crypto_meta' => 'required|array',
            'kdf' => 'sometimes|nullable|array',
            'original_name' => 'sometimes|nullable|string|max:255|regex:/^[\w\s.-]+$/',
            'mime' => 'sometimes|nullable|string|max:128|regex:/^[a-z0-9+\/-]+(\.[a-z0-9+\/-]+)*$/i',
        ]);

        $maxMeta = config('otshare.max_crypto_meta_size', 4096);
        if (strlen(json_encode($validated['crypto_meta'])) > $maxMeta) {
            throw ValidationException::withMessages(['crypto_meta' => ['crypto_meta exceeds maximum size.']]);
        }

        $disk = config('otshare.storage_disk');
        $path = 'shares/'.$share->id;

        if ($request->hasFile('ciphertext')) {
            $file = $request->file('ciphertext');
            if ($file->getSize() > $maxSize) {
                throw ValidationException::withMessages(['ciphertext' => ['File exceeds maximum size.']]);
            }
            $stored = Storage::disk($disk)->putFile($path, $file);
            $size = $file->getSize();
        } else {
            $data = $request->input('ciphertext');
            if (is_string($data) && preg_match('/^[A-Za-z0-9+\/=]+\s*$/', $data)) {
                $binary = base64_decode($data, true);
                if ($binary === false || strlen($binary) > $maxSize) {
                    throw ValidationException::withMessages(['ciphertext' => ['Invalid base64 or exceeds max size.']]);
                }
                $stored = $path.'/'.str_replace('-', '', $share->id).'.bin';
                Storage::disk($disk)->put($stored, $binary);
                $size = strlen($binary);
            } else {
                throw ValidationException::withMessages(['ciphertext' => ['Ciphertext must be a file or base64 string.']]);
            }
        }

        $share->update([
            'object_key' => $stored,
            'crypto_meta' => $validated['crypto_meta'],
            'kdf' => $validated['kdf'] ?? null,
            'original_name' => $validated['original_name'] ?? null,
            'mime' => $validated['mime'] ?? null,
            'size_bytes' => $size,
        ]);

        return response()->json([
            'id' => $share->id,
            'expires_at' => $share->expires_at->toIso8601String(),
        ]);
    }
}
