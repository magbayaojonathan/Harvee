<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'Farmer';
$last_name = $_SESSION['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);

// Get farmer statistics
$stats = [
    'total_products' => 0,
    'total_orders' => 0,
    'total_revenue' => 0,
    'pending_orders' => 0,
    'low_stock' => 0,
    'total_customers' => 0
];

$recent_orders = [];
$low_stock_products = [];
$recent_products = [];

try {
    // Get total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE farmer_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_products'] = $stmt->fetch()['count'] ?? 0;
    
    // Get total orders and revenue
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as order_count, 
               COALESCE(SUM(oi.total_price), 0) as revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.farmer_id = ? AND o.order_status = 'delivered'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['total_orders'] = $result['order_count'] ?? 0;
    $stats['total_revenue'] = $result['revenue'] ?? 0;
    
    // Get pending orders
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.farmer_id = ? AND o.order_status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $stats['pending_orders'] = $stmt->fetch()['count'] ?? 0;
    
    // Get low stock products (stock <= 10)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE farmer_id = ? AND stock_quantity <= 10 AND stock_quantity > 0
    ");
    $stmt->execute([$user_id]);
    $stats['low_stock'] = $stmt->fetch()['count'] ?? 0;
    
    // Get total unique customers
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.customer_id) as count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.farmer_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats['total_customers'] = $stmt->fetch()['count'] ?? 0;
    
    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.total, o.order_status, o.created_at,
               u.first_name, u.last_name,
               COUNT(oi.id) as item_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.customer_id = u.id
        WHERE oi.farmer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll();
    
    // Get low stock products list
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock_quantity, unit
        FROM products
        WHERE farmer_id = ? AND stock_quantity <= 10
        ORDER BY stock_quantity ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $low_stock_products = $stmt->fetchAll();
    
    // Get recent products
    $stmt = $pdo->prepare("
        SELECT id, name, price, stock_quantity, created_at
        FROM products
        WHERE farmer_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - Harvee Farm</title>
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
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(16, 133, 77, 0.1), 0 10px 10px -5px rgba(16, 133, 77, 0.04);
            border-color: rgba(16, 133, 77, 0.3);
        }
        .stat-card {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '🌱🌽🥕';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 80px;
            opacity: 0.1;
            transform: rotate(-10deg);
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
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
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
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
    </style>
</head>
<body class="min-h-screen">
    <!-- Main Navigation -->
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">H</span>
                    </div>
                    <span class="text-xl font-bold gradient-text">Harvee Farm</span>
                </a>
                
                <!-- Main Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="nav-link text-gray-700 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="products/products.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">
                        <i class="fas fa-box mr-1"></i> Products
                    </a>
                    <a href="orders.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">
                        <i class="fas fa-shopping-bag mr-1"></i> Orders
                    </a>
                    <a href="reviews.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">
                        <i class="fas fa-star mr-1"></i> Reviews
                    </a>
                    <a href="customers.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">
                        <i class="fas fa-users mr-1"></i> Customers
                    </a>
                </div>

                <!-- Right Section - Profile Only -->
                <div class="flex items-center space-x-3">
                    <!-- Add Product Button -->
                    <a href="products/add.php" class="hidden md:inline-flex items-center px-4 py-2 bg-[#10854d] text-white text-sm font-medium rounded-lg hover:bg-[#0d6e40] transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i> Add Product
                    </a>
                    
                    <!-- Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-1.5 hover:bg-gray-100 rounded-full transition-all">
                            <div class="w-9 h-9 bg-[#10854d] rounded-full flex items-center justify-center shadow-md">
                                <span class="text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium">
                                <?php echo htmlspecialchars($first_name); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100" style="display: none;">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($full_name); ?></p>
                                <p class="text-xs text-gray-500">Farmer</p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#10854d]">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#10854d]">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Alpine.js for dropdown -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <main class="container mx-auto px-4 py-8">
        <!-- Welcome Banner -->
        <div class="stat-card rounded-2xl p-8 mb-8 text-white relative overflow-hidden">
            <div class="relative z-10">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">
                        Welcome back, Farmer <?php echo htmlspecialchars($first_name); ?>! 🌱
                    </h1>
                    <p class="text-white/90 text-lg max-w-2xl">
                        Manage your farm products, track orders, and grow your business with Harvee.
                    </p>
                </div>
                
                <!-- Quick Stats Row -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-8">
                    
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="dashboard-card bg-white rounded-xl p-6 fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Products</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_products']; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Active in your store</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-2xl text-[#10854d]"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="products/products.php" class="text-sm text-[#10854d] hover:underline">View all products →</a>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-xl p-6 fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Orders</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_orders']; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Completed orders</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-2xl text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="orders.php" class="text-sm text-blue-600 hover:underline">View all orders →</a>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-xl p-6 fade-in" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Revenue</p>
                        <p class="text-3xl font-bold text-gray-800">₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        <p class="text-xs text-gray-400 mt-1">Lifetime earnings</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-peso-sign text-2xl text-[#10854d]"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="earnings.php" class="text-sm text-[#10854d] hover:underline">View earnings →</a>
                </div>
            </div>

            <div class="dashboard-card bg-white rounded-xl p-6 fade-in" style="animation-delay: 0.4s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Low Stock Alert</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $stats['low_stock']; ?></p>
                        <p class="text-xs text-gray-400 mt-1">Products need restock</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="products/products.php?stock=low" class="text-sm text-red-600 hover:underline">View low stock →</a>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Recent Orders -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Orders Card -->
                <div class="dashboard-card bg-white rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-history text-[#10854d] mr-2"></i>
                            Recent Orders
                        </h2>
                        <a href="orders.php" class="text-sm text-[#10854d] hover:underline flex items-center">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-shopping-bag text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-gray-500">No orders yet</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-[#10854d]/10 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-receipt text-[#10854d]"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800"><?php echo $order['order_number']; ?></p>
                                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                                <span><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                                                <span>•</span>
                                                <span><?php echo $order['item_count']; ?> items</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-[#10854d]">₱<?php echo number_format($order['total'], 2); ?></p>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Products -->
                <div class="dashboard-card bg-white rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-box text-[#10854d] mr-2"></i>
                            Recently Added Products
                        </h2>
                        <a href="products/products.php" class="text-sm text-[#10854d] hover:underline flex items-center">
                            View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($recent_products)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-box text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-gray-500 mb-3">No products added yet</p>
                            <a href="products/add.php" class="inline-flex items-center px-4 py-2 bg-[#10854d] text-white text-sm rounded-lg hover:bg-[#0d6e40] transition-colors">
                                <i class="fas fa-plus mr-2"></i> Add Your First Product
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($recent_products as $product): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div>
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="text-sm text-gray-500">₱<?php echo number_format($product['price'], 2); ?> • <?php echo $product['stock_quantity']; ?> in stock</p>
                                    </div>
                                    <a href="products/edit.php?id=<?php echo $product['id']; ?>" class="text-[#10854d] hover:text-[#0d6e40]">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Low Stock Alert Card -->
                <div class="dashboard-card bg-white rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                        Low Stock Alert
                    </h2>
                    
                    <?php if (empty($low_stock_products)): ?>
                        <div class="text-center py-6">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-check text-[#10854d]"></i>
                            </div>
                            <p class="text-gray-600">All products have sufficient stock</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                    <div>
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="text-sm">
                                            <span class="text-red-600 font-bold"><?php echo $product['stock_quantity']; ?> left</span>
                                            <span class="text-gray-500"> • <?php echo $product['unit']; ?></span>
                                        </p>
                                    </div>
                                    <a href="products/edit.php?id=<?php echo $product['id']; ?>" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700">
                                        Restock
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($stats['low_stock'] > 5): ?>
                            <div class="mt-4 text-center">
                                <a href="products/products.php?stock=low" class="text-sm text-red-600 hover:underline">
                                    View all <?php echo $stats['low_stock']; ?> low stock items →
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions Card -->
                <div class="dashboard-card bg-white rounded-xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-bolt text-[#10854d] mr-2"></i>
                        Quick Actions
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <a href="products/add.php" class="p-4 bg-green-50 rounded-lg text-center hover:bg-green-100 transition-all group">
                            <i class="fas fa-plus-circle text-2xl text-[#10854d] mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Add Product</span>
                        </a>
                        
                        <a href="products/products.php" class="p-4 bg-blue-50 rounded-lg text-center hover:bg-blue-100 transition-all group">
                            <i class="fas fa-boxes text-2xl text-blue-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Manage Products</span>
                        </a>
                        
                        <a href="orders.php?status=pending" class="p-4 bg-yellow-50 rounded-lg text-center hover:bg-yellow-100 transition-all group">
                            <i class="fas fa-clock text-2xl text-yellow-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Pending Orders</span>
                            <?php if ($stats['pending_orders'] > 0): ?>
                                <span class="text-xs text-yellow-600 font-semibold"><?php echo $stats['pending_orders']; ?> orders</span>
                            <?php endif; ?>
                        </a>
                        <a href="reviews.php" class="p-4 bg-amber-50 rounded-lg text-center hover:bg-amber-100 transition-all group">
                            <i class="fas fa-star text-2xl text-amber-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Reviews</span>
                        </a>
                        
                        <a href="earnings.php" class="p-4 bg-purple-50 rounded-lg text-center hover:bg-purple-100 transition-all group">
                            <i class="fas fa-chart-line text-2xl text-purple-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Earnings</span>
                        </a>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="dashboard-card bg-gradient-to-br from-[#10854d] to-[#059669] rounded-xl p-6 text-white">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-lightbulb text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg">Farmer Tips</h3>
                    </div>
                    <ul class="space-y-3 text-sm text-white/90">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-0.5 mr-2 text-white"></i>
                            <span>Keep your products updated with fresh photos</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-0.5 mr-2 text-white"></i>
                            <span>Respond to customer inquiries quickly</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-0.5 mr-2 text-white"></i>
                            <span>Monitor low stock items to avoid missed sales</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mt-0.5 mr-2 text-white"></i>
                            <span>Check pending orders daily</span>
                        </li>
                    </ul>
                    <div class="mt-6 pt-4 border-t border-white/20">
                        <a href="help.php" class="text-sm text-white hover:underline flex items-center">
                            <i class="fas fa-question-circle mr-2"></i>
                            Need more help? Visit Farmer Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white/90 backdrop-blur-sm mt-12 py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-xl">H</span>
                        </div>
                        <span class="text-xl font-bold gradient-text">Harvee Farm</span>
                    </div>
                    <p class="text-gray-600 text-sm">Empowering farmers to reach more customers with fresh, quality products.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="dashboard.php" class="text-gray-600 hover:text-[#10854d]">Dashboard</a></li>
                        <li><a href="products/products.php" class="text-gray-600 hover:text-[#10854d]">My Products</a></li>
                        <li><a href="orders.php" class="text-gray-600 hover:text-[#10854d]">Orders</a></li>
                        <li><a href="earnings.php" class="text-gray-600 hover:text-[#10854d]">Earnings</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="help.php" class="text-gray-600 hover:text-[#10854d]">Help Center</a></li>
                        <li><a href="faq.php" class="text-gray-600 hover:text-[#10854d]">FAQ</a></li>
                        <li><a href="contact.php" class="text-gray-600 hover:text-[#10854d]">Contact Us</a></li>
                        <li><a href="terms.php" class="text-gray-600 hover:text-[#10854d]">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Contact</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center"><i class="fas fa-phone mr-2 text-[#10854d]"></i> +63 (123) 456-7890</li>
                        <li class="flex items-center"><i class="fas fa-envelope mr-2 text-[#10854d]"></i> farmer@harvee.com</li>
                        <li class="flex items-center"><i class="fas fa-map-marker-alt mr-2 text-[#10854d]"></i> Farming District, Philippines</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 mt-8 pt-8 text-center">
                <p class="text-gray-500 text-sm">© <?php echo date('Y'); ?> Harvee Farm. All rights reserved. Growing together with our farmers.</p>
            </div>
        </div>
    </footer>
</body>
</html>
