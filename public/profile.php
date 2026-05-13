<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/totp.php';
require_once __DIR__ . '/../app/logger.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'enable_2fa') {
        $code = trim($_POST['totp'] ?? '');
        if (totp_verify($user['two_factor_secret'], $code)) {
            $stmt = db()->prepare('UPDATE users SET two_factor_enabled = 1 WHERE id = ?');
            $stmt->execute([$user['id']]);
            log_system_event('auth.2fa_enabled', 'User enabled Two-Factor Authentication.', $user);
            set_flash('success', 'Two-Factor Authentication enabled successfully.');
            redirect('profile.php');
        }
        log_system_event('auth.2fa_enable_failed', 'User failed to enable Two-Factor Authentication.', $user);
        set_flash('danger', 'Invalid 2FA code. Scan the QR and try again.');
    }
    if ($action === 'disable_2fa') {
        $stmt = db()->prepare('UPDATE users SET two_factor_enabled = 0, two_factor_secret = ? WHERE id = ?');
        $stmt->execute([totp_generate_secret(), $user['id']]);
        log_system_event('auth.2fa_disabled', 'User disabled Two-Factor Authentication.', $user);
        set_flash('success', 'Two-Factor Authentication disabled and secret reset.');
        redirect('profile.php');
    }
}
$qr = totp_qr_url($user['email'], $user['two_factor_secret']);
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="profile-grid">
    <div class="panel glass tilt-card reveal">
        <p class="eyebrow">Account Security</p>
        <h1><?= e($user['name']) ?></h1>
        <dl class="meta-list">
            <dt>Email</dt><dd><?= e($user['email']) ?></dd>
            <dt>Role</dt><dd><span class="badge <?= e($user['role']) ?>"><?= e($user['role']) ?></span></dd>
            <dt>2FA</dt><dd><span class="badge <?= $user['two_factor_enabled'] ? 'valid' : 'pending' ?>"><?= $user['two_factor_enabled'] ? 'Enabled' : 'Disabled' ?></span></dd>
            <dt>Joined</dt><dd><?= e($user['created_at']) ?></dd>
        </dl>
    </div>
    <div class="panel glass reveal delay-1">
        <h2>Two-Factor Authentication</h2>
        <?php if (!$user['two_factor_enabled']): ?>
            <p class="muted">Scan the QR code with Google Authenticator, Microsoft Authenticator, or Authy. Then enter the 6-digit code.</p>
            <div class="qr-wrap"><img src="<?= e($qr) ?>" alt="2FA QR Code"></div>
            <code class="secret-code"><?= e($user['two_factor_secret']) ?></code>
            <form method="post" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="enable_2fa">
                <label>Authenticator Code<input name="totp" inputmode="numeric" maxlength="6" placeholder="123456" required></label>
                <button class="btn primary" type="submit">Enable 2FA</button>
            </form>
        <?php else: ?>
            <p class="muted">2FA is active. Login requires password plus a time-based one-time code.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="disable_2fa">
                <button class="btn danger" type="submit">Disable 2FA</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
