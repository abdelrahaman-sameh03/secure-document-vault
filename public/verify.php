<?php
require_once __DIR__ . '/../app/auth.php';
$user = require_auth();

if (in_array($user['role'], ['admin', 'manager'], true)) {
    $stmt = db()->query('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id ORDER BY d.uploaded_at DESC');
} else {
    $stmt = db()->prepare('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id WHERE d.owner_id = ? ORDER BY d.uploaded_at DESC');
    $stmt->execute([$user['id']]);
}
$documents = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="panel glass reveal">
    <div class="panel-title">
        <div>
            <p class="eyebrow">Digital Signatures & Integrity Verification</p>
            <h1>Document Verification</h1>
        </div>
        <a class="btn secondary" href="upload.php">Upload New</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Document</th><th>Owner</th><th>Size</th><th>Status</th><th>Last Check</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$documents): ?><tr><td colspan="6" class="empty">No documents found.</td></tr><?php endif; ?>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name']) ?><br><code><?= e(substr($doc['sha256_hash'], 0, 28)) ?>...</code></td>
                    <td><?= e($doc['owner_name']) ?></td>
                    <td><?= number_format($doc['size_bytes'] / 1024, 2) ?> KB</td>
                    <td><span class="badge <?= e($doc['status']) ?>"><?= e($doc['status']) ?></span></td>
                    <td><span class="badge <?= e($doc['last_verification_status']) ?>"><?= e($doc['last_verification_status']) ?></span></td>
                    <td class="actions">
                        <a href="document.php?id=<?= (int)$doc['id'] ?>">Details</a>
                        <a href="verify_document.php?id=<?= (int)$doc['id'] ?>">Verify</a>
                        <a href="download.php?id=<?= (int)$doc['id'] ?>">Download</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
