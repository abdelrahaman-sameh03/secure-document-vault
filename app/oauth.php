<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/logger.php';

function oauth_provider_names(): array
{
    return [
        'github' => [
            'title' => 'GitHub OAuth',
            'client_label' => 'GitHub Client ID',
            'secret_label' => 'GitHub Client Secret',
            'redirect_uri' => GITHUB_REDIRECT_URI,
            'start_page' => 'oauth_github.php',
            'icon' => '⌘',
        ],
        'google' => [
            'title' => 'Google OAuth',
            'client_label' => 'Google Client ID',
            'secret_label' => 'Google Client Secret',
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'start_page' => 'oauth_google.php',
            'icon' => 'G',
        ],
    ];
}

function oauth_is_valid_provider(string $provider): bool
{
    return array_key_exists($provider, oauth_provider_names());
}

function oauth_credentials(string $provider): array
{
    return match ($provider) {
        'github' => [
            'client_id' => GITHUB_CLIENT_ID,
            'client_secret' => GITHUB_CLIENT_SECRET,
            'redirect_uri' => GITHUB_REDIRECT_URI,
        ],
        'google' => [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
        ],
        default => [
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ],
    };
}

function oauth_credentials_ready(string $provider): bool
{
    $credentials = oauth_credentials($provider);
    return trim((string)$credentials['client_id']) !== '' && trim((string)$credentials['client_secret']) !== '';
}

function oauth_requested_flow(): string
{
    $flow = strtolower(trim($_GET['flow'] ?? 'login'));
    return $flow === 'register' ? 'register' : 'login';
}

function oauth_flow_return_page(string $flow): string
{
    return $flow === 'register' ? 'register.php' : 'login.php';
}

function oauth_http_json(string $url, array $headers = [], ?array $post = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException(curl_error($ch));
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($response, true);
    if ($status >= 400 || !is_array($json)) {
        throw new RuntimeException('OAuth provider returned an invalid response.');
    }
    return $json;
}

function oauth_find_existing_user(string $provider, string $oauthId, string $email): ?array
{
    $email = strtolower(trim($email));
    $stmt = db()->prepare('SELECT * FROM users WHERE (oauth_provider = ? AND oauth_id = ?) OR email = ? LIMIT 1');
    $stmt->execute([$provider, $oauthId, $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function oauth_find_linked_user(string $provider, string $oauthId): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ? LIMIT 1');
    $stmt->execute([$provider, $oauthId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function oauth_link_user_if_needed(array $user, string $provider, string $oauthId): array
{
    if (empty($user['oauth_provider'])) {
        $stmt = db()->prepare('UPDATE users SET oauth_provider = ?, oauth_id = ? WHERE id = ?');
        $stmt->execute([$provider, $oauthId, $user['id']]);
        $user['oauth_provider'] = $provider;
        $user['oauth_id'] = $oauthId;
    }

    return $user;
}

function oauth_find_or_create_user(string $provider, string $oauthId, string $email, string $name): array
{
    $email = strtolower(trim($email));
    $name = trim($name) ?: ucfirst($provider) . ' User';

    $user = oauth_find_existing_user($provider, $oauthId, $email);
    if (!$user) {
        $stmt = db()->prepare('INSERT INTO users (name, email, role, oauth_provider, oauth_id, two_factor_secret) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, 'user', $provider, $oauthId, totp_generate_secret()]);
        $userId = db()->lastInsertId();
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    return oauth_link_user_if_needed($user, $provider, $oauthId);
}

function oauth_complete_user_flow(string $provider, string $oauthId, string $email, string $name, string $flow): void
{
    $wasLinkedBefore = oauth_find_linked_user($provider, $oauthId) !== null;
    $existingUserBefore = oauth_find_existing_user($provider, $oauthId, $email);
    $user = oauth_find_or_create_user($provider, $oauthId, $email, $name);

    issue_login_cookie($user);

    if ($flow === 'register' && !$wasLinkedBefore) {
        log_system_event('oauth.register_success', 'User registered using ' . ucfirst($provider) . ' OAuth.', $user, ['provider' => $provider]);
        set_flash('success', 'Account registered with ' . ucfirst($provider) . ' OAuth. Welcome, ' . $user['name'] . '.');
    } elseif (!$wasLinkedBefore) {
        log_system_event($existingUserBefore ? 'oauth.account_linked' : 'oauth.auto_register_success', 'User logged in using ' . ucfirst($provider) . ' OAuth.', $user, [
            'provider' => $provider,
            'created_or_linked' => $existingUserBefore ? 'linked_existing_email' : 'created_new_account',
        ]);
        set_flash('success', 'Logged in with ' . ucfirst($provider) . ' OAuth. A new account was created automatically.');
    } else {
        log_system_event('oauth.login_success', 'User logged in using ' . ucfirst($provider) . ' OAuth.', $user, ['provider' => $provider]);
        set_flash('success', 'Logged in with ' . ucfirst($provider) . ' OAuth.');
    }

    redirect('dashboard.php');
}

function oauth_begin(string $provider, string $authUrl, array $params, string $flow = 'login'): void
{
    $flow = $flow === 'register' ? 'register' : 'login';
    $_SESSION['oauth_' . $provider . '_state'] = bin2hex(random_bytes(16));
    $_SESSION['oauth_' . $provider . '_flow'] = $flow;
    $params['state'] = $_SESSION['oauth_' . $provider . '_state'];
    header('Location: ' . $authUrl . '?' . http_build_query($params));
    exit;
}

function oauth_validate_state(string $provider): array
{
    $state = $_GET['state'] ?? '';
    $code = $_GET['code'] ?? '';
    $flow = $_SESSION['oauth_' . $provider . '_flow'] ?? 'login';
    if (!$code || !$state || !hash_equals($_SESSION['oauth_' . $provider . '_state'] ?? '', $state)) {
        log_system_event('oauth.state_failed', ucfirst($provider) . ' OAuth state validation failed.', null, ['provider' => $provider, 'flow' => $flow]);
        set_flash('danger', ucfirst($provider) . ' OAuth state validation failed.');
        redirect(oauth_flow_return_page($flow));
    }
    unset($_SESSION['oauth_' . $provider . '_state'], $_SESSION['oauth_' . $provider . '_flow']);
    return ['code' => $code, 'flow' => ($flow === 'register' ? 'register' : 'login')];
}
