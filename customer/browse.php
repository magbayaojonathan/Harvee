<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    if ($product_id > 0 && $quantity > 0) {
        // Check if product exists and is in stock
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            if ($product['stock'] >= $quantity) {
                // Check if item already in cart
                $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                $existing_item = $stmt->fetch();
                
                if ($existing_item) {
                    // Update quantity
                    $new_quantity = $existing_item['quantity'] + $quantity;
                    if ($new_quantity <= $product['stock']) {
                        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $stmt->execute([$new_quantity, $existing_item['id']]);
                        $message = "✓ Added to cart! Quantity updated.";
                    } else {
                        $message = "⚠ Cannot add more than available stock! Only " . $product['stock'] . " items left.";
                    }
                } else {
                    // Add new item
                    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $product_id, $quantity]);
                    $message = "✓ Product added to cart successfully!";
                }
            } else {
                $message = "⚠ Not enough stock available! Only " . $product['stock'] . " items left.";
            }
        } else {
            $message = "❌ Product not found!";
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build base query - REMOVED farmer join since it's single farmer
$sql = "SELECT SQL_CALC_FOUND_ROWS 
               p.*
        FROM products p 
        WHERE p.stock > 0";
        
$count_sql = "SELECT COUNT(*) FROM products p WHERE p.stock > 0";

$params = [];
$count_params = [];

// Apply filters
if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $count_sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if (!empty($category)) {
    $sql .= " AND p.category = ?";
    $count_sql .= " AND p.category = ?";
    $params[] = $category;
    $count_params[] = $category;
}

if (!empty($min_price) && is_numeric($min_price)) {
    $sql .= " AND p.price >= ?";
    $count_sql .= " AND p.price >= ?";
    $params[] = (float)$min_price;
    $count_params[] = (float)$min_price;
}

if (!empty($max_price) && is_numeric($max_price)) {
    $sql .= " AND p.price <= ?";
    $count_sql .= " AND p.price <= ?";
    $params[] = (float)$max_price;
    $count_params[] = (float)$max_price;
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.product_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.product_name DESC";
        break;
    case 'stock_high':
        $sql .= " ORDER BY p.stock DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

// Add pagination - don't add to $params array, bind separately
$sql .= " LIMIT ? OFFSET ?";

// Get products
$stmt = $pdo->prepare($sql);

// Bind all parameters with proper types
$param_index = 1;
foreach ($params as $param) {
    // Determine parameter type
    if (is_int($param)) {
        $stmt->bindValue($param_index, $param, PDO::PARAM_INT);
    } elseif (is_float($param)) {
        $stmt->bindValue($param_index, $param, PDO::PARAM_STR);
    } else {
        $stmt->bindValue($param_index, $param, PDO::PARAM_STR);
    }
    $param_index++;
}

// Bind LIMIT and OFFSET as integers
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index, $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare($count_sql);
if (!empty($count_params)) {
    $count_stmt->execute($count_params);
} else {
    $count_stmt->execute();
}
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get categories for filter dropdown - with proper error handling
$categories = [];
try {
    // Check if category column exists
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM products LIKE 'category'");
    $checkStmt->execute();
    $columnExists = $checkStmt->fetch();
    
    if ($columnExists) {
        // Get distinct categories
        $catStmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $catStmt->execute();
        $categories = $catStmt->fetchAll();
    } else {
        // Create sample categories if column doesn't exist
        $categories = [
            ['category' => 'Vegetables'],
            ['category' => 'Fruits'],
            ['category' => 'Grains'],
            ['category' => 'Poultry'],
            ['category' => 'Dairy']
        ];
    }
} catch (PDOException $e) {
    // Use default categories on error
    $categories = [
        ['category' => 'Vegetables'],
        ['category' => 'Fruits'],
        ['category' => 'Grains']
    ];
}

// Get cart count for badge
$cart_count = 0;
try {
    $cartStmt = $pdo->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cartResult = $cartStmt->fetch();
    $cart_count = $cartResult['total_items'] ?? 0;
} catch (PDOException $e) {
    // Cart table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .category-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }
        .price-tag {
            background: linear-gradient(135deg, #10854d, #0d6e40);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
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
        .filter-sidebar {
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #10854d;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                        <span class="text-gray-700 font-medium ml-2">Browse Products</span>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-4">
                    <form method="GET" action="" class="relative">
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-full focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </form>
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
                                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium">
                                <?php echo htmlspecialchars($_SESSION['first_name']); ?>
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

    <!-- Success Message -->
    <?php if ($message): ?>
        <div class="fixed top-20 right-4 z-50 animate__animated animate__fadeInRight">
            <div class="bg-white rounded-lg shadow-lg p-4 max-w-sm border-l-4 <?php echo strpos($message, '✓') !== false ? 'border-green-500' : 'border-red-500'; ?>">
                <div class="flex items-center">
                    <?php if (strpos($message, '✓') !== false): ?>
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">
                            <?php echo htmlspecialchars(str_replace(['✓', '⚠', '❌'], '', $message)); ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <a href="cart.php" class="text-[#10854d] hover:underline">View Cart →</a>
                        </p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <script>
            // Auto-remove message after 5 seconds
            setTimeout(() => {
                const message = document.querySelector('.fixed.top-20');
                if (message) message.remove();
            }, 5000);
        </script>
    <?php endif; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <div class="lg:w-1/4">
                <div class="filter-sidebar bg-white rounded-xl shadow-sm p-6">
                    <!-- Filter Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Filters</h2>
                        <?php if ($search || $category || $min_price || $max_price): ?>
                            <a href="browse.php" class="text-sm text-[#10854d] hover:underline">
                                Clear All
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Filter Form -->
                    <form method="GET" action="" class="space-y-6">
                        <!-- Price Range -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-3">Price Range</h3>
                            <div class="flex gap-3">
                                <div class="flex-1">
                                    <input type="number" 
                                           name="min_price" 
                                           placeholder="Min ₱" 
                                           value="<?php echo htmlspecialchars($min_price); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                                </div>
                                <div class="flex-1">
                                    <input type="number" 
                                           name="max_price" 
                                           placeholder="Max ₱" 
                                           value="<?php echo htmlspecialchars($max_price); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Category -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-3">Category</h3>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category'] ?? $cat->category); ?>" <?php echo ($category == ($cat['category'] ?? $cat->category)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category'] ?? $cat->category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-3">Sort By</h3>
                            <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                                <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                                <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                                <option value="stock_high" <?php echo ($sort == 'stock_high') ? 'selected' : ''; ?>>Most Stock</option>
                            </select>
                        </div>
                        
                        <!-- Apply Filters Button -->
                        <button type="submit" 
                                class="w-full py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                            Apply Filters
                        </button>
                    </form>
                    
                    <!-- Active Filters -->
                    <?php if ($search || $category || $min_price || $max_price): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="font-medium text-gray-700 mb-2">Active Filters</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($search): ?>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full flex items-center">
                                        Search: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="ml-2 text-blue-600 hover:text-blue-800">
                                            ×
                                        </a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($category): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full flex items-center">
                                        Category: <?php echo htmlspecialchars($category); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" class="ml-2 text-green-600 hover:text-green-800">
                                            ×
                                        </a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($min_price): ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full flex items-center">
                                        Min: ₱<?php echo htmlspecialchars($min_price); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['min_price' => ''])); ?>" class="ml-2 text-yellow-600 hover:text-yellow-800">
                                            ×
                                        </a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($max_price): ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full flex items-center">
                                        Max: ₱<?php echo htmlspecialchars($max_price); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['max_price' => ''])); ?>" class="ml-2 text-yellow-600 hover:text-yellow-800">
                                            ×
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats -->
                <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                    <h3 class="font-medium text-gray-700 mb-4">Store Stats</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Total Products</span>
                            <span class="font-bold text-[#10854d]"><?php echo $total_products; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Categories</span>
                            <span class="font-bold text-[#10854d]"><?php echo count($categories); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Items in Cart</span>
                            <span class="font-bold text-[#10854d]"><?php echo $cart_count; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="lg:w-3/4">
                <!-- Results Header -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Fresh Products from Harvee Farm</h1>
                            <p class="text-gray-600">
                                <?php if ($total_products > 0): ?>
                                    Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
                                <?php else: ?>
                                    No products found
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="cart.php" class="relative px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Cart
                                <?php if ($cart_count > 0): ?>
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                                        <?php echo $cart_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <!-- Empty State -->
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-700 mb-3">No products found</h2>
                        <p class="text-gray-500 max-w-md mx-auto mb-6">
                            <?php if ($search || $category || $min_price || $max_price): ?>
                                Try adjusting your search filters or clear all filters to see more products.
                            <?php else: ?>
                                No products are currently available. Check back soon!
                            <?php endif; ?>
                        </p>
                        <div class="space-x-4">
                            <a href="browse.php" class="inline-block px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                Clear Filters
                            </a>
                            <a href="dashboard.php" class="inline-block px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($products as $product): 
                            $is_low_stock = $product['stock'] < 10;
                            $is_new = strtotime($product['created_at']) > strtotime('-7 days');
                        ?>
                            <div class="product-card bg-white rounded-xl shadow-sm overflow-hidden fade-in">
                                <!-- Product Image & Badges -->
                                <div class="relative h-48 bg-gradient-to-br from-green-50 to-gray-100">
                                    <!-- Product Image Placeholder -->
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    
                                    <!-- Badges -->
                                    <?php if ($is_new): ?>
                                        <span class="category-tag px-3 py-1 bg-blue-500 text-white text-xs font-bold rounded-full">
                                            NEW
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_low_stock): ?>
                                        <span class="stock-badge px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                            Only <?php echo $product['stock']; ?> left
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="absolute bottom-3 left-3 price-tag text-sm">
                                        ₱<?php echo number_format($product['price'], 2); ?>
                                    </span>
                                    
                                    <?php if ($product['category']): ?>
                                        <span class="absolute bottom-3 right-3 px-3 py-1 bg-white/90 text-gray-700 text-xs font-medium rounded-full">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="p-5">
                                    <h3 class="font-bold text-gray-800 text-lg mb-2 truncate">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </h3>
                                    
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-2 h-10">
                                        <?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?>
                                    </p>
                                    
                                    <!-- Store Info (instead of farmer) -->
                                    <div class="flex items-center mb-4">
                                        <div class="w-8 h-8 bg-[#10854d] rounded-full flex items-center justify-center mr-3">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">Harvee Farm Store</p>
                                            <p class="text-xs text-gray-500">Direct from farm</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Stock & Add to Cart -->
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm">
                                            <span class="text-gray-500">Stock:</span>
                                            <span class="font-medium ml-1 <?php echo $is_low_stock ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo $product['stock']; ?> available
                                            </span>
                                        </div>
                                        
                                        <!-- Add to Cart Form -->
                                        <form method="POST" action="" class="flex items-center space-x-2">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="relative">
                                                <input type="number" 
                                                       name="quantity" 
                                                       value="1" 
                                                       min="1" 
                                                       max="<?php echo min($product['stock'], 10); ?>"
                                                       class="w-16 px-3 py-1 border border-gray-300 rounded-lg text-center text-sm">
                                            </div>
                                            <button type="submit" 
                                                    name="add_to_cart" 
                                                    class="px-4 py-2 bg-[#10854d] text-white text-sm font-medium rounded-lg hover:bg-[#0d6e40] transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Add
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Quick Actions -->
                                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between">
                                        <button onclick="showProductDetails(<?php echo $product['id']; ?>)" 
                                                class="text-sm text-[#10854d] hover:underline">
                                            View Details
                                        </button>
                                        <?php if ($product['category']): ?>
                                            <a href="?category=<?php echo urlencode($product['category']); ?>" 
                                               class="text-sm text-gray-600 hover:text-[#10854d]">
                                                More <?php echo htmlspecialchars($product['category']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php 
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++): 
                                ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-[#10854d] text-white border-[#10854d]' : 'border-gray-300 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Results Summary -->
                    <div class="mt-8 text-center text-gray-500 text-sm">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        • <?php echo $total_products; ?> total products
                        <?php if ($search): ?>
                            • Searching for "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Quick View Modal -->
    <div id="productModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Product Details</h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="container mx-auto px-4 py-8">
            <div class="text-center text-gray-500 text-sm">
                <p>© <?php echo date('Y'); ?> Harvee Marketplace. All rights reserved.</p>
                <p class="mt-2">Fresh produce from Harvee Farm to your table</p>
            </div>
        </div>
    </footer>

    <script>
        // Show product details modal
        function showProductDetails(productId) {
            // Show loading state
            document.getElementById('modalContent').innerHTML = `
                <div class="flex justify-center items-center h-64">
                    <div class="loader"></div>
                    <span class="ml-3 text-gray-600">Loading product details...</span>
                </div>
            `;
            
            // Show modal
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
            
            // Load product details
            fetch(`../product_details.php?id=${productId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContent').innerHTML = `
                        <div class="text-center p-8">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Failed to load product details</h3>
                            <p class="text-gray-600 mb-4">Please try again later.</p>
                            <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                Close
                            </button>
                        </div>
                    `;
                });
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('productModal');
            if (modal && e.target === modal) {
                closeModal();
            }
        });
        
        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Auto-submit search when typing stops (debounced)
        let searchTimeout;
        document.querySelector('input[name="search"]')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
        
        // Add to cart with animation
        document.querySelectorAll('form[action=""]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[name="add_to_cart"]');
                if (button) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Adding...';
                    button.disabled = true;
                    
                    // Re-enable button after 2 seconds if something goes wrong
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 2000);
                }
            });
        });
    </script>
</body>
</html>