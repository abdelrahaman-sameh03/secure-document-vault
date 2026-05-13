<?php
require_once __DIR__ . '/db.php';

function ensure_system_logs_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    db()->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        user_name VARCHAR(120) NULL,
        user_email VARCHAR(180) NULL,
        event_type VARCHAR(80) NOT NULL,
        message VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        context_json TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_system_logs_created_at (created_at),
        INDEX idx_system_logs_event_type (event_type),
        INDEX idx_system_logs_user_id (user_id),
        CONSTRAINT fk_system_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function log_system_event(string $eventType, string $message, ?array $user = null, array $context = []): void
{
    try {
        ensure_system_logs_table();

        $eventType = strtolower(trim($eventType));
        $eventType = preg_replace('/[^a-z0-9_.-]+/', '_', $eventType) ?: 'system_event';
        $eventType = substr($eventType, 0, 80);
        $message = substr(trim($message), 0, 255);

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($forwarded[0]);
        }
        $ipAddress = $ipAddress ? substr($ipAddress, 0, 45) : null;
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 255);
        $contextJson = $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $stmt = db()->prepare('INSERT INTO system_logs (user_id, user_name, user_email, event_type, message, ip_address, user_agent, context_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            isset($user['id']) ? (int)$user['id'] : null,
            $user['name'] ?? null,
            $user['email'] ?? null,
            $eventType,
            $message,
            $ipAddress,
            $userAgent,
            $contextJson,
        ]);
    } catch (Throwable $ex) {
        error_log('System log failed: ' . $ex->getMessage());
    }
}

function pretty_log_context(?string $json): string
{
    if (!$json) {
        return '';
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $json;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
