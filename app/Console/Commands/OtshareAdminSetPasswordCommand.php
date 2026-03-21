<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class OtshareAdminSetPasswordCommand extends Command
{
    protected $signature = 'otshare:admin-set-password
                            {name : Admin label as created with otshare:admin-create}
                            {password : New dashboard password (stored hashed)}';

    protected $description = 'Set or replace the dashboard password for an existing admin (by name).';

    public function handle(): int
    {
        $name = $this->argument('name');
        $admin = Admin::where('name', $name)->first();
        if (! $admin) {
            $this->components->error("No admin named \"{$name}\".");

            return self::FAILURE;
        }

        $admin->password_hash = Hash::make($this->argument('password'));
        $admin->save();

        $this->components->info("Dashboard password updated for \"{$name}\".");

        return self::SUCCESS;
    }
}
