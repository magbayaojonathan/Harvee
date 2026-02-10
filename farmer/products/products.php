<?php
session_start();
require_once '../../config/database.php';
// Check if user is logged in as farmer/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'farmer' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle actions (delete, update stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete product
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'] ?? 0;
        
        if ($product_id > 0) {
            try {
                // Check if product belongs to this farmer
                $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
                $check_stmt->execute([$product_id, $user_id]);
                
                if ($check_stmt->fetch()) {
                    $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $delete_stmt->execute([$product_id]);
                    $message = "✓ Product deleted successfully!";
                } else {
                    $message = "❌ You don't have permission to delete this product.";
                }
            } catch (PDOException $e) {
                $message = "❌ Error deleting product: " . $e->getMessage();
            }
        }
    }
    
    // Update stock
    if (isset($_POST['update_stock'])) {
        $product_id = $_POST['product_id'] ?? 0;
        $new_stock = $_POST['stock'] ?? 0;
        
        if ($product_id > 0 && is_numeric($new_stock) && $new_stock >= 0) {
            try {
                // Check if product belongs to this farmer
                $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
                $check_stmt->execute([$product_id, $user_id]);
                
                if ($check_stmt->fetch()) {
                    $update_stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                    $update_stmt->execute([$new_stock, $product_id]);
                    $message = "✓ Stock updated successfully!";
                } else {
                    $message = "❌ You don't have permission to update this product.";
                }
            } catch (PDOException $e) {
                $message = "❌ Error updating stock: " . $e->getMessage();
            }
        }
    }
    
    // Update price
    if (isset($_POST['update_price'])) {
        $product_id = $_POST['product_id'] ?? 0;
        $new_price = $_POST['price'] ?? 0;
        
        if ($product_id > 0 && is_numeric($new_price) && $new_price > 0) {
            try {
                // Check if product belongs to this farmer
                $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
                $check_stmt->execute([$product_id, $user_id]);
                
                if ($check_stmt->fetch()) {
                    $update_stmt = $pdo->prepare("UPDATE products SET price = ? WHERE id = ?");
                    $update_stmt->execute([$new_price, $product_id]);
                    $message = "✓ Price updated successfully!";
                } else {
                    $message = "❌ You don't have permission to update this product.";
                }
            } catch (PDOException $e) {
                $message = "❌ Error updating price: " . $e->getMessage();
            }
        }
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$stock_filter = $_GET['stock'] ?? '';

// Build query
$sql = "SELECT * FROM products WHERE farmer_id = ?";
$params = [$user_id];

if (!empty($search)) {
    $sql .= " AND (product_name LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($stock_filter === 'low') {
    $sql .= " AND stock < 10";
} elseif ($stock_filter === 'out') {
    $sql .= " AND stock = 0";
} elseif ($stock_filter === 'in') {
    $sql .= " AND stock > 0";
}

// Apply sorting
switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY product_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY product_name DESC";
        break;
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'stock_low':
        $sql .= " ORDER BY stock ASC";
        break;
    case 'stock_high':
        $sql .= " ORDER BY stock DESC";
        break;
    default:
        $sql .= " ORDER BY created_at DESC";
}

// Get products
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get total counts for stats
$total_products = count($products);

$low_stock_count = 0;
$out_of_stock_count = 0;
$in_stock_count = 0;
$total_stock_value = 0;

foreach ($products as $product) {
    if ($product['stock'] == 0) {
        $out_of_stock_count++;
    } elseif ($product['stock'] < 10) {
        $low_stock_count++;
    } else {
        $in_stock_count++;
    }
    $total_stock_value += $product['price'] * $product['stock'];
}

// Get unique categories for filter
$categories = [];
foreach ($products as $product) {
    if (!empty($product['category']) && !in_array($product['category'], $categories)) {
        $categories[] = $product['category'];
    }
}
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .product-row:hover {
            background-color: #f9fafb;
        }
        .stock-low {
            background-color: #fef3c7;
        }
        .stock-out {
            background-color: #fee2e2;
        }
        .stock-good {
            background-color: #d1fae5;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
                        <span class="text-xl font-bold text-gray-800">Harvee Farm</span>
                    </a>
                    <div class="hidden md:block">
                        <span class="text-gray-500">/</span>
                        <span class="text-gray-700 font-medium ml-2">Manage Products</span>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <a href="add.php" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40] transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add New
                    </a>
                    
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
                            <a href="add.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-plus-circle mr-2"></i> Add Product
                            </a>
                            <a href="products.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-boxes mr-2"></i> Manage Products
                            </a>
                            <a href="orders.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-shopping-bag mr-2"></i> View Orders
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

    <!-- Success/Error Message -->
    <?php if ($message): ?>
        <div class="container mx-auto px-4 mt-6">
            <div class="p-4 rounded-lg border-l-4 <?php echo strpos($message, '✓') !== false ? 'bg-green-100 text-green-700 border-green-500' : 'bg-red-100 text-red-700 border-red-500'; ?>">
                <div class="flex items-center">
                    <?php if (strpos($message, '✓') !== false): ?>
                        <i class="fas fa-check-circle mr-3"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars(str_replace(['✓', '❌'], '', $message)); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-[#10854d] mb-2">Manage Products</h1>
            <p class="text-gray-600">View, edit, and manage your farm products</p>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Products</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_products; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">In Stock</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $in_stock_count; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Low Stock</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $low_stock_count; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Out of Stock</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $out_of_stock_count; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <input type="text" 
                               name="search" 
                               placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20">
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Stock Filter -->
                    <div>
                        <select name="stock" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                            <option value="">All Stock</option>
                            <option value="in" <?php echo ($stock_filter == 'in') ? 'selected' : ''; ?>>In Stock</option>
                            <option value="low" <?php echo ($stock_filter == 'low') ? 'selected' : ''; ?>>Low Stock (&lt;10)</option>
                            <option value="out" <?php echo ($stock_filter == 'out') ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <!-- Sort By -->
                    <div>
                        <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none">
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name: A-Z</option>
                            <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name: Z-A</option>
                            <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="stock_low" <?php echo ($sort == 'stock_low') ? 'selected' : ''; ?>>Stock: Low to High</option>
                            <option value="stock_high" <?php echo ($sort == 'stock_high') ? 'selected' : ''; ?>>Stock: High to Low</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-between">
                    <button type="submit" 
                            class="px-6 py-2 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                        Apply Filters
                    </button>
                    <?php if ($search || $category || $stock_filter): ?>
                        <a href="products.php" 
                           class="px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if (empty($products)): ?>
                <!-- Empty State -->
                <div class="p-12 text-center">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-3">No products found</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        <?php if ($search || $category || $stock_filter): ?>
                            Try adjusting your filters or 
                            <a href="products.php" class="text-[#10854d] hover:underline">clear all filters</a>.
                        <?php else: ?>
                            You haven't added any products yet.
                        <?php endif; ?>
                    </p>
                    <a href="add.php" 
                       class="inline-block px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add Your First Product
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Product</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Category</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Price</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Stock</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($products as $product): 
                                $stock_class = '';
                                $stock_status = '';
                                
                                if ($product['stock'] == 0) {
                                    $stock_class = 'stock-out';
                                    $stock_status = 'Out of Stock';
                                } elseif ($product['stock'] < 10) {
                                    $stock_class = 'stock-low';
                                    $stock_status = 'Low Stock';
                                } else {
                                    $stock_class = 'stock-good';
                                    $stock_status = 'In Stock';
                                }
                            ?>
                                <tr class="product-row <?php echo $stock_class; ?>">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                                                <i class="fas fa-seedling text-gray-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                                <p class="text-xs text-gray-500 truncate max-w-xs">
                                                    <?php echo htmlspecialchars(substr($product['description'] ?? 'No description', 0, 50)); ?>...
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                            <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-[#10854d]">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <button onclick="openEditPriceModal(<?php echo $product['id']; ?>, <?php echo $product['price']; ?>)" 
                                                class="ml-2 text-gray-400 hover:text-[#10854d] text-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <span class="font-medium <?php echo $product['stock'] == 0 ? 'text-red-600' : ($product['stock'] < 10 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                                <?php echo $product['stock']; ?>
                                            </span>
                                            <button onclick="openEditStockModal(<?php echo $product['id']; ?>, <?php echo $product['stock']; ?>)" 
                                                    class="ml-2 text-gray-400 hover:text-[#10854d] text-sm">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 text-xs rounded-full 
                                            <?php echo $product['stock'] == 0 ? 'bg-red-100 text-red-800' : 
                                                   ($product['stock'] < 10 ? 'bg-yellow-100 text-yellow-800' : 
                                                   'bg-green-100 text-green-800'); ?>">
                                            <?php echo $stock_status; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-2">
                                            <a href="#" 
                                               onclick="openViewModal(<?php echo $product['id']; ?>)" 
                                               class="px-3 py-1 bg-blue-100 text-blue-600 text-xs font-medium rounded hover:bg-blue-200">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="edit.php?id=<?php echo $product['id']; ?>" 
                                               class="px-3 py-1 bg-yellow-100 text-yellow-600 text-xs font-medium rounded hover:bg-yellow-200">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="inline" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" 
                                                        name="delete_product" 
                                                        class="px-3 py-1 bg-red-100 text-red-600 text-xs font-medium rounded hover:bg-red-200">
                                                    <i class="fas fa-trash mr-1"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Footer -->
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-sm text-gray-600 mb-4 md:mb-0">
                            Showing <?php echo count($products); ?> product(s)
                            <?php if ($category): ?>
                                in <span class="font-medium"><?php echo htmlspecialchars($category); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm">
                                <span class="text-gray-600">Total Stock Value:</span>
                                <span class="font-bold text-[#10854d] ml-2">₱<?php echo number_format($total_stock_value, 2); ?></span>
                            </div>
                            <a href="add.php" 
                               class="px-4 py-2 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                <i class="fas fa-plus mr-2"></i> Add New Product
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- View Product Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Product Details</h3>
                    <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <!-- Edit Stock Modal -->
    <div id="editStockModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Update Stock</h3>
                    <button onclick="closeModal('editStockModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" id="stockForm">
                    <input type="hidden" name="product_id" id="stockProductId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">New Stock Quantity</label>
                            <input type="number" 
                                   name="stock" 
                                   id="stockInput"
                                   min="0"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                   required>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" 
                                    onclick="closeModal('editStockModal')" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    name="update_stock" 
                                    class="px-6 py-2 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40]">
                                Update Stock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Price Modal -->
    <div id="editPriceModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Update Price</h3>
                    <button onclick="closeModal('editPriceModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" id="priceForm">
                    <input type="hidden" name="product_id" id="priceProductId">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">New Price (₱)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" 
                                       name="price" 
                                       id="priceInput"
                                       step="0.01"
                                       min="0.01"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                       required>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" 
                                    onclick="closeModal('editPriceModal')" 
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    name="update_price" 
                                    class="px-6 py-2 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40]">
                                Update Price
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openViewModal(productId) {
            // Load product details
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalContent').innerHTML = html;
                    document.getElementById('viewModal').style.display = 'block';
                })
                .catch(error => {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="text-center p-8">
                            <i class="fas fa-exclamation-circle text-red-500 text-3xl mb-4"></i>
                            <p class="text-gray-600">Failed to load product details.</p>
                        </div>
                    `;
                    document.getElementById('viewModal').style.display = 'block';
                });
        }

        function openEditStockModal(productId, currentStock) {
            document.getElementById('stockProductId').value = productId;
            document.getElementById('stockInput').value = currentStock;
            document.getElementById('editStockModal').style.display = 'block';
        }

        function openEditPriceModal(productId, currentPrice) {
            document.getElementById('priceProductId').value = productId;
            document.getElementById('priceInput').value = currentPrice;
            document.getElementById('editPriceModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['viewModal', 'editStockModal', 'editPriceModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Delete confirmation
        function confirmDelete() {
            return confirm('Are you sure you want to delete this product? This action cannot be undone.');
        }

        // Format price on blur
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('priceInput');
            if (priceInput) {
                priceInput.addEventListener('blur', function() {
                    if (this.value && !isNaN(this.value)) {
                        this.value = parseFloat(this.value).toFixed(2);
                    }
                });
            }
        });
    </script>
</body>
</html>