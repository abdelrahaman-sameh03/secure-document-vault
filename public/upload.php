<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/crypto.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_auth();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        log_system_event('document.upload_failed', 'Document upload failed before validation.', $user, ['upload_error' => $_FILES['document']['error'] ?? 'missing_file']);
        $errors[] = 'Please choose a valid document.';
    } else {
        $file = $_FILES['document'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';

        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            $errors[] = 'File extension is not allowed.';
        }
        if ($file['size'] > MAX_UPLOAD_BYTES) {
            $errors[] = 'File size must be 10 MB or less.';
        }
        $mimeOk = false;
        foreach (ALLOWED_MIME_PREFIXES as $allowed) {
            if (str_starts_with($mime, $allowed)) {
                $mimeOk = true;
                break;
            }
        }
        if (!$mimeOk) {
            $errors[] = 'File type is not allowed.';
        }

        if ($errors) {
            log_system_event('document.upload_failed', 'Document upload validation failed.', $user, [
                'original_name' => $originalName,
                'mime_type' => $mime,
                'size_bytes' => (int)$file['size'],
                'errors' => $errors,
            ]);
        }

        if (!$errors) {
            $plainBytes = file_get_contents($file['tmp_name']);
            $sha256 = hash('sha256', $plainBytes);
            $encrypted = encrypt_document_bytes($plainBytes);
            $signature = rsa_sign_hash($sha256);
            $publicKey = rsa_public_key();
            if (!is_dir(DOCUMENT_STORAGE_PATH)) mkdir(DOCUMENT_STORAGE_PATH, 0775, true);
            $storedName = bin2hex(random_bytes(20)) . '.bin';
            file_put_contents(DOCUMENT_STORAGE_PATH . DIRECTORY_SEPARATOR . $storedName, $encrypted['ciphertext']);

            $stmt = db()->prepare('INSERT INTO documents (owner_id, original_name, stored_name, mime_type, size_bytes, sha256_hash, signature, public_key, iv, auth_tag, encryption_alg, status, last_verification_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user['id'], $originalName, $storedName, $mime, $file['size'], $sha256, $signature, $publicKey, $encrypted['iv'], $encrypted['auth_tag'], $encrypted['algorithm'], 'pending', 'not_checked']);
            $documentId = (int)db()->lastInsertId();
            log_system_event('document.upload_success', 'Document encrypted, signed, and uploaded.', $user, [
                'document_id' => $documentId,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'size_bytes' => (int)$file['size'],
                'sha256_hash' => $sha256,
            ]);
            set_flash('success', 'Document encrypted, signed, and uploaded successfully.');
            redirect('verify.php');
        }
    }
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="upload-grid">
    <form class="upload-card glass tilt-card reveal" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <p class="eyebrow">Secure Document Management</p>
        <h1>Upload Document</h1>
        <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>
        <label class="drop-zone" data-drop-zone>
            <input type="file" name="document" required>
            <span class="drop-icon">⬆</span>
            <strong>Choose or drag a file</strong>
            <small>PDF, TXT, DOC, DOCX, PNG, JPG up to 10 MB</small>
        </label>
        <button class="btn primary full" type="submit">Encrypt + Sign + Upload</button>
    </form>
    <div class="process-card glass reveal delay-1">
        <h2>Upload Security Pipeline</h2>
        <ol class="steps-3d">
            <li><span>1</span>Validate file type and size</li>
            <li><span>2</span>Generate SHA-256 integrity hash</li>
            <li><span>3</span>Sign hash with RSA private key</li>
            <li><span>4</span>Encrypt file using AES-256-GCM</li>
            <li><span>5</span>Store only encrypted bytes on server</li>
        </ol>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
