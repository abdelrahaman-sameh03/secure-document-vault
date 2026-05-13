<?php
require_once __DIR__ . '/../app/auth.php';
$user = require_auth();

if ($user['role'] === 'admin') {
    $stats = db()->query('SELECT
        (SELECT COUNT(*) FROM users) AS users_count,
        (SELECT COUNT(*) FROM documents) AS docs_count,
        (SELECT COUNT(*) FROM documents WHERE status = "pending") AS pending_count,
        (SELECT COUNT(*) FROM documents WHERE last_verification_status = "valid") AS verified_count
    ')->fetch();
    $stmt = db()->query('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id ORDER BY d.uploaded_at DESC LIMIT 8');
} elseif ($user['role'] === 'manager') {
    $stats = db()->query('SELECT
        (SELECT COUNT(*) FROM documents) AS docs_count,
        (SELECT COUNT(*) FROM documents WHERE status = "pending") AS pending_count,
        (SELECT COUNT(*) FROM documents WHERE last_verification_status = "valid") AS verified_count
    ')->fetch();
    $stmt = db()->query('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id ORDER BY d.uploaded_at DESC LIMIT 8');
} else {
    $stmtStats = db()->prepare('SELECT
        COUNT(*) AS docs_count,
        SUM(last_verification_status = "valid") AS verified_count,
        SUM(status = "pending") AS pending_count
        FROM documents WHERE owner_id = ?');
    $stmtStats->execute([$user['id']]);
    $stats = $stmtStats->fetch();
    $stmt = db()->prepare('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id WHERE owner_id = ? ORDER BY uploaded_at DESC LIMIT 8');
    $stmt->execute([$user['id']]);
}
$documents = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Role: <?= e(ucfirst($user['role'])) ?></p>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p class="muted">Manage encrypted documents and verify integrity from one secure interface.</p>
    </div>
    <a class="btn primary" href="upload.php">Upload Document</a>
</section>
<section class="stats-grid">
    <div class="stat glass tilt-card"><span><?= e((string)($stats['users_count'] ?? '—')) ?></span><p>Users</p></div>
    <div class="stat glass tilt-card"><span><?= e((string)($stats['docs_count'] ?? 0)) ?></span><p>Total Documents</p></div>
    <div class="stat glass tilt-card"><span><?= e((string)($stats['pending_count'] ?? 0)) ?></span><p>Pending</p></div>
    <div class="stat glass tilt-card"><span><?= e((string)($stats['verified_count'] ?? '—')) ?></span><p>Integrity Verified</p></div>
</section>
<section class="panel glass reveal">
    <div class="panel-title">
        <h2>Recent Documents</h2>
        <a href="verify.php">View all</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Owner</th><th>Status</th><th>SHA-256</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$documents): ?>
                <tr><td colspan="6" class="empty">No documents yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name']) ?></td>
                    <td><?= e($doc['owner_name']) ?></td>
                    <td><span class="badge <?= e($doc['status']) ?>"><?= e($doc['status']) ?></span></td>
                    <td><code><?= e(substr($doc['sha256_hash'], 0, 18)) ?>...</code></td>
                    <td><?= e($doc['uploaded_at']) ?></td>
                    <td class="actions">
                        <a href="document.php?id=<?= (int)$doc['id'] ?>">Details</a>
                        <a href="download.php?id=<?= (int)$doc['id'] ?>">Download</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
