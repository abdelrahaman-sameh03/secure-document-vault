<?php
/**
 * Secure Document Vault - Main Configuration
 * XAMPP users: edit database settings if your MySQL username/password are different.
 */

define('APP_NAME', 'Secure Document Vault');
define('APP_URL', getenv('APP_URL') ?: 'https://localhost/secure_document_vault_xampp/public');
define('BASE_PATH', dirname(__DIR__));
define('STORAGE_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'storage');
define('DOCUMENT_STORAGE_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'documents');
define('KEY_STORAGE_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'keys');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'secure_document_vault');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Change these values before real deployment. For local demo, the installer creates keys automatically.
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change_this_secret_locally');
define('APP_ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY') ?: 'Change with your APP_ENCRYPTION_KEY');

define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'txt', 'doc', 'docx', 'png', 'jpg', 'jpeg']);
define('ALLOWED_MIME_PREFIXES', ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/png', 'image/jpeg']);

define('GITHUB_CLIENT_ID', getenv('GITHUB_CLIENT_ID') ?: '');
define('GITHUB_CLIENT_SECRET', getenv('GITHUB_CLIENT_SECRET') ?: '');

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');



// HTTPS switch: set true after configuring SSL in XAMPP/Apache.
define('HTTPS_ONLY', true);

// Cookie settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
if (HTTPS_ONLY) {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
