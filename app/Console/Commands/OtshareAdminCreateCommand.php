<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class OtshareAdminCreateCommand extends Command
{
    protected $signature = 'otshare:admin-create
                            {name : Label for this credential (e.g. ops or your name)}
                            {password : Dashboard password (stored hashed; use a strong value)}
                            {--length=48 : Secret key length in bytes (encoded as base64url)}';

    protected $description = 'Create an admin key + dashboard password for the hidden /admin flow (key is shown once; put it in OTSHARE_ADMIN_SECRET).';

    public function handle(): int
    {
        $length = max(32, min(64, (int) $this->option('length')));
        $plain = rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');

        Admin::create([
            'name' => $this->argument('name'),
            'key_hash' => Hash::make($plain),
            'password_hash' => Hash::make($this->argument('password')),
        ]);

        $this->newLine();
        $this->components->info('Admin credential created. Store the key and password safely — the key is shown only once.');
        $this->line('');
        $this->line('  <fg=cyan>1.</> Add the key to .env:');
        $this->line('     <fg=yellow>OTSHARE_ADMIN_SECRET='.$plain.'</>');
        $this->newLine();
        $this->line('  <fg=cyan>2.</> Open the dashboard (after IP allowlist):');
        $this->line('     '.config('app.url').'/admin/login?key=<paste key>');
        $this->newLine();
        $this->line('  <fg=yellow>KEY (copy now):</>');
        $this->line('  '.$plain);
        $this->newLine();
        $this->components->warn('Set OTSHARE_ADMIN_ALLOWED_IPS in .env (comma-separated) so only your IPs can reach /admin.');
        $this->line('  Example: OTSHARE_ADMIN_ALLOWED_IPS=127.0.0.1,::1');
        $this->newLine();
        $this->line('  TOTP is configured in the browser on first visit (no separate MFA command).');
        $this->newLine();

        return self::SUCCESS;
    }
}
