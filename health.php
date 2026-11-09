<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
try {
    $pdo->query('SELECT id FROM users LIMIT 1');
    $pdo->query('SELECT shipping_address, subtotal, delivery_driver_id FROM orders LIMIT 1');
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    error_log('Health check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'unavailable']);
}
