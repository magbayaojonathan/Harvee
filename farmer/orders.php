<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? ($_SESSION['name'] ?? 'Farmer');
$last_name = $_SESSION['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);
$message = '';
$message_type = '';

$valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
$farmer_updatable_status = 'confirmed';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    if ($order_id <= 0 || $new_status !== $farmer_updatable_status) {
        $message = 'Invalid request.';
        $message_type = 'error';
    } else {
        try {
            // Ensure the farmer owns at least one item in this order
            $check_stmt = $pdo->prepare("
                SELECT o.order_status, COUNT(*) AS item_count
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.order_id = ? AND oi.farmer_id = ?
                GROUP BY o.order_status
            ");
            $check_stmt->execute([$order_id, $user_id]);
            $owned_order = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $owned_items = (int)($owned_order['item_count'] ?? 0);

            if ($owned_items === 0) {
                $message = 'You do not have permission to update this order.';
                $message_type = 'error';
            } elseif (($owned_order['order_status'] ?? '') !== 'pending') {
                $message = 'Only pending orders can be confirmed by farmers.';
                $message_type = 'error';
            } else {
                $update_stmt = $pdo->prepare("UPDATE orders SET order_status = 'confirmed' WHERE id = ? AND order_status = 'pending'");
                $update_stmt->execute([$order_id]);

                if ($update_stmt->rowCount() > 0) {
                    $track_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, updated_by) VALUES (?, 'confirmed', ?, ?)");
                    $track_stmt->execute([$order_id, 'Order confirmed by farmer and ready for driver assignment.', $user_id]);
                }

                $message = $update_stmt->rowCount() > 0
                    ? 'Order confirmed successfully. Drivers can now claim this delivery.'
                    : 'Order was already updated.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Failed to update order status.';
            $message_type = 'error';
            error_log('Farmer orders update error: ' . $e->getMessage());
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
if ($status_filter !== 'all' && !in_array($status_filter, $valid_statuses, true)) {
    $status_filter = 'all';
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0,
    'revenue' => 0
];

$orders = [];

try {
    // Stats
    $stats_stmt = $pdo->prepare("
        SELECT o.order_status, COALESCE(SUM(oi.total_price), 0) AS amount
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.farmer_id = ?
        GROUP BY o.id, o.order_status
    ");
    $stats_stmt->execute([$user_id]);
    $stat_rows = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stat_rows as $row) {
        $status = $row['order_status'] ?? '';
        $amount = (float)($row['amount'] ?? 0);
        $stats['total']++;
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        if ($status === 'delivered') {
            $stats['revenue'] += $amount;
        }
    }

    // Orders list
    $sql = "
        SELECT o.id, o.order_number, o.order_status, o.payment_status, o.created_at,
               u.first_name, u.last_name,
               COUNT(oi.id) AS item_count,
               COALESCE(SUM(oi.total_price), 0) AS farmer_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.customer_id = u.id
        WHERE oi.farmer_id = ?
    ";
    $params = [$user_id];

    if ($status_filter !== 'all') {
        $sql .= " AND o.order_status = ?";
        $params[] = $status_filter;
    }

    $sql .= "
        GROUP BY o.id, o.order_number, o.order_status, o.payment_status, o.created_at, u.first_name, u.last_name
        ORDER BY o.created_at DESC
    ";

    $orders_stmt = $pdo->prepare($sql);
    $orders_stmt->execute($params);
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Failed to load orders.';
    $message_type = 'error';
    error_log('Farmer orders load error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Orders - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%);
        }
        .dashboard-card {
            transition: all 0.3s ease;
            background: white;
            border: 1px solid rgba(16, 133, 77, 0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(16, 133, 77, 0.08), 0 10px 10px -5px rgba(16, 133, 77, 0.03);
            border-color: rgba(16, 133, 77, 0.3);
        }
        .nav-link {
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #10854d;
        }
        .nav-link.active {
            color: #10854d;
            border-bottom: 2px solid #10854d;
        }
        .gradient-text {
            background: linear-gradient(135deg, #10854d 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
        }
        .status-badge {
            padding: 0.2rem 0.7rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-shipped { background: #c7d2fe; color: #3730a3; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled, .status-refunded { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">H</span>
                    </div>
                    <span class="text-xl font-bold gradient-text">Harvee Farm</span>
                </a>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-home mr-1"></i> Dashboard</a>
                    <a href="products/products.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-box mr-1"></i> Products</a>
                    <a href="orders.php" class="nav-link active text-gray-700 font-medium"><i class="fas fa-shopping-bag mr-1"></i> Orders</a>
                    <a href="reviews.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-star mr-1"></i> Reviews</a>
                    <a href="profile.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-user mr-1"></i> Profile</a>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="products/add.php" class="hidden md:inline-flex items-center px-4 py-2 bg-[#10854d] text-white text-sm font-medium rounded-lg hover:bg-[#0d6e40] transition-all shadow-md">
                        <i class="fas fa-plus mr-2"></i> Add Product
                    </a>
                    <a href="../auth/logout.php" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="hero rounded-2xl p-8 mb-8 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Orders Management</h1>
            <p class="text-white/90 text-lg">Confirm new orders so drivers can claim and deliver them, <?php echo htmlspecialchars($first_name); ?>.</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Total</p><p class="text-2xl font-bold"><?php echo $stats['total']; ?></p></div>
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Pending</p><p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p></div>
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Processing</p><p class="text-2xl font-bold text-sky-600"><?php echo $stats['processing']; ?></p></div>
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Shipped</p><p class="text-2xl font-bold text-indigo-600"><?php echo $stats['shipped']; ?></p></div>
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Delivered</p><p class="text-2xl font-bold text-green-600"><?php echo $stats['delivered']; ?></p></div>
            <div class="dashboard-card rounded-xl p-4"><p class="text-gray-500 text-sm">Revenue</p><p class="text-2xl font-bold text-[#10854d]">&#8369;<?php echo number_format($stats['revenue'], 2); ?></p></div>
        </div>

        <div class="dashboard-card rounded-xl p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <?php
                    $filters = ['all', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
                    foreach ($filters as $f):
                        $active = $status_filter === $f;
                    ?>
                        <a href="?status=<?php echo urlencode($f); ?>" class="px-4 py-2 rounded-full text-sm font-medium <?php echo $active ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <?php echo ucfirst($f); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="text-sm text-gray-500">Showing <?php echo count($orders); ?> order(s)</div>
            </div>
        </div>

        <div class="dashboard-card rounded-xl overflow-hidden">
            <?php if (empty($orders)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-shopping-bag text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600 font-medium">No orders found for this filter.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Order</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Customer</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Items</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Amount</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Status</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['order_number'] ?: ('ORD-' . str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT))); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: 'Customer'); ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo (int)$order['item_count']; ?></td>
                                    <td class="px-6 py-4 font-bold text-[#10854d]">&#8369;<?php echo number_format((float)$order['farmer_total'], 2); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['order_status']); ?>">
                                            <?php echo htmlspecialchars($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (($order['order_status'] ?? '') === 'pending'): ?>
                                            <form method="POST" action="" class="flex items-center gap-2">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                                <input type="hidden" name="new_status" value="confirmed">
                                                <button type="submit" name="update_status" class="px-3 py-1.5 bg-[#10854d] text-white text-xs font-medium rounded-lg hover:bg-[#0d6e40]">
                                                    Confirm Order
                                                </button>
                                            </form>
                                        <?php elseif (($order['order_status'] ?? '') === 'confirmed'): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-blue-50 text-blue-700">
                                                Waiting for Driver
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600">
                                                Managed by Driver
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
