<?php
session_start();
require_once '../config/database.php';
require_once '../shared/demo_data.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? ($_SESSION['name'] ?? 'Farmer');
$last_name = $_SESSION['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);
$search = trim($_GET['search'] ?? '');

$stats = [
    'total_customers' => 0,
    'repeat_customers' => 0,
    'total_orders' => 0,
    'total_sales' => 0
];

$customers = [];
$using_demo_customers = false;

try {
    $stats_stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT o.customer_id) AS total_customers,
            COUNT(DISTINCT CASE WHEN oc.order_count > 1 THEN oc.customer_id END) AS repeat_customers,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(oi.total_price), 0) AS total_sales
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN (
            SELECT o2.customer_id, COUNT(DISTINCT o2.id) AS order_count
            FROM orders o2
            JOIN order_items oi2 ON o2.id = oi2.order_id
            WHERE oi2.farmer_id = ?
            GROUP BY o2.customer_id
        ) oc ON oc.customer_id = o.customer_id
        WHERE oi.farmer_id = ?
    ");
    $stats_stmt->execute([$user_id, $user_id]);
    $stats_row = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats_row) {
        $stats['total_customers'] = (int)($stats_row['total_customers'] ?? 0);
        $stats['repeat_customers'] = (int)($stats_row['repeat_customers'] ?? 0);
        $stats['total_orders'] = (int)($stats_row['total_orders'] ?? 0);
        $stats['total_sales'] = (float)($stats_row['total_sales'] ?? 0);
    }

    $sql = "
        SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.name,
            u.email,
            u.phone,
            COUNT(DISTINCT o.id) AS order_count,
            COALESCE(SUM(oi.total_price), 0) AS total_spent,
            MAX(o.created_at) AS last_order_date
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.customer_id = u.id
        WHERE oi.farmer_id = ?
    ";
    $params = [$user_id];

    if ($search !== '') {
        $sql .= " AND (
            u.first_name LIKE ? OR
            u.last_name LIKE ? OR
            u.name LIKE ? OR
            u.email LIKE ? OR
            u.phone LIKE ?
        )";
        $term = '%' . $search . '%';
        array_push($params, $term, $term, $term, $term, $term);
    }

    $sql .= "
        GROUP BY u.id, u.first_name, u.last_name, u.name, u.email, u.phone
        ORDER BY total_spent DESC, last_order_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Farmer customers page error: ' . $e->getMessage());
}

// Demo fallback (only when there are zero customers and no search filter)
if (empty($customers) && $search === '' && (int)($stats['total_customers'] ?? 0) === 0) {
    $customers = harvee_demo_farmer_customers();
    $using_demo_customers = true;

    $total_customers = count($customers);
    $repeat_customers = 0;
    $total_orders = 0;
    $total_sales = 0.0;

    foreach ($customers as $c) {
        $order_count = (int)($c['order_count'] ?? 0);
        $total_orders += $order_count;
        if ($order_count > 1) {
            $repeat_customers++;
        }
        $total_sales += (float)($c['total_spent'] ?? 0);
    }

    $stats['total_customers'] = $total_customers;
    $stats['repeat_customers'] = $repeat_customers;
    $stats['total_orders'] = $total_orders;
    $stats['total_sales'] = $total_sales;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Customers - Harvee Farm</title>
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
    </style>
</head>
<body class="min-h-screen">
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="hero rounded-2xl p-8 mb-8 text-white">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Customer Insights</h1>
            <p class="text-white/90 text-lg">See who buys from your farm and track customer value.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Total Customers</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_customers']; ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Repeat Customers</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $stats['repeat_customers']; ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Total Orders</p>
                <p class="text-2xl font-bold text-indigo-600"><?php echo $stats['total_orders']; ?></p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-gray-500 text-sm">Sales from Customers</p>
                <p class="text-2xl font-bold text-[#10854d]">&#8369;<?php echo number_format($stats['total_sales'], 2); ?></p>
            </div>
        </div>

        <div class="dashboard-card rounded-xl p-4 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
                <div class="relative w-full md:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by name, email, or phone"
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                    >
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                        Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="customers.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="dashboard-card rounded-xl overflow-hidden">
            <?php if (empty($customers)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-users text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600 font-medium">No customers found.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Customer</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Contact</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Orders</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Total Spent</th>
                                <th class="text-left text-sm font-semibold text-gray-700 px-6 py-4">Last Order</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($customers as $customer): ?>
                                <?php
                                $name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                                if ($name === '') {
                                    $name = trim((string)($customer['name'] ?? 'Customer'));
                                }
                                $is_demo = !empty($customer['is_demo']);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-green-100 text-[#10854d] rounded-full flex items-center justify-center font-bold">
                                                <?php echo strtoupper(substr($name, 0, 1)); ?>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($name); ?></span>
                                                <?php if ($is_demo): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600"></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div><?php echo htmlspecialchars($customer['email'] ?? 'No email'); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($customer['phone'] ?? 'No phone'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo (int)$customer['order_count']; ?></td>
                                    <td class="px-6 py-4 font-bold text-[#10854d]">&#8369;<?php echo number_format((float)$customer['total_spent'], 2); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $customer['last_order_date'] ? date('M d, Y h:i A', strtotime($customer['last_order_date'])) : 'N/A'; ?>
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
