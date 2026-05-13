<?php
require_once __DIR__ . '/../app/oauth.php';

$credentials = oauth_credentials('github');
if (!oauth_credentials_ready('github')) {
    set_flash('danger', 'GitHub OAuth credentials are missing. Add GITHUB_CLIENT_ID and GITHUB_CLIENT_SECRET in app/config.php.');
    redirect('login.php');
}

$oauthState = oauth_validate_state('github');
$code = $oauthState['code'];
$flow = $oauthState['flow'];

try {
    $tokenResponse = oauth_http_json('https://github.com/login/oauth/access_token', ['User-Agent: Secure-Document-Vault'], [
        'client_id' => $credentials['client_id'],
        'client_secret' => $credentials['client_secret'],
        'code' => $code,
        'redirect_uri' => $credentials['redirect_uri'],
    ]);
    $accessToken = $tokenResponse['access_token'] ?? null;
    if (!$accessToken) {
        throw new RuntimeException('GitHub did not return an access token.');
    }

    $authHeader = ['Authorization: Bearer ' . $accessToken, 'User-Agent: Secure-Document-Vault'];
    $ghUser = oauth_http_json('https://api.github.com/user', $authHeader);
    $emails = oauth_http_json('https://api.github.com/user/emails', $authHeader);

    $email = $ghUser['email'] ?? null;
    foreach ($emails as $item) {
        if (!empty($item['primary']) && !empty($item['verified'])) {
            $email = $item['email'];
            break;
        }
    }
    if (!$email) {
        throw new RuntimeException('No verified email found in GitHub account.');
    }

    oauth_complete_user_flow('github', (string)$ghUser['id'], $email, $ghUser['name'] ?: ($ghUser['login'] ?? 'GitHub User'), $flow);
} catch (Throwable $ex) {
    log_system_event('oauth.github_failed', 'GitHub OAuth failed.', null, ['error' => $ex->getMessage(), 'flow' => $flow]);
    set_flash('danger', 'GitHub OAuth failed: ' . $ex->getMessage());
    redirect(oauth_flow_return_page($flow));
}
