<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('verify.php');
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc || !user_can_manage_document($user, $doc)) {
    log_system_event('document.delete_denied', 'Document delete was denied.', $user, ['document_id' => $id]);
    http_response_code(404);
    exit('Document not found.');
}
$path = DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $doc['stored_name'];
if (file_exists($path)) unlink($path);
$delete = db()->prepare('DELETE FROM documents WHERE id = ?');
$delete->execute([$doc['id']]);
log_system_event('document.delete_success', 'Document deleted.', $user, ['document_id' => (int)$doc['id'], 'original_name' => $doc['original_name']]);
set_flash('success', 'Document deleted.');
redirect('verify.php');
