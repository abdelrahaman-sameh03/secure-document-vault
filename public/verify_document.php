<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/crypto.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_auth();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc || !user_can_manage_document($user, $doc)) {
    log_system_event('document.verify_denied', 'Document integrity verification was denied.', $user, ['document_id' => $id]);
    http_response_code(404);
    exit('Document not found.');
}

$cipherPath = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $doc['stored_name'];
$status = 'failed';
$message = 'Document verification failed.';

if (file_exists($cipherPath)) {
    $cipherBytes = file_get_contents($cipherPath);
    $plain = decrypt_document_bytes($cipherBytes, $doc['iv'], $doc['auth_tag']);
    if ($plain !== false) {
        $currentHash = hash('sha256', $plain);
        $hashOk = hash_equals($doc['sha256_hash'], $currentHash);
        $sigOk = rsa_verify_hash($currentHash, $doc['signature'], $doc['public_key']);
        if ($hashOk && $sigOk) {
            $status = 'valid';
            $message = 'Document is valid: encrypted file decrypted successfully, SHA-256 hash matched, and RSA signature verified.';
        } else {
            $message = 'Document decrypted, but hash or signature does not match. The document may have been modified.';
        }
    } else {
        $message = 'Decryption failed. The encrypted file or authentication tag may have been tampered with.';
    }
} else {
    $message = 'Encrypted file is missing from storage.';
}

$update = db()->prepare('UPDATE documents SET last_verification_status = ?, verified_at = NOW(), verified_by = ? WHERE id = ?');
$update->execute([$status, $user['id'], $doc['id']]);
log_system_event($status === 'valid' ? 'document.verify_valid' : 'document.verify_failed', $message, $user, [
    'document_id' => (int)$doc['id'],
    'original_name' => $doc['original_name'],
    'verification_status' => $status,
]);
set_flash($status === 'valid' ? 'success' : 'danger', $message);
redirect('document.php?id=' . $doc['id']);
