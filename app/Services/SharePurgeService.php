<?php

namespace App\Services;

use App\Models\Share;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SharePurgeService
{
    /** Phrase required in admin UI (and documented for operators). */
    public const CONFIRMATION_PHRASE = 'DELETE ALL SHARES';

    /**
     * Deletes shares, tokens, and share files only.
     * Does not touch {@see \App\Models\AdminAccessLog} or other admin tables.
     *
     * Delete every share row, all share_tokens (DB cascade), and stored files for each share.
     *
     * @return array{shares_deleted: int, files_deleted: int, file_errors: list<string>}
     */
    public function purgeAll(): array
    {
        $diskName = config('otshare.storage_disk', 'local');
        $disk = Storage::disk($diskName);
        $filesDeleted = 0;
        $fileErrors = [];
        $sharesDeleted = 0;

        DB::transaction(function () use ($disk, &$filesDeleted, &$fileErrors, &$sharesDeleted) {
            $sharesDeleted = Share::count();

            Share::query()->orderBy('id')->chunkById(100, function ($shares) use ($disk, &$filesDeleted, &$fileErrors) {
                foreach ($shares as $share) {
                    $key = $share->object_key;
                    if (! is_string($key) || $key === '') {
                        continue;
                    }
                    try {
                        if ($disk->exists($key)) {
                            $disk->delete($key);
                            $filesDeleted++;
                        }
                    } catch (\Throwable $e) {
                        $fileErrors[] = $share->id.': '.$e->getMessage();
                        Log::warning('otshare.purge.file_delete_failed', [
                            'share_id' => $share->id,
                            'object_key' => $key,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            Share::query()->delete();
        });

        return [
            'shares_deleted' => $sharesDeleted,
            'files_deleted' => $filesDeleted,
            'file_errors' => $fileErrors,
        ];
    }
}
