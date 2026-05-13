<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function password_policy_errors(string $password): array
{
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must include at least one special character.';
    }
    return $errors;
}

function issue_login_cookie(array $user): void
{
    $payload = [
        'sub' => (int)$user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 3600 * 8,
    ];
    $token = jwt_encode($payload);
    setcookie('vault_jwt', $token, [
        'expires' => $payload['exp'],
        'path' => '/',
        'secure' => HTTPS_ONLY,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_login_cookie(): void
{
    setcookie('vault_jwt', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => HTTPS_ONLY,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }
    $token = $_COOKIE['vault_jwt'] ?? '';
    $payload = $token ? jwt_decode($token) : null;
    if (!$payload || empty($payload['sub'])) {
        $cached = false;
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, role, two_factor_enabled, two_factor_secret, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$payload['sub']]);
    $user = $stmt->fetch();
    $cached = $user ?: false;
    return $user ?: null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        set_flash('warning', 'Please login first.');
        redirect('login.php');
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_auth();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden: your role is not allowed to access this page.');
    }
    return $user;
}

function role_rank(string $role): int
{
    return ['user' => 1, 'manager' => 2, 'admin' => 3][$role] ?? 0;
}

function user_can_manage_document(array $user, array $document): bool
{
    if ($user['role'] === 'admin' || $user['role'] === 'manager') {
        return true;
    }
    return (int)$document['owner_id'] === (int)$user['id'];
}
