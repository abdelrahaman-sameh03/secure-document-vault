<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_role(['manager', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $docId = (int)($_POST['doc_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['pending', 'verified', 'rejected'], true)) {
        $docStmt = db()->prepare('SELECT id, original_name, status FROM documents WHERE id = ? LIMIT 1');
        $docStmt->execute([$docId]);
        $documentBefore = $docStmt->fetch();

        $stmt = db()->prepare('UPDATE documents SET status = ?, verified_at = NOW(), verified_by = ? WHERE id = ?');
        $stmt->execute([$status, $user['id'], $docId]);

        log_system_event('document.workflow_status_updated', 'Manager/Admin updated document workflow status.', $user, [
            'document_id' => $docId,
            'original_name' => $documentBefore['original_name'] ?? null,
            'old_status' => $documentBefore['status'] ?? null,
            'new_status' => $status,
        ]);
        set_flash('success', 'Document status updated.');
    }
    redirect('manager.php');
}

$stmt = db()->query('SELECT d.*, u.name AS owner_name FROM documents d JOIN users u ON u.id = d.owner_id ORDER BY d.uploaded_at DESC');
$documents = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="panel glass reveal">
    <div class="panel-title">
        <div>
            <p class="eyebrow">Manager Review</p>
            <h1>Review and Verify Documents</h1>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Document</th><th>Owner</th><th>Verification</th><th>Workflow</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name']) ?><br><code><?= e(substr($doc['sha256_hash'], 0, 26)) ?>...</code></td>
                    <td><?= e($doc['owner_name']) ?></td>
                    <td><span class="badge <?= e($doc['last_verification_status']) ?>"><?= e($doc['last_verification_status']) ?></span></td>
                    <td><span class="badge <?= e($doc['status']) ?>"><?= e($doc['status']) ?></span></td>
                    <td class="actions">
                        <a href="verify_document.php?id=<?= (int)$doc['id'] ?>">Verify Crypto</a>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="doc_id" value="<?= (int)$doc['id'] ?>">
                            <select name="status">
                                <option value="pending" <?= $doc['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="verified" <?= $doc['status'] === 'verified' ? 'selected' : '' ?>>Verified</option>
                                <option value="rejected" <?= $doc['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                            <button class="mini-btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
