<?php
require_once __DIR__ . '/config.php';

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string|false
{
    $padding = 4 - (strlen($data) % 4);
    if ($padding < 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'), true);
}

function jwt_encode(array $payload, string $secret = JWT_SECRET): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [
        b64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES)),
        b64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
    $segments[] = b64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $jwt, string $secret = JWT_SECRET): ?array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    [$headb64, $payloadb64, $sigb64] = $parts;
    $expected = b64url_encode(hash_hmac('sha256', $headb64 . '.' . $payloadb64, $secret, true));
    if (!hash_equals($expected, $sigb64)) {
        return null;
    }
    $payloadJson = b64url_decode($payloadb64);
    if ($payloadJson === false) {
        return null;
    }
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }
    if (isset($payload['exp']) && time() > (int)$payload['exp']) {
        return null;
    }
    return $payload;
}
