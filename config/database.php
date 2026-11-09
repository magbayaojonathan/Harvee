<?php
require_once __DIR__ . '/i18n.php';
harvee_i18n_bootstrap();

// Credentials are configured through the hosting environment.
$defaultHost = 'localhost';
$defaultPort = '3306';
$defaultDbName = 'harvee_marketplace';
$defaultDbUser = 'root';
$defaultDbPass = '';
$defaultCharset = 'utf8mb4';

$host = getenv('DB_HOST') ?: $defaultHost;
$port = getenv('DB_PORT') ?: $defaultPort;
$dbname = getenv('DB_NAME') ?: $defaultDbName;
$username = getenv('DB_USER') ?: $defaultDbUser;
$password = getenv('DB_PASS') ?: $defaultDbPass;
$charset = getenv('DB_CHARSET') ?: $defaultCharset;

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    if (getenv('APP_ENV') === 'production') {
        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            if (getenv($key) === false || getenv($key) === '') {
                throw new PDOException('Missing database configuration: ' . $key);
            }
        }
    }
    if ($ca = getenv('DB_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die(getenv('APP_DEBUG') === '1' && getenv('APP_ENV') !== 'production'
        ? 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Service temporarily unavailable. Please try again later.');
}
