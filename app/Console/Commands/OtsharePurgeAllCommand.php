<?php

namespace App\Console\Commands;

use App\Services\SharePurgeService;
use Illuminate\Console\Command;

class OtsharePurgeAllCommand extends Command
{
    protected $signature = 'otshare:purge-all
                            {--force : Do not ask for confirmation}';

    protected $description = 'Delete all shares, download tokens (cascade), and remove share files from the configured storage disk.';

    public function handle(SharePurgeService $purge): int
    {
        if (! $this->option('force')) {
            $this->components->warn('This removes every share, all pickup codes become invalid, and deletes blobs from storage.');
            if (! $this->confirm('Purge everything?', false)) {
                $this->components->info('Aborted.');

                return self::FAILURE;
            }
        }

        $result = $purge->purgeAll();

        $this->components->info("Deleted {$result['shares_deleted']} share(s), removed {$result['files_deleted']} file(s) from disk.");
        if ($result['file_errors'] !== []) {
            $this->components->error('Some files could not be deleted:');
            foreach ($result['file_errors'] as $err) {
                $this->line('  '.$err);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
