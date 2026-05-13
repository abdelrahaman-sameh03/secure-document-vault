<?php
require_once __DIR__ . '/../app/oauth.php';

$flow = oauth_requested_flow();
$credentials = oauth_credentials('github');
if (!oauth_credentials_ready('github')) {
    set_flash('danger', 'GitHub OAuth credentials are missing. Add GITHUB_CLIENT_ID and GITHUB_CLIENT_SECRET in app/config.php, then try again.');
    redirect(oauth_flow_return_page($flow));
}

oauth_begin('github', 'https://github.com/login/oauth/authorize', [
    'client_id' => $credentials['client_id'],
    'redirect_uri' => $credentials['redirect_uri'],
    'scope' => 'read:user user:email',
], $flow);
