<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Share extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'short_id',
        'pickup_hash',
        'object_key',
        'expires_at',
        'max_downloads',
        'download_count',
        'failed_attempts',
        'passcode_failed_attempts',
        'locked_until',
        'kdf',
        'crypto_meta',
        'original_name',
        'mime',
        'size_bytes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'locked_until' => 'datetime',
        'kdf' => 'array',
        'crypto_meta' => 'array',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(ShareToken::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function isConsumed(): bool
    {
        return $this->download_count >= $this->max_downloads;
    }

    public function canBeDownloaded(): bool
    {
        return ! $this->isExpired() && ! $this->isLocked() && ! $this->isConsumed()
            && $this->object_key !== null;
    }

    public function isPasscodeExhausted(): bool
    {
        return $this->passcode_failed_attempts >= config('otshare.max_passcode_attempts', 3);
    }

    public function passcodeAttemptsLeft(): int
    {
        return max(0, config('otshare.max_passcode_attempts', 3) - $this->passcode_failed_attempts);
    }
}
