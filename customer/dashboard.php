<?php
session_start();
// Check if user is logged in as customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../shared/header.php';
require_once '../shared/navigation.php';

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

try {
    // Get cart count
    $cartStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cart_count = $cartStmt->fetch()['total'] ?? 0;
    
    // Get order count
    $orderStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
    $orderStmt->execute([$user_id]);
    $order_count = $orderStmt->fetch()['total'] ?? 0;
    
    // Get recent orders
    $recentStmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 3
    ");
    $recentStmt->execute([$user_id]);
    $recent_orders = $recentStmt->fetchAll();
    
} catch (PDOException $e) {
    // Tables might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .dashboard-card {
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .welcome-section {
            background: linear-gradient(135deg, #10854d, #0d6e40);
            position: relative;
            overflow: hidden;
        }
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .cart-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .gradient-border {
            position: relative;
            background: white;
            border-radius: 1rem;
        }
        .gradient-border::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #10854d, #4ade80);
            border-radius: 1.2rem;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gradient-border:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header/Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <svg class="w-8 h-8 text-[#10854d]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xl font-bold text-gray-800">Harvee Market</span>
                    </a>
                    <div class="hidden md:block">
                        <span class="text-gray-500">/</span>
                        <span class="text-gray-700 font-medium ml-2">Dashboard</span>
                    </div>
                </div>
                
                <!-- Cart & User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Cart Button -->
                    <a href="cart.php" class="relative p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center cart-pulse">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- User Menu -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100">
                            <div class="w-8 h-8 bg-[#10854d] rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium">
                                <?php echo htmlspecialchars($first_name); ?>
                            </span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> My Profile
                            </a>
                            <a href="orders.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-shopping-bag mr-2"></i> My Orders
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="welcome-section rounded-2xl p-8 mb-8 text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-2">
                            Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋
                        </h1>
                        <p class="text-white/90 text-lg max-w-2xl">
                            Your farming marketplace for fresh, quality products. Track your orders, manage your cart, and discover new items.
                        </p>
                    </div>
                    <div class="hidden lg:block">
                        <svg class="w-32 h-32 text-white/20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white/80 text-sm">Cart Items</p>
                                <p class="text-2xl font-bold"><?php echo $cart_count; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white/80 text-sm">Total Orders</p>
                                <p class="text-2xl font-bold"><?php echo $order_count; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white/80 text-sm">Member Since</p>
                                <p class="text-xl font-bold">2024</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Browse Products Card -->
            <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 fade-in" style="animation-delay: 0.1s">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-[#10854d]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Browse Products</h3>
                <p class="text-gray-600 text-sm mb-4">Explore our fresh produce and farm products</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">200+ products</span>
                    <a href="browse.php" class="inline-flex items-center text-[#10854d] font-medium hover:text-[#0d6e40]">
                        Shop Now
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- My Cart Card -->
            <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 fade-in" style="animation-delay: 0.2s">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">My Cart</h3>
                <p class="text-gray-600 text-sm mb-4">Review and checkout your items</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500"><?php echo $cart_count; ?> items</span>
                    <a href="cart.php" class="inline-flex items-center text-blue-600 font-medium hover:text-blue-700">
                        View Cart
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- My Orders Card -->
            <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 fade-in" style="animation-delay: 0.3s">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">My Orders</h3>
                <p class="text-gray-600 text-sm mb-4">Track your orders and history</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500"><?php echo $order_count; ?> orders</span>
                    <a href="orders.php" class="inline-flex items-center text-purple-600 font-medium hover:text-purple-700">
                        View Orders
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 fade-in" style="animation-delay: 0.4s">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">My Profile</h3>
                <p class="text-gray-600 text-sm mb-4">Manage your account settings</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars($first_name); ?></span>
                    <a href="profile.php" class="inline-flex items-center text-orange-600 font-medium hover:text-orange-700">
                        Edit Profile
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Recent Orders -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Recent Orders</h2>
                    <a href="orders.php" class="text-sm text-[#10854d] hover:underline">View All</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500">No orders yet</p>
                        <a href="browse.php" class="inline-block mt-3 text-sm text-[#10854d] hover:underline">
                            Start Shopping →
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-[#10854d]">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                    <p class="text-xs text-gray-500"><?php echo $order['item_count']; ?> items</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recommended Products -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Quick Actions</h2>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <a href="browse.php?category=Vegetables" class="p-4 bg-green-50 rounded-lg text-center hover:bg-green-100 transition-colors">
                        <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Vegetables</span>
                    </a>
                    
                    <a href="browse.php?category=Fruits" class="p-4 bg-orange-50 rounded-lg text-center hover:bg-orange-100 transition-colors">
                        <svg class="w-8 h-8 text-orange-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Fruits</span>
                    </a>
                    
                    <a href="browse.php" class="p-4 bg-blue-50 rounded-lg text-center hover:bg-blue-100 transition-colors">
                        <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Browse All</span>
                    </a>
                    
                    <a href="cart.php" class="p-4 bg-purple-50 rounded-lg text-center hover:bg-purple-100 transition-colors">
                        <svg class="w-8 h-8 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">View Cart</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Featured Categories -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Shop by Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="browse.php?category=Vegetables" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-green-200 transition-colors">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">Vegetables</span>
                </a>
                
                <a href="browse.php?category=Fruits" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-orange-200 transition-colors">
                        <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">Fruits</span>
                </a>
                
                <a href="browse.php?category=Grains" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-yellow-200 transition-colors">
                        <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">Grains</span>
                </a>
                
                <a href="browse.php?category=Poultry" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-red-200 transition-colors">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">Poultry</span>
                </a>
                
                <a href="browse.php?category=Dairy" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-blue-200 transition-colors">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">Dairy</span>
                </a>
                
                <a href="browse.php" class="text-center group">
                    <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-gray-200 transition-colors">
                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-[#10854d]">All</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>

    <?php require_once '../shared/footer.php'; ?>