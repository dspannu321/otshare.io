<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ShareTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        protected ShareTokenService $tokenService
    ) {}

    /**
     * Stream ciphertext for download. Does NOT consume the token; client must call confirm after decrypt.
     */
    public function download(Request $request): StreamedResponse|JsonResponse
    {
        $token = $request->query('token');

        if (! $token || ! is_string($token)) {
            return response()->json(['message' => 'Missing or invalid token.'], 400);
        }

        $tokenModel = $this->tokenService->findValidByPlainToken($token);

        if (! $tokenModel) {
            return response()->json(['message' => 'Invalid or expired download token.'], 404);
        }

        $share = $tokenModel->share;

        if (! $share->canBeDownloaded()) {
            return response()->json(['message' => 'Share is no longer available.'], 410);
        }

        if ($share->isPasscodeExhausted()) {
            return response()->json(['message' => 'No attempts left. The file has been permanently deleted.'], 410);
        }

        $path = $share->object_key;
        $disk = config('otshare.storage_disk');

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $filename = $share->original_name ?: 'download.bin';
        $mime = $share->mime ?: 'application/octet-stream';

        return Storage::disk($disk)->download($path, $filename, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Confirm decrypt success or failure. On success: mark token used, increment download_count.
     * On failure: increment passcode_failed_attempts; after max attempts, delete file and expire share.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'success' => 'required|boolean',
        ]);

        $tokenModel = $this->tokenService->findByPlainToken($validated['token']);

        if (! $tokenModel) {
            return response()->json(['message' => 'Invalid or expired download token.'], 404);
        }

        $share = $tokenModel->share;
        $maxAttempts = config('otshare.max_passcode_attempts', 3);

        if ($validated['success']) {
            if ($tokenModel->used_at !== null) {
                return response()->json(['message' => 'This download link has already been used.'], 410);
            }
            if (! $share->canBeDownloaded()) {
                return response()->json(['message' => 'Share is no longer available.'], 410);
            }
            $tokenModel->update(['used_at' => now()]);
            $share->increment('download_count');

            return response()->json(['message' => 'Download confirmed.']);
        }

        $share->increment('passcode_failed_attempts');
        $attemptsLeft = max(0, $maxAttempts - $share->passcode_failed_attempts);

        if ($share->passcode_failed_attempts >= $maxAttempts) {
            $disk = config('otshare.storage_disk');
            if ($share->object_key && Storage::disk($disk)->exists($share->object_key)) {
                Storage::disk($disk)->delete($share->object_key);
            }
            $share->update([
                'object_key' => null,
                'expires_at' => now(),
            ]);

            return response()->json([
                'message' => 'No attempts left. The file has been permanently deleted.',
                'attempts_left' => 0,
                'expired' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Wrong passcode.',
            'attempts_left' => $attemptsLeft,
            'expired' => false,
        ], 200);
    }
}
