<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/logger.php';
$user = current_user();
if ($user) {
    log_system_event('auth.logout', 'User logged out.', $user);
}
clear_login_cookie();
set_flash('success', 'Logged out successfully.');
redirect('login.php');
