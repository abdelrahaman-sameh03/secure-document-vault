<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/totp.php';
require_once __DIR__ . '/../app/logger.php';
if (current_user()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $totp = trim($_POST['totp'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        log_system_event('auth.login_failed', 'Failed password login attempt.', null, ['email' => $email]);
        $errors[] = 'Invalid email or password.';
    } elseif ((int)$user['two_factor_enabled'] === 1 && !totp_verify($user['two_factor_secret'], $totp)) {
        log_system_event('auth.2fa_failed', 'Failed 2FA login attempt.', $user, ['email' => $email]);
        $errors[] = 'Invalid 2FA code.';
    } else {
        issue_login_cookie($user);
        log_system_event('auth.login_success', 'User logged in with email and password.', $user);
        set_flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect('dashboard.php');
    }
}
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="auth-wrap">
    <form class="auth-card glass tilt-card reveal" method="post" autocomplete="off">
        <?= csrf_field() ?>
        <p class="eyebrow">Protected access</p>
        <h1>Login</h1>
        <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>
        <label>Email<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
        <label>Password<input type="password" name="password" required></label>
        <label>2FA Code <span class="optional">if enabled</span><input name="totp" inputmode="numeric" maxlength="6" placeholder="123456"></label>
        <button class="btn primary full" type="submit">Login Securely</button>

        <div class="oauth-grid">
            <a class="btn oauth-btn full" href="oauth_github.php?flow=login">
                <span class="oauth-icon github" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path fill="currentColor" d="M12 2C6.48 2 2 6.58 2 12.22c0 4.5 2.87 8.31 6.84 9.66.5.1.66-.22.66-.49v-1.72c-2.78.62-3.37-1.21-3.37-1.21-.45-1.18-1.11-1.49-1.11-1.49-.91-.63.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.9 1.58 2.35 1.12 2.92.86.09-.67.35-1.12.63-1.37-2.22-.26-4.56-1.15-4.56-5.1 0-1.13.39-2.04 1.03-2.76-.1-.26-.45-1.32.1-2.76 0 0 .84-.28 2.75 1.05A9.27 9.27 0 0 1 12 6.84c.85 0 1.7.12 2.5.35 1.9-1.33 2.74-1.05 2.74-1.05.56 1.44.21 2.5.1 2.76.64.72 1.03 1.63 1.03 2.76 0 3.96-2.34 4.84-4.57 5.09.36.32.68.93.68 1.88v2.79c0 .27.18.6.67.49A10.26 10.26 0 0 0 22 12.22C22 6.58 17.52 2 12 2Z"></path>
                    </svg>
                </span>
                <span>Login with GitHub OAuth</span>
            </a>

            <a class="btn oauth-btn full" href="oauth_google.php?flow=login">
                <span class="oauth-icon google" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.92h5.48c-.24 1.26-.96 2.33-2.04 3.05l3.3 2.56c1.92-1.77 3.02-4.37 3.02-7.45 0-.72-.06-1.4-.18-2.08H12z"></path>
                        <path fill="#34A853" d="M12 22c2.7 0 4.96-.89 6.62-2.42l-3.3-2.56c-.92.62-2.08.99-3.32.99-2.55 0-4.7-1.72-5.47-4.02l-3.4 2.62C4.8 19.84 8.13 22 12 22z"></path>
                        <path fill="#4A90E2" d="M6.53 13.99A5.96 5.96 0 0 1 6.22 12c0-.69.12-1.35.31-1.99l-3.4-2.62A10.02 10.02 0 0 0 2 12c0 1.61.39 3.13 1.13 4.39l3.4-2.4z"></path>
                        <path fill="#FBBC05" d="M12 5.99c1.47 0 2.79.51 3.83 1.5l2.87-2.87C16.95 2.98 14.69 2 12 2 8.13 2 4.8 4.16 3.13 7.61l3.4 2.62C7.3 7.71 9.45 5.99 12 5.99z"></path>
                    </svg>
                </span>
                <span>Login with Google OAuth</span>
            </a>
        </div>

        <p class="small-center">OAuth login can create or link your account automatically.</p>
        <p class="small-center">No account? <a href="register.php">Register</a></p>
    </form>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
