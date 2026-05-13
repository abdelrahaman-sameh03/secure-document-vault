<?php
require_once __DIR__ . '/../app/oauth.php';

$credentials = oauth_credentials('google');
if (!oauth_credentials_ready('google')) {
    set_flash('danger', 'Google OAuth credentials are missing. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in app/config.php.');
    redirect('login.php');
}

$oauthState = oauth_validate_state('google');
$code = $oauthState['code'];
$flow = $oauthState['flow'];

try {
    $tokenResponse = oauth_http_json('https://oauth2.googleapis.com/token', [], [
        'client_id' => $credentials['client_id'],
        'client_secret' => $credentials['client_secret'],
        'code' => $code,
        'redirect_uri' => $credentials['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]);
    $accessToken = $tokenResponse['access_token'] ?? null;
    if (!$accessToken) {
        throw new RuntimeException('Google did not return an access token.');
    }

    $googleUser = oauth_http_json('https://www.googleapis.com/oauth2/v3/userinfo', ['Authorization: Bearer ' . $accessToken]);
    if (empty($googleUser['email']) || empty($googleUser['email_verified'])) {
        throw new RuntimeException('No verified email found in Google account.');
    }

    oauth_complete_user_flow('google', (string)$googleUser['sub'], $googleUser['email'], $googleUser['name'] ?? 'Google User', $flow);
} catch (Throwable $ex) {
    log_system_event('oauth.google_failed', 'Google OAuth failed.', null, ['error' => $ex->getMessage(), 'flow' => $flow]);
    set_flash('danger', 'Google OAuth failed: ' . $ex->getMessage());
    redirect(oauth_flow_return_page($flow));
}
