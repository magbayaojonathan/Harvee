<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? ($_SESSION['name'] ?? 'Farmer');

$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';

$stats = [
    'lifetime' => 0,
    'this_month' => 0,
    'last_month' => 0,
    'delivered_orders' => 0,
    'average_order' => 0
];

$monthly_rows = [];
$recent_earnings = [];

try {
    $where = " WHERE oi.farmer_id = ? AND o.order_status = 'delivered' ";
    $params = [$user_id];

    if ($date_from !== '') {
        $where .= " AND DATE(o.created_at) >= ? ";
        $params[] = $date_from;
    }
    if ($date_to !== '') {
        $where .= " AND DATE(o.created_at) <= ? ";
        $params[] = $date_to;
    }

    $lifetime_stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(oi.total_price), 0) AS lifetime,
            COUNT(DISTINCT o.id) AS delivered_orders
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        $where
    ");
    $lifetime_stmt->execute($params);
    $lifetime = $lifetime_stmt->fetch(PDO::FETCH_ASSOC);

    $stats['lifetime'] = (float)($lifetime['lifetime'] ?? 0);
    $stats['delivered_orders'] = (int)($lifetime['delivered_orders'] ?? 0);
    $stats['average_order'] = $stats['delivered_orders'] > 0
        ? $stats['lifetime'] / $stats['delivered_orders']
        : 0;

    $month_stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN DATE_FORMAT(o.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN oi.total_price ELSE 0 END) AS this_month,
            SUM(CASE WHEN DATE_FORMAT(o.created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') THEN oi.total_price ELSE 0 END) AS last_month
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.farmer_id = ? AND o.order_status = 'delivered'
    ");
    $month_stmt->execute([$user_id]);
    $month = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $stats['this_month'] = (float)($month['this_month'] ?? 0);
    $stats['last_month'] = (float)($month['last_month'] ?? 0);

    $monthly_stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(o.created_at, '%Y-%m') AS month_key,
            DATE_FORMAT(o.created_at, '%b %Y') AS month_label,
            COALESCE(SUM(oi.total_price), 0) AS amount
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        $where
        GROUP BY DATE_FORMAT(o.created_at, '%Y-%m'), DATE_FORMAT(o.created_at, '%b %Y')
        ORDER BY month_key DESC
        LIMIT 12
    ");
    $monthly_stmt->execute($params);
    $monthly_rows = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

    $recent_stmt = $pdo->prepare("
        SELECT
            o.id,
            o.order_number,
            o.created_at,
            COALESCE(SUM(oi.total_price), 0) AS amount,
            COUNT(oi.id) AS item_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        $where
        GROUP BY o.id, o.order_number, o.created_at
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recent_stmt->execute($params);
    $recent_earnings = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Farmer earnings page error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Harvee Farm</title>
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
        .gradient-text {
            background: linear-gradient(135deg, #10854d 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        .hero {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
        }
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
                    <a href="orders.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-shopping-bag mr-1"></i> Orders</a>
                    <a href="earnings.php" class="nav-link active text-gray-700 font-medium"><i class="fas fa-chart-line mr-1"></i> Earnings</a>
                </div>

                <div class="flex items-center space-x-3">
                    <span class="hidden md:inline text-sm text-gray-600">Hi, <?php echo htmlspecialchars($first_name); ?></span>
                    <a href="../auth/logout.php" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="hero rounded-2xl p-8 mb-8 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Earnings Overview</h1>
            <p class="text-white/90 text-lg">Track your farm revenue and monthly performance.</p>
        </div>

        <div class="dashboard-card rounded-xl p-4 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row md:items-end gap-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">From</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($date_from); ?>" class="border border-gray-300 rounded-lg px-3 py-2 focus:border-[#10854d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">To</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($date_to); ?>" class="border border-gray-300 rounded-lg px-3 py-2 focus:border-[#10854d] focus:outline-none">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">Apply</button>
                    <?php if ($date_from !== '' || $date_to !== ''): ?>
                        <a href="earnings.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Lifetime Revenue</p>
                <p class="text-2xl font-bold text-[#10854d]">&#8369;<?php echo number_format($stats['lifetime'], 2); ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">This Month</p>
                <p class="text-2xl font-bold text-green-600">&#8369;<?php echo number_format($stats['this_month'], 2); ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Last Month</p>
                <p class="text-2xl font-bold text-blue-600">&#8369;<?php echo number_format($stats['last_month'], 2); ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Delivered Orders</p>
                <p class="text-2xl font-bold text-indigo-600"><?php echo $stats['delivered_orders']; ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Average / Order</p>
                <p class="text-2xl font-bold text-gray-800">&#8369;<?php echo number_format($stats['average_order'], 2); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="dashboard-card rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Monthly Earnings (Last 12 Months)</h2>
                </div>
                <?php if (empty($monthly_rows)): ?>
                    <div class="p-10 text-center text-gray-500">No earnings data found.</div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($monthly_rows as $row): ?>
                            <div class="px-6 py-4 flex items-center justify-between">
                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($row['month_label']); ?></span>
                                <span class="font-bold text-[#10854d]">&#8369;<?php echo number_format((float)$row['amount'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Recent Earnings</h2>
                </div>
                <?php if (empty($recent_earnings)): ?>
                    <div class="p-10 text-center text-gray-500">No delivered orders yet.</div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($recent_earnings as $earning): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($earning['order_number'] ?: ('ORD-' . str_pad((string)$earning['id'], 6, '0', STR_PAD_LEFT))); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M d, Y h:i A', strtotime($earning['created_at'])); ?> | <?php echo (int)$earning['item_count']; ?> item(s)
                                        </p>
                                    </div>
                                    <div class="font-bold text-[#10854d]">
                                        &#8369;<?php echo number_format((float)$earning['amount'], 2); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
