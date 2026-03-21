<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only authentication audit for the operations dashboard.
 * Never deleted by share purge or admin UI.
 */
class AdminAccessLog extends Model
{
    public $timestamps = true;

    public const EVENT_PASSWORD_SUCCESS = 'password_success';

    public const EVENT_PASSWORD_FAILURE = 'password_failure';

    public const EVENT_TOTP_SUCCESS = 'totp_success';

    public const EVENT_TOTP_FAILURE = 'totp_failure';

    public const EVENT_TOTP_SETUP_COMPLETE = 'totp_setup_complete';

    public const EVENT_LOGOUT = 'logout';

    protected $fillable = [
        'admin_id',
        'admin_name',
        'event',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
