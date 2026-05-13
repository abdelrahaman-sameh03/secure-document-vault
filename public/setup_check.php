<?php
require_once __DIR__ . '/../app/config.php';
$checks = [
    'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO MySQL Enabled' => extension_loaded('pdo_mysql'),
    'OpenSSL Enabled' => extension_loaded('openssl'),
    'cURL Enabled for OAuth' => extension_loaded('curl'),
    'GitHub OAuth credentials configured' => GITHUB_CLIENT_ID !== '' && GITHUB_CLIENT_SECRET !== '',
    'Google OAuth credentials configured' => GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '',
    'Documents folder writable' => is_writable(DOCUMENT_STORAGE_PATH) || is_writable(dirname(DOCUMENT_STORAGE_PATH)),
    'Keys folder writable' => is_writable(KEY_STORAGE_PATH) || is_writable(dirname(KEY_STORAGE_PATH)),
];
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Setup Check</title><link rel="stylesheet" href="assets/css/style.css"></head><body><main class="page-shell"><section class="panel glass"><h1>Setup Check</h1><table><tbody><?php foreach($checks as $name=>$ok): ?><tr><td><?= e($name) ?></td><td><span class="badge <?= $ok?'valid':'failed' ?>"><?= $ok?'OK':'Missing' ?></span></td></tr><?php endforeach; ?></tbody></table><p><a href="index.php">Back to app</a></p></section></main></body></html>
