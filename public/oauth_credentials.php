<?php
require_once __DIR__ . '/../app/oauth.php';
set_flash('warning', 'OAuth credentials are no longer entered inside the website. Add GitHub or Google credentials once in app/config.php, then use the OAuth buttons from Login or Register.');
redirect('login.php');
