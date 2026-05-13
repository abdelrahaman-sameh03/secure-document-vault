<?php
require_once __DIR__ . '/config.php';

function vault_encryption_key(): string
{
    $key = base64_decode(APP_ENCRYPTION_KEY, true);
    if ($key === false || strlen($key) < 32) {
        return hash('sha256', APP_ENCRYPTION_KEY, true);
    }
    return substr($key, 0, 32);
}

function ensure_keypair(): void
{
    if (!is_dir(KEY_STORAGE_PATH)) {
        mkdir(KEY_STORAGE_PATH, 0775, true);
    }
    $privatePath = KEY_STORAGE_PATH . DIRECTORY_SEPARATOR . 'vault_private.pem';
    $publicPath = KEY_STORAGE_PATH . DIRECTORY_SEPARATOR . 'vault_public.pem';

    if (file_exists($privatePath) && file_exists($publicPath)) {
        return;
    }

$configPath = 'C:\\xampp\\apache\\conf\\openssl.cnf';

if (!file_exists($configPath)) {
    $configPath = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';
}

$config = [
    'config' => $configPath,
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'digest_alg' => 'sha256',
];

$res = openssl_pkey_new($config);
if (!$res) {
    $errors = [];
    while ($msg = openssl_error_string()) {
        $errors[] = $msg;
    }
    throw new RuntimeException('Unable to generate RSA key pair. OpenSSL error: ' . implode(' | ', $errors));
}

openssl_pkey_export($res, $privateKey, null, $config);
$details = openssl_pkey_get_details($res);
    if (!$res) {
        throw new RuntimeException('Unable to generate RSA key pair. Check OpenSSL configuration in PHP.');
    }
    openssl_pkey_export($res, $privateKey);
    $details = openssl_pkey_get_details($res);
    file_put_contents($privatePath, $privateKey);
    file_put_contents($publicPath, $details['key']);
}

function rsa_sign_hash(string $hashHex): string
{
    ensure_keypair();
    $privateKey = file_get_contents(KEY_STORAGE_PATH . DIRECTORY_SEPARATOR . 'vault_private.pem');
    $ok = openssl_sign($hashHex, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        throw new RuntimeException('Failed to sign document hash.');
    }
    return base64_encode($signature);
}

function rsa_public_key(): string
{
    ensure_keypair();
    return file_get_contents(KEY_STORAGE_PATH . DIRECTORY_SEPARATOR . 'vault_public.pem');
}

function rsa_verify_hash(string $hashHex, string $signatureB64, string $publicKey): bool
{
    $signature = base64_decode($signatureB64, true);
    if ($signature === false) {
        return false;
    }
    return openssl_verify($hashHex, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
}

function encrypt_document_bytes(string $plainBytes): array
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plainBytes, 'aes-256-gcm', vault_encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        throw new RuntimeException('Encryption failed.');
    }
    return [
        'ciphertext' => $cipher,
        'iv' => base64_encode($iv),
        'auth_tag' => base64_encode($tag),
        'algorithm' => 'AES-256-GCM',
    ];
}

function decrypt_document_bytes(string $cipherBytes, string $ivB64, string $tagB64): string|false
{
    $iv = base64_decode($ivB64, true);
    $tag = base64_decode($tagB64, true);
    if ($iv === false || $tag === false) {
        return false;
    }
    return openssl_decrypt($cipherBytes, 'aes-256-gcm', vault_encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
}
