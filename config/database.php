<?php
require_once __DIR__ . '/i18n.php';
harvee_i18n_bootstrap();
try {
    $url = getenv('DATABASE_URL');
    $parts = $url ? parse_url($url) : [];
    if ($url && (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true))) {
        throw new RuntimeException('DATABASE_URL must be a PostgreSQL URL');
    }
    $host = $parts['host'] ?? (getenv('DB_HOST') ?: 'localhost');
    $port = $parts['port'] ?? (getenv('DB_PORT') ?: '5432');
    $dbname = isset($parts['path']) ? rawurldecode(ltrim($parts['path'], '/')) : (getenv('DB_NAME') ?: 'harvee_marketplace');
    $username = isset($parts['user']) ? rawurldecode($parts['user']) : (getenv('DB_USER') ?: 'postgres');
    $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : (getenv('DB_PASS') ?: '');
    parse_str($parts['query'] ?? '', $query);
    $sslmode = getenv('DB_SSLMODE') ?: ($query['sslmode'] ?? 'prefer');
    if (!in_array($sslmode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
        throw new RuntimeException('Invalid DB_SSLMODE');
    }
    foreach ([$host, $port, $dbname] as $component) {
        if (preg_match('/[;\s]/', (string)$component)) throw new RuntimeException('Invalid database connection component');
    }
    if (getenv('APP_ENV') === 'production' && !$url && (!getenv('DB_HOST') || !getenv('DB_USER') || !getenv('DB_NAME') || !getenv('DB_PASS'))) {
        throw new RuntimeException('Configure DATABASE_URL or DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS');
    }
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode};connect_timeout=10", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (PHP_SAPI === 'cli') exit(1);
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}
