<?php

namespace App\Services;

use Illuminate\Support\Str;

class PickupCodeService
{
    public function generate(): string
    {
        $shortId = $this->randomShortId(config('otshare.short_id_length'));
        $pin = $this->randomPin(config('otshare.pin_length'));

        return $shortId . '-' . $pin;
    }

    public function shortIdFromCode(string $pickupCode): ?string
    {
        $parts = explode('-', $pickupCode, 2);
        if (count($parts) !== 2) {
            return null;
        }
        $shortId = strtoupper($parts[0]);
        $pin = $parts[1];
        if (! preg_match('/^[A-Z0-9]{4}$/', $shortId) || ! ctype_digit($pin)) {
            return null;
        }

        return $shortId;
    }

    public function hash(string $pickupCode): string
    {
        return hash_hmac('sha256', $pickupCode, config('app.key'));
    }

    public function verify(string $pickupCode, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hash($pickupCode));
    }

    protected function randomShortId(int $length): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous 0/O, 1/I
        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }

    protected function randomPin(int $length): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= (string) random_int(0, 9);
        }

        return $result;
    }
}
