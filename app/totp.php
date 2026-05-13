<?php
require_once __DIR__ . '/config.php';

function base32_encode_no_padding(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $base32 = '';
    foreach (str_split($bits, 5) as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $base32 .= $alphabet[bindec($chunk)];
    }
    return $base32;
}

function base32_decode_no_padding(string $base32): string|false
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
    $bits = '';
    foreach (str_split($base32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            return false;
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $bytes .= chr(bindec($byte));
        }
    }
    return $bytes;
}

function totp_generate_secret(): string
{
    return base32_encode_no_padding(random_bytes(20));
}

function hotp(string $secret, int $counter, int $digits = 6): string
{
    $key = base32_decode_no_padding($secret);
    if ($key === false) {
        return '000000';
    }
    $binaryCounter = pack('N*', 0) . pack('N*', $counter);
    $hash = hash_hmac('sha1', $binaryCounter, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
    return str_pad((string)($truncated % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}

function totp_now(string $secret, ?int $time = null): string
{
    $time = $time ?? time();
    return hotp($secret, intdiv($time, 30));
}

function totp_verify(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\D/', '', $code);
    $counter = intdiv(time(), 30);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(hotp($secret, $counter + $i), $code)) {
            return true;
        }
    }
    return false;
}

function totp_qr_url(string $email, string $secret): string
{
    $issuer = rawurlencode(APP_NAME);
    $label = rawurlencode(APP_NAME . ':' . $email);
    $otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($otpauth);
}
