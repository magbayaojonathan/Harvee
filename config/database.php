<?php
// Environment-first configuration with host-aware defaults.
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isInfinityFreeHost =
    stripos($httpHost, 'infinityfreeapp.com') !== false ||
    stripos($httpHost, 'epizy.com') !== false;

// Local defaults
$defaultHost = 'localhost';
$defaultPort = '3306';
$defaultDbName = 'harvee_marketplace';
$defaultDbUser = 'root';
$defaultDbPass = '';
$defaultCharset = 'utf8mb4';

// InfinityFree defaults (update host/password with your panel values if needed)
if ($isInfinityFreeHost) {
    $defaultHost = 'sql209.infinityfree.com';
    $defaultDbName = 'if0_41295176_harvee';
    $defaultDbUser = 'if0_41295176';
    $defaultDbPass = 'Marco01182005';
}

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
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Show real DB errors during setup; set APP_DEBUG=0 to hide details later.
    $isDebug = getenv('APP_DEBUG');
    $isDebug = ($isDebug === false) ? true : ($isDebug === '1');
    $message = $isDebug
        ? "Database connection failed: " . $e->getMessage()
        : "Database connection failed.";
    die($message);
}
