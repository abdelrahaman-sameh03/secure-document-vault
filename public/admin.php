<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_role(['admin']);
ensure_system_logs_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $targetId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'user';
    if (in_array($role, ['admin', 'manager', 'user'], true) && $targetId !== (int)$user['id']) {
        $beforeStmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $beforeStmt->execute([$targetId]);
        $targetUser = $beforeStmt->fetch();

        $stmt = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $targetId]);

        log_system_event('admin.role_update', 'Admin updated a user role.', $user, [
            'target_user_id' => $targetId,
            'target_name' => $targetUser['name'] ?? null,
            'target_email' => $targetUser['email'] ?? null,
            'old_role' => $targetUser['role'] ?? null,
            'new_role' => $role,
        ]);

        set_flash('success', 'User role updated.');
    } else {
        log_system_event('admin.role_update_blocked', 'Admin role update was blocked.', $user, [
            'target_user_id' => $targetId,
            'requested_role' => $role,
        ]);
        set_flash('warning', 'You cannot change your own role from this panel.');
    }
    redirect('admin.php');
}
$users = db()->query('SELECT id, name, email, role, oauth_provider, two_factor_enabled, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$logs = db()->query('SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 120')->fetchAll();
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="panel glass reveal">
    <div class="panel-title">
        <div>
            <p class="eyebrow">Role-Based Access Control</p>
            <h1>Admin Panel</h1>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>User</th><th>Email</th><th>Auth</th><th>2FA</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['oauth_provider'] ?: 'password') ?></td>
                    <td><span class="badge <?= $u['two_factor_enabled'] ? 'valid' : 'pending' ?>"><?= $u['two_factor_enabled'] ? 'enabled' : 'off' ?></span></td>
                    <td><span class="badge <?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                    <td><?= e($u['created_at']) ?></td>
                    <td>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <select name="role" <?= (int)$u['id'] === (int)$user['id'] ? 'disabled' : '' ?>>
                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="manager" <?= $u['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button class="mini-btn" type="submit" <?= (int)$u['id'] === (int)$user['id'] ? 'disabled' : '' ?>>Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel glass reveal delay-1 admin-logs-panel">
    <div class="panel-title">
        <div>
            <p class="eyebrow">Audit Trail</p>
            <h1>System Logs</h1>
            <p class="muted">Latest 120 security and system events: login, OAuth, uploads, downloads, verification, 2FA, manager review, and admin role changes.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>User</th>
                    <th>Message</th>
                    <th>IP</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$logs): ?>
                <tr><td colspan="6" class="empty">No logs yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']) ?></td>
                    <td><span class="badge log-event"><?= e($log['event_type']) ?></span></td>
                    <td>
                        <?= e($log['user_name'] ?: 'Guest/System') ?><br>
                        <small><?= e($log['user_email'] ?: '—') ?></small>
                    </td>
                    <td><?= e($log['message']) ?></td>
                    <td><code><?= e($log['ip_address'] ?: '—') ?></code></td>
                    <td>
                        <?php $context = pretty_log_context($log['context_json']); ?>
                        <?php if ($context): ?>
                            <details class="log-details">
                                <summary>View</summary>
                                <pre><?= e($context) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
