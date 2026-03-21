<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'key_hash',
        'password_hash',
        'totp_secret',
    ];

    protected $hidden = [
        'key_hash',
        'password_hash',
        'totp_secret',
    ];

    protected function casts(): array
    {
        return [
            'totp_secret' => 'encrypted',
        ];
    }
}
