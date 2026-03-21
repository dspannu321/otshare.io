<?php

namespace App\Support\Admin;

use chillerlan\QRCode\QRCode;

/**
 * Renders a data-URI image (SVG) for otpauth:// URLs so authenticator apps can scan the QR code.
 */
final class AdminTotpQrCode
{
    public static function imageDataUri(string $otpauthUri): string
    {
        return (new QRCode)->render($otpauthUri);
    }
}
