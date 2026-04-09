<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingStatsService
{
    private const CACHE_KEY = 'landing_stats_v1';

    /**
     * @return array{
     *     share_count: int,
     *     share_count_display: string,
     *     total_bytes: int,
     *     data_volume_display: string,
     *     unlock_count: int,
     *     unlock_count_display: string,
     * }
     */
    public function summary(): array
    {
        $ttl = (int) config('seo.landing_stats.cache_seconds', 60);
        if ($ttl <= 0) {
            return $this->compute();
        }

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => $this->compute());
    }

    /**
     * @return array<string, int|string>
     */
    private function compute(): array
    {
        $row = DB::table('shares')
            ->selectRaw('COUNT(*) as share_count')
            ->selectRaw('COALESCE(SUM(size_bytes), 0) as total_bytes')
            ->selectRaw('COALESCE(SUM(download_count), 0) as unlock_count')
            ->first();

        $extraShares = max(0, (int) config('seo.landing_stats.extra_shares', 0));
        $extraBytes = max(0, (int) config('seo.landing_stats.extra_bytes', 0));
        $extraUnlocks = max(0, (int) config('seo.landing_stats.extra_unlocks', 0));

        $shareCount = (int) $row->share_count + $extraShares;
        $totalBytes = (int) $row->total_bytes + $extraBytes;
        $unlockCount = (int) $row->unlock_count + $extraUnlocks;

        return [
            'share_count' => $shareCount,
            'share_count_display' => $this->formatInteger($shareCount),
            'total_bytes' => $totalBytes,
            'data_volume_display' => $this->formatBytes($totalBytes),
            'unlock_count' => $unlockCount,
            'unlock_count_display' => $this->formatInteger($unlockCount),
        ];
    }

    private function formatInteger(int $n): string
    {
        return number_format(max(0, $n));
    }

    private function formatBytes(int $bytes): string
    {
        $bytes = max(0, $bytes);
        if ($bytes >= 1_073_741_824) {
            return rtrim(rtrim(number_format($bytes / 1_073_741_824, 1), '0'), '.').' GB';
        }
        if ($bytes >= 1_048_576) {
            return rtrim(rtrim(number_format($bytes / 1_048_576, 1), '0'), '.').' MB';
        }
        if ($bytes >= 1024) {
            return number_format((int) floor($bytes / 1024)).' KB';
        }

        return $bytes === 0 ? '0 B' : $bytes.' B';
    }
}
