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
    log_system_event('document.download_denied', 'Document download was denied.', $user, ['document_id' => $id]);
    http_response_code(404);
    exit('Document not found.');
}
$cipherPath = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $doc['stored_name'];
if (!file_exists($cipherPath)) {
    http_response_code(404);
    exit('Encrypted file not found.');
}
$plain = decrypt_document_bytes(file_get_contents($cipherPath), $doc['iv'], $doc['auth_tag']);
if ($plain === false) {
    log_system_event('document.download_failed', 'Document download failed because decryption failed.', $user, ['document_id' => (int)$doc['id'], 'original_name' => $doc['original_name']]);
    http_response_code(409);
    exit('Cannot decrypt document. It may be corrupted or tampered with.');
}
log_system_event('document.download_success', 'Document downloaded.', $user, ['document_id' => (int)$doc['id'], 'original_name' => $doc['original_name']]);
header('Content-Type: ' . $doc['mime_type']);
header('Content-Length: ' . strlen($plain));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
echo $plain;
exit;
