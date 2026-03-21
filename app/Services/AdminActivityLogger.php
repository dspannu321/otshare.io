<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminAccessLog;
use Illuminate\Http\Request;

class AdminActivityLogger
{
    public static function record(Request $request, ?string $adminId, string $event): void
    {
        $adminName = null;
        if ($adminId !== null) {
            $adminName = Admin::query()->whereKey($adminId)->value('name');
        }

        AdminAccessLog::query()->create([
            'admin_id' => $adminId,
            'admin_name' => is_string($adminName) ? $adminName : null,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
        ]);
    }
}
