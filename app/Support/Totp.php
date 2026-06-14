<?php

namespace App\Support;

/**
 * Minimal RFC 6238 TOTP (time-based one-time password) — the standard used by
 * Google Authenticator, Microsoft Authenticator, Authy, etc. Pure PHP so the
 * on-site single-bundle install needs no extra Composer package. SHA-1, 6
 * digits, 30-second period (the universal defaults all authenticator apps use).
 */
class Totp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // RFC 4648 base32

    /** A fresh random base32 secret (default 160 bits). */
    public static function generateSecret(int $length = 32): string
    {
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            $s .= self::ALPHABET[random_int(0, 31)];
        }

        return $s;
    }

    /** Verify a user-entered code, tolerating ±$window steps of clock drift. */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if ($code === '' || strlen($code) !== self::DIGITS) {
            return false;
        }

        $counter = intdiv(time(), self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** The otpauth:// URI to encode in the setup QR code. */
    public static function provisioningUri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode("{$issuer}:{$label}")
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    private static function codeAt(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        if ($key === '') {
            return str_repeat('0', self::DIGITS);
        }

        // 8-byte big-endian counter (high word is 0 for any realistic time).
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = rtrim(strtoupper($b32), '=');
        $bits = '';
        foreach (str_split($b32) as $c) {
            $v = strpos(self::ALPHABET, $c);
            if ($v === false) {
                continue;
            }
            $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    /** Generate one-time recovery codes (shown once, stored for later match). */
    public static function recoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::randomBlock(5) . '-' . self::randomBlock(5);
        }

        return $codes;
    }

    private static function randomBlock(int $len): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous 0/O/1/I
        $s = '';
        for ($i = 0; $i < $len; $i++) {
            $s .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $s;
    }
}
