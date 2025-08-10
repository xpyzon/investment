<?php
declare(strict_types=1);

namespace App\Services;

class Totp
{
    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return self::base32Encode($bytes);
    }

    public static function verifyCode(string $base32Secret, string $code, int $timeStep = 30, int $digits = 6, int $window = 1): bool
    {
        $time = time();
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        for ($i = -$window; $i <= $window; $i++) {
            $calc = self::generateCode($base32Secret, $time + ($i * $timeStep), $timeStep, $digits);
            if (hash_equals($calc, $code)) return true;
        }
        return false;
    }

    public static function generateCode(string $base32Secret, int $forTime, int $timeStep = 30, int $digits = 6): string
    {
        $secret = self::base32Decode($base32Secret);
        $counter = intdiv($forTime, $timeStep);
        $binCounter = pack('J', $counter);
        if (PHP_INT_SIZE === 4) { // 32-bit fallback
            $high = ($counter & 0xffffffff00000000) >> 32;
            $low = $counter & 0xffffffff;
            $binCounter = pack('N2', $high, $low);
        } else {
            // Ensure big-endian 64-bit
            $binCounter = pack('N2', $counter >> 32, $counter & 0xffffffff);
        }
        $hash = hash_hmac('sha1', $binCounter, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        $otp = $truncated % (10 ** $digits);
        return str_pad((string)$otp, $digits, '0', STR_PAD_LEFT);
    }

    public static function provisioningUri(string $accountName, string $issuer, string $base32Secret): string
    {
        $label = rawurlencode($issuer.':'.$accountName);
        $issuerParam = rawurlencode($issuer);
        return 'otpauth://totp/'.$label.'?secret='.$base32Secret.'&issuer='.$issuerParam.'&algorithm=SHA1&digits=6&period=30';
    }

    private static function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';
        for ($i=0; $i<strlen($data); $i++) {
            $binaryString .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitGroups = str_split($binaryString, 5);
        $base32 = '';
        foreach ($fiveBitGroups as $bits) {
            if (strlen($bits) < 5) {
                $bits = str_pad($bits, 5, '0', STR_PAD_RIGHT);
            }
            $base32 .= $alphabet[bindec($bits)];
        }
        $padding = 8 - (strlen($base32) % 8);
        if ($padding > 0 && $padding < 8) {
            $base32 .= str_repeat('=', $padding);
        }
        return $base32;
    }

    private static function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $base32 = preg_replace('/[^A-Z2-7=]/', '', $base32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = rtrim($base32, '=');
        $binaryString = '';
        for ($i=0; $i<strlen($base32); $i++) {
            $val = strpos($alphabet, $base32[$i]);
            if ($val === false) continue;
            $binaryString .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        $eightBits = str_split($binaryString, 8);
        foreach ($eightBits as $bits) {
            if (strlen($bits) === 8) {
                $bytes .= chr(bindec($bits));
            }
        }
        return $bytes;
    }
}