<?php
session_start();
// Check if user is logged in as customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../shared/header.php';

// Get customer name from session
$first_name = $_SESSION['first_name'] ?? 'Customer';
$last_name = $_SESSION['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);

// Get cart count
require_once '../config/database.php';
$user_id = $_SESSION['user_id'];
$cart_count = 0;
$order_count = 0;
$recent_orders = [];
$total_spent = 0;
$pending_orders = 0;
$wishlist_count = 0;

try {
    // Get cart count
    $cartStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cart_count = $cartStmt->fetch()['total'] ?? 0;
    
    // Get order count and total spent
    $orderStmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as spent FROM orders WHERE customer_id = ?");
    $orderStmt->execute([$user_id]);
    $orderData = $orderStmt->fetch();
    $order_count = $orderData['total'] ?? 0;
    $total_spent = $orderData['spent'] ?? 0;
    
    // Get pending orders count
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND order_status = 'pending'");
    $pendingStmt->execute([$user_id]);
    $pending_orders = $pendingStmt->fetch()['total'] ?? 0;
    
    // Get wishlist count
    $wishlistStmt = $pdo->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $wishlistStmt->execute([$user_id]);
    $wishlist_count = $wishlistStmt->fetch()['total'] ?? 0;
    
    // Get recent orders with items
    $recentStmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               u.first_name as seller_name
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN users u ON o.customer_id = u.id
        WHERE o.customer_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentStmt->execute([$user_id]);
    $recent_orders = $recentStmt->fetchAll();
    
    // Get recommended products based on past orders
    $recommendedStmt = $pdo->prepare("
        SELECT p.*, 
               u.first_name as seller_name,
               AVG(r.rating) as avg_rating
        FROM products p 
        LEFT JOIN users u ON p.farmer_id = u.id
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.is_active = TRUE 
        GROUP BY p.id 
        ORDER BY RAND() 
        LIMIT 4
    ");
    $recommendedStmt->execute();
    $recommended_products = $recommendedStmt->fetchAll();
    
} catch (PDOException $e) {
    // Tables might not exist yet
    $recommended_products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Harvee Market</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%);
        }
        .dashboard-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(16, 133, 77, 0.1);
        }
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(16, 133, 77, 0.1), 0 10px 10px -5px rgba(16, 133, 77, 0.04);
            border-color: rgba(16, 133, 77, 0.3);
        }
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::after {
            content: '🍎🌽🥕';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 120px;
            opacity: 0.1;
            transform: rotate(-10deg);
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: scale(1.02);
        }
        .gradient-text {
            background: linear-gradient(135deg, #10854d, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #e0f2fe; color: #0369a1; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        /* Main navigation styling */
        .main-nav a {
            transition: all 0.3s ease;
        }
        .main-nav a:hover {
            color: #10854d;
        }
        .main-nav a.active {
            color: #10854d;
            font-weight: 600;
            border-bottom: 2px solid #10854d;
        }
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Main Navigation - Home, Shopping, Cart, Orders, Dashboard -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-8 h-8 object-contain">
                    <span class="text-xl font-bold gradient-text">Harvee</span>
                </a>
                
                <!-- Main Navigation Links -->
                <div class="hidden md:flex items-center space-x-8 main-nav">
                    <a href="dashboard.php" class="text-gray-700 font-medium hover:text-[#10854d] transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="browse.php" class="text-gray-700 font-medium hover:text-[#10854d] transition-colors">
                        <i class="fas fa-search mr-1"></i> Shopping
                    </a>
                    <a href="cart.php" class="text-gray-700 font-medium hover:text-[#10854d] transition-colors relative">
                        <i class="fas fa-shopping-cart mr-1"></i> Cart
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-2 -right-4 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="orders.php" class="text-gray-700 font-medium hover:text-[#10854d] transition-colors">
                        <i class="fas fa-clipboard-list mr-1"></i> My Orders
                    </a>
                    <a href="addresses.php" class="text-gray-700 font-medium hover:text-[#10854d] transition-colors">
                        <i class="fas fa-map-marker-alt mr-1"></i> Addresses
                    </a>
                </div>

                <!-- Right Section - Profile Only -->
                <div class="flex items-center space-x-3">
                    <!-- Profile Dropdown - Only Logout -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 p-1.5 hover:bg-gray-100 rounded-full transition-all">
                            <div class="w-9 h-9 bg-gradient-to-br from-[#10854d] to-[#4ade80] rounded-full flex items-center justify-center shadow-md">
                                <span class="text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium">
                                <?php echo htmlspecialchars($first_name); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        
                        <!-- Dropdown Menu - Profile & Logout -->
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($full_name); ?></p>
                                <p class="text-xs text-gray-500">Customer</p>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="addresses.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-map-marker-alt mr-2"></i> My Addresses
                            </a>
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

    <main class="container mx-auto px-4 py-8 flex-grow">
        <!-- Welcome Banner -->
        <div class="welcome-banner rounded-2xl p-8 mb-8 text-white relative">
            <div class="relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-2">
                            Welcome back, <?php echo htmlspecialchars($first_name); ?>! 
                        </h1>
                        <p class="text-white/90 text-lg max-w-2xl">
                            Your farming marketplace for fresh, quality products. Here's what's happening with your account today.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="browse.php" class="inline-flex items-center px-6 py-3 bg-white text-[#10854d] rounded-xl font-semibold hover:bg-gray-100 transition-all shadow-lg">
                            <i class="fas fa-store mr-2"></i>
                            Start Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-2xl text-[#10854d]"></i>
                    </div>
                    <span class="text-green-600 bg-green-100 text-xs font-semibold px-2 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $order_count; ?></h3>
                <p class="text-gray-500 text-sm">Total Orders</p>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-blue-600"></i>
                    </div>
                    <span class="text-blue-600 bg-blue-100 text-xs font-semibold px-2 py-1 rounded-full">Pending</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_orders; ?></h3>
                <p class="text-gray-500 text-sm">Pending Orders</p>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-cart-shopping text-2xl text-purple-600"></i>
                    </div>
                    <span class="text-purple-600 bg-purple-100 text-xs font-semibold px-2 py-1 rounded-full">Cart</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $cart_count; ?></h3>
                <p class="text-gray-500 text-sm">Items in Cart</p>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-peso-sign text-2xl text-yellow-600"></i>
                    </div>
                    <span class="text-yellow-600 bg-yellow-100 text-xs font-semibold px-2 py-1 rounded-full">Spent</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800">₱<?php echo number_format($total_spent, 2); ?></h3>
                <p class="text-gray-500 text-sm">Total Spent</p>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Recent Orders -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Orders Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 dashboard-card">
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
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-shopping-bag text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">No orders yet</h3>
                            <p class="text-gray-500 mb-4">Start shopping to see your orders here</p>
                            <a href="browse.php" class="inline-flex items-center px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40] transition-colors">
                                <i class="fas fa-store mr-2"></i>
                                Browse Products
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-[#10854d]/10 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-receipt text-[#10854d]"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></p>
                                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                                <span><i class="far fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                                <span>•</span>
                                                <span><?php echo $order['item_count']; ?> items</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-[#10854d]">₱<?php echo number_format($order['total'] ?? 0, 2); ?></p>
                                        <span class="status-badge status-<?php echo $order['order_status'] ?? 'pending'; ?>">
                                            <?php echo ucfirst($order['order_status'] ?? 'Pending'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recommended Products -->
                <div class="bg-white rounded-xl shadow-sm p-6 dashboard-card">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-thumbs-up text-[#10854d] mr-2"></i>
                            Recommended for You
                        </h2>
                        <a href="browse.php" class="text-sm text-[#10854d] hover:underline">View More</a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($recommended_products as $product): ?>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-all">
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <i class="fas fa-box text-2xl text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="text-sm text-gray-500 mb-1"><?php echo htmlspecialchars($product['seller_name']); ?></p>
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-[#10854d]">₱<?php echo number_format($product['price'], 2); ?></span>
                                            <?php if (!empty($product['avg_rating'])): ?>
                                                <div class="flex items-center text-yellow-400 text-xs">
                                                    <i class="fas fa-star"></i>
                                                    <span class="ml-1 text-gray-600"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Quick Actions & Categories -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 dashboard-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-bolt text-[#10854d] mr-2"></i>
                        Quick Actions
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <a href="browse.php" class="p-4 bg-green-50 rounded-xl text-center hover:bg-green-100 transition-all group">
                            <i class="fas fa-search text-2xl text-green-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Browse</span>
                        </a>
                        
                        <a href="cart.php" class="p-4 bg-blue-50 rounded-xl text-center hover:bg-blue-100 transition-all group">
                            <i class="fas fa-shopping-cart text-2xl text-blue-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Cart</span>
                            <?php if ($cart_count > 0): ?>
                                <span class="text-xs text-blue-600 font-semibold"><?php echo $cart_count; ?> items</span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="orders.php" class="p-4 bg-purple-50 rounded-xl text-center hover:bg-purple-100 transition-all group">
                            <i class="fas fa-truck text-2xl text-purple-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Track</span>
                        </a>
                        
                        <a href="wishlist.php" class="p-4 bg-pink-50 rounded-xl text-center hover:bg-pink-100 transition-all group">
                            <i class="fas fa-heart text-2xl text-pink-600 mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="block text-sm font-medium text-gray-700">Wishlist</span>
                            <?php if ($wishlist_count > 0): ?>
                                <span class="text-xs text-pink-600 font-semibold"><?php echo $wishlist_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-white rounded-xl shadow-sm p-6 dashboard-card">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-tags text-[#10854d] mr-2"></i>
                        Shop by Category
                    </h2>
                    
                    <div class="space-y-2">
                        <a href="browse.php?category=Vegetables" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-green-50 transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-carrot text-green-600"></i>
                                </div>
                                <span class="font-medium text-gray-700 group-hover:text-green-600">Vegetables</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-sm group-hover:text-green-600"></i>
                        </a>
                        
                        <a href="browse.php?category=Fruits" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-apple-alt text-orange-600"></i>
                                </div>
                                <span class="font-medium text-gray-700 group-hover:text-orange-600">Fruits</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-sm group-hover:text-orange-600"></i>
                        </a>
                        
                        <a href="browse.php?category=Grains" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-yellow-50 transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-seedling text-yellow-600"></i>
                                </div>
                                <span class="font-medium text-gray-700 group-hover:text-yellow-600">Grains</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-sm group-hover:text-yellow-600"></i>
                        </a>
                        
                        
                        <a href="browse.php?category=Dairy" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-blue-50 transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-cheese text-blue-600"></i>
                                </div>
                                <span class="font-medium text-gray-700 group-hover:text-blue-600">Dairy</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-sm group-hover:text-blue-600"></i>
                        </a>
                    </div>
                </div>

                <!-- Support Card -->
                <div class="bg-gradient-to-br from-[#10854d] to-[#059669] rounded-xl shadow-sm p-6 text-white">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-headset text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg">Need Help?</h3>
                    </div>
                    <p class="text-white/90 text-sm mb-4">
                        Our support team is ready to assist you with any questions or concerns.
                    </p>
                    <a href="contact.php" class="inline-flex items-center px-4 py-2 bg-white text-[#10854d] rounded-lg font-medium hover:bg-gray-100 transition-colors text-sm">
                        <i class="fas fa-envelope mr-2"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="footer text-white mt-12">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-8 h-8 object-contain">
                        <span class="text-xl font-bold text-white">Harvee Market</span>
                    </div>
                    <p class="text-gray-300 text-sm mb-4">
                        Your trusted online farming marketplace for fresh, quality products straight from local farmers.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-twitter text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="browse.php" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Browse Products</a></li>
                        <li><a href="cart.php" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>My Cart</a></li>
                        <li><a href="orders.php" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>My Orders</a></li>
                        <li><a href="wishlist.php" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Wishlist</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <li><a href="browse.php?category=Vegetables" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Vegetables</a></li>
                        <li><a href="browse.php?category=Fruits" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Fruits</a></li>
                        <li><a href="browse.php?category=Grains" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Grains</a></li>
                        <li><a href="browse.php?category=Dairy" class="text-gray-300 hover:text-white transition-colors"><i class="fas fa-chevron-right text-xs mr-2"></i>Dairy</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-map-marker-alt mt-1 text-gray-300"></i>
                            <span class="text-gray-300 text-sm">123 Market Street, Farming District, Philippines</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-phone text-gray-300"></i>
                            <span class="text-gray-300 text-sm">+63 (123) 456-7890</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-gray-300"></i>
                            <span class="text-gray-300 text-sm">support@harvee.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    © <?php echo date('Y'); ?> Harvee Market. All rights reserved. | Fresh from farm to your table
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
