<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
$user = require_auth();
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT d.*, u.name AS owner_name, v.name AS verifier_name FROM documents d JOIN users u ON u.id = d.owner_id LEFT JOIN users v ON v.id = d.verified_by WHERE d.id = ? LIMIT 1');
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc || !user_can_manage_document($user, $doc)) {
    http_response_code(404);
    exit('Document not found.');
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="document-view grid-2">
    <div class="panel glass tilt-card reveal">
        <p class="eyebrow">Document Metadata</p>
        <h1><?= e($doc['original_name']) ?></h1>
        <dl class="meta-list">
            <dt>Owner</dt><dd><?= e($doc['owner_name']) ?></dd>
            <dt>MIME Type</dt><dd><?= e($doc['mime_type']) ?></dd>
            <dt>Size</dt><dd><?= number_format($doc['size_bytes'] / 1024, 2) ?> KB</dd>
            <dt>Encryption</dt><dd><?= e($doc['encryption_alg']) ?></dd>
            <dt>Workflow Status</dt><dd><span class="badge <?= e($doc['status']) ?>"><?= e($doc['status']) ?></span></dd>
            <dt>Last Verification</dt><dd><span class="badge <?= e($doc['last_verification_status']) ?>"><?= e($doc['last_verification_status']) ?></span></dd>
            <dt>Uploaded</dt><dd><?= e($doc['uploaded_at']) ?></dd>
            <dt>Verified At</dt><dd><?= e($doc['verified_at'] ?? 'Not verified yet') ?></dd>
            <dt>Verified By</dt><dd><?= e($doc['verifier_name'] ?? '—') ?></dd>
        </dl>
        <div class="actions row-actions">
            <a class="btn primary" href="verify_document.php?id=<?= (int)$doc['id'] ?>">Verify Integrity</a>
            <a class="btn secondary" href="download.php?id=<?= (int)$doc['id'] ?>">Download</a>
            <form method="post" action="delete_document.php" onsubmit="return confirm('Delete this document?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                <button class="btn danger" type="submit">Delete</button>
            </form>
        </div>
    </div>
    <div class="panel glass reveal delay-1">
        <h2>Cryptographic Evidence</h2>
        <label>SHA-256 Hash<textarea readonly><?= e($doc['sha256_hash']) ?></textarea></label>
        <label>Digital Signature<textarea readonly><?= e($doc['signature']) ?></textarea></label>
        <label>Public Key<textarea readonly><?= e($doc['public_key']) ?></textarea></label>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
