<?php
require_once __DIR__ . '/../app/oauth.php';

$flow = oauth_requested_flow();
$credentials = oauth_credentials('google');
if (!oauth_credentials_ready('google')) {
    set_flash('danger', 'Google OAuth credentials are missing. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in app/config.php, then try again.');
    redirect(oauth_flow_return_page($flow));
}

oauth_begin('google', 'https://accounts.google.com/o/oauth2/v2/auth', [
    'client_id' => $credentials['client_id'],
    'redirect_uri' => $credentials['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'access_type' => 'online',
    'prompt' => 'select_account',
], $flow);
