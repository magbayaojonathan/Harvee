<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
try {
    $pdo->query('SELECT pg_advisory_lock(724031)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS harvee_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    $version = '001_postgresql';
    $check = $pdo->prepare('SELECT version FROM harvee_migrations WHERE version = ?');
    $check->execute([$version]);
    if (!$check->fetchColumn()) {
        $pdo->exec(file_get_contents(__DIR__ . '/../database/postgresql.sql'));
        $stmt = $pdo->prepare('INSERT INTO harvee_migrations (version) VALUES (?)');
        $stmt->execute([$version]);
    }
    $pdo->query('SELECT pg_advisory_unlock(724031)');
    echo "Database schema ready.\n";
} catch (Throwable $e) {
    error_log('Schema initialization failed: ' . $e->getMessage());
    exit(1);
}
