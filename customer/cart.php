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
$cart_items = [];
$total_amount = 0;

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to Cart (from product page)
    if (isset($_POST['add_to_cart'])) {
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
                            $message = "Cart updated successfully!";
                        } else {
                            $message = "Cannot add more than available stock!";
                        }
                    } else {
                        // Add new item
                        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $product_id, $quantity]);
                        $message = "Product added to cart!";
                    }
                } else {
                    $message = "Not enough stock available! Only " . $product['stock'] . " items left.";
                }
            } else {
                $message = "Product not found!";
            }
        }
    }
    
    // Update Cart Quantities
    if (isset($_POST['update_cart'])) {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $cart_id => $quantity) {
                $cart_id = (int)$cart_id;
                $quantity = (int)$quantity;
                
                if ($quantity <= 0) {
                    // Remove item if quantity is 0 or less
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $stmt->execute([$cart_id, $user_id]);
                } else {
                    // Check stock availability
                    $stmt = $pdo->prepare("
                        SELECT p.stock 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.id = ? AND c.user_id = ?
                    ");
                    $stmt->execute([$cart_id, $user_id]);
                    $item = $stmt->fetch();
                    
                    if ($item && $quantity <= $item['stock']) {
                        // Update quantity
                        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                        $stmt->execute([$quantity, $cart_id, $user_id]);
                    }
                }
            }
            $message = "Cart updated successfully!";
        }
    }
    
    // Remove Single Item
    if (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'] ?? 0;
        if ($cart_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $message = "Item removed from cart!";
        }
    }
    
    // Clear Entire Cart
    if (isset($_POST['clear_cart'])) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $message = "Cart cleared!";
    }
    
    // Checkout
    if (isset($_POST['checkout'])) {
        try {
            $pdo->beginTransaction();
            
            // Get cart items with product details
            $stmt = $pdo->prepare("
                SELECT c.*, p.product_name, p.price, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $cart_items_checkout = $stmt->fetchAll();
            
            if (empty($cart_items_checkout)) {
                throw new Exception("Your cart is empty!");
            }
            
            // Check stock availability
            foreach ($cart_items_checkout as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("'{$item['product_name']}' is out of stock! Available: {$item['stock']}");
                }
            }
            
            // Calculate total
            $order_total = 0;
            foreach ($cart_items_checkout as $item) {
                $order_total += $item['quantity'] * $item['price'];
            }
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $order_total]);
            $order_id = $pdo->lastInsertId();
            
            // Create order items and update stock
            foreach ($cart_items_checkout as $item) {
                // Add to order items
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Store order info in session for confirmation
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            $_SESSION['order_total'] = $order_total;
            
            // Redirect to order confirmation
            header('Location: order_confirmation.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Checkout failed: " . $e->getMessage();
        }
    }
}

// Get cart items for display
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.quantity, 
           p.id as product_id, p.product_name, p.price, p.description, p.stock,
           u.first_name as farmer_first, u.last_name as farmer_last
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    JOIN users u ON p.farmer_id = u.id 
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate total
foreach ($cart_items as $item) {
    $total_amount += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            background-color: #f9fafb;
        }
        .quantity-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            background: white;
            cursor: pointer;
            user-select: none;
        }
        .quantity-btn:hover {
            background-color: #f3f4f6;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #d1d5db;
            padding: 8px;
        }
        .sticky-summary {
            position: sticky;
            top: 20px;
        }
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #10854d;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="text-gray-700">Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-[#10854d]">Shopping Cart</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="browse.php" class="text-[#10854d] hover:underline">Continue Shopping</a>
                    <span class="text-gray-500">|</span>
                    <span class="text-gray-600">
                        <?php echo count($cart_items); ?> item(s)
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'success') !== false || strpos($message, 'added') !== false ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <div class="flex items-center">
                    <?php if (strpos($message, 'success') !== false || strpos($message, 'added') !== false): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items Section -->
            <div class="lg:w-2/3">
                <?php if (empty($cart_items)): ?>
                    <!-- Empty Cart -->
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-700 mb-3">Your cart is empty</h2>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                            Looks like you haven't added any products to your cart yet.
                            Start shopping to find amazing products from local farmers!
                        </p>
                        <div class="space-y-4">
                            <a href="browse.php" class="inline-block px-8 py-3 bg-[#10854d] text-white font-medium rounded-full hover:bg-[#0d6e40] transition-colors shadow-lg">
                                Browse Products
                            </a>
                            <p class="text-sm text-gray-500">
                                or <a href="dashboard.php" class="text-[#10854d] hover:underline">return to dashboard</a>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Cart Items Table -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <form method="POST" action="" id="cartForm">
                            <div class="p-6 border-b">
                                <div class="flex justify-between items-center">
                                    <h2 class="text-xl font-bold text-gray-800">Your Items (<?php echo count($cart_items); ?>)</h2>
                                    <button type="submit" 
                                            name="update_cart" 
                                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-full hover:bg-gray-200 transition-colors">
                                        Update Cart
                                    </button>
                                </div>
                            </div>
                            
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($cart_items as $index => $item): 
                                    $item_total = $item['quantity'] * $item['price'];
                                    $is_low_stock = $item['stock'] < 5;
                                    $max_quantity = min($item['stock'], 20); // Limit to 20 or available stock
                                ?>
                                    <div class="p-6 cart-item">
                                        <div class="flex flex-col md:flex-row gap-6">
                                            <!-- Product Image -->
                                            <div class="md:w-1/4">
                                                <div class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Product Details -->
                                            <div class="md:w-3/4">
                                                <div class="flex justify-between">
                                                    <div class="flex-1">
                                                        <h3 class="text-lg font-semibold text-gray-800 mb-1">
                                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                                        </h3>
                                                        <p class="text-gray-500 text-sm mb-2 line-clamp-2">
                                                            <?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?>
                                                        </p>
                                                        <div class="text-sm text-gray-600 mb-3">
                                                            <span class="font-medium">Farmer:</span> 
                                                            <?php echo htmlspecialchars($item['farmer_first'] . ' ' . $item['farmer_last']); ?>
                                                        </div>
                                                        
                                                        <!-- Stock Status -->
                                                        <div class="flex items-center gap-4 mb-4">
                                                            <div class="flex items-center">
                                                                <?php if ($is_low_stock): ?>
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                                        </svg>
                                                                        Low Stock: <?php echo $item['stock']; ?> left
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                                        </svg>
                                                                        In Stock
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span class="text-gray-500 text-sm">
                                                                Max: <?php echo $max_quantity; ?> per order
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Price -->
                                                    <div class="text-right">
                                                        <div class="text-xl font-bold text-[#10854d] mb-2">
                                                            ₱<?php echo number_format($item['price'], 2); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">per item</div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Quantity Controls -->
                                                <div class="flex items-center justify-between mt-4">
                                                    <div class="flex items-center">
                                                        <div class="flex items-center border border-gray-300 rounded-lg">
                                                            <button type="button" 
                                                                    class="quantity-btn decrement rounded-l-lg"
                                                                    data-cart-id="<?php echo $item['cart_id']; ?>"
                                                                    onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                                </svg>
                                                            </button>
                                                            <input type="number" 
                                                                   name="quantities[<?php echo $item['cart_id']; ?>]" 
                                                                   value="<?php echo $item['quantity']; ?>" 
                                                                   min="1" 
                                                                   max="<?php echo $max_quantity; ?>"
                                                                   class="quantity-input border-0 focus:ring-2 focus:ring-[#10854d]"
                                                                   onchange="validateQuantity(this, <?php echo $max_quantity; ?>)"
                                                                   data-cart-id="<?php echo $item['cart_id']; ?>">
                                                            <button type="button" 
                                                                    class="quantity-btn increment rounded-r-lg"
                                                                    data-cart-id="<?php echo $item['cart_id']; ?>"
                                                                    onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <span class="ml-4 text-gray-500 text-sm">
                                                            × <?php echo $item['quantity']; ?> = 
                                                            <span class="font-bold text-gray-700">
                                                                ₱<?php echo number_format($item_total, 2); ?>
                                                            </span>
                                                        </span>
                                                    </div>
                                                    
                                                    <!-- Remove Button -->
                                                    <form method="POST" action="" class="inline">
                                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                        <button type="submit" 
                                                                name="remove_item" 
                                                                onclick="return confirm('Remove this item from cart?')"
                                                                class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition-colors">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Cart Actions -->
                            <div class="p-6 bg-gray-50 border-t">
                                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                    <div class="text-sm text-gray-600">
                                        Prices and availability are subject to change
                                    </div>
                                    <div class="flex gap-3">
                                        <a href="browse.php" 
                                           class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-full hover:bg-gray-300 transition-colors">
                                            Continue Shopping
                                        </a>
                                        <button type="button" 
                                                onclick="clearCart()"
                                                class="px-6 py-3 bg-red-100 text-red-600 font-medium rounded-full hover:bg-red-200 transition-colors">
                                            Clear Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary Sidebar -->
            <?php if (!empty($cart_items)): ?>
                <div class="lg:w-1/3">
                    <div class="sticky-summary">
                        <!-- Order Summary Card -->
                        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                            <h2 class="text-xl font-bold text-gray-800 mb-6">Order Summary</h2>
                            
                            <!-- Items List -->
                            <div class="space-y-3 mb-6">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="flex justify-between items-center text-sm">
                                        <div class="flex-1 truncate mr-4">
                                            <span class="text-gray-700 font-medium">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </span>
                                            <span class="text-gray-500">×<?php echo $item['quantity']; ?></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-medium">
                                                ₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Calculations -->
                            <div class="space-y-3 border-t border-gray-200 pt-4 mb-6">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium">₱<?php echo number_format($total_amount, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping Fee</span>
                                    <span class="font-medium">
                                        <?php echo $total_amount >= 1000 ? 'FREE' : '₱50.00'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Service Fee</span>
                                    <span class="font-medium">₱10.00</span>
                                </div>
                                
                                <!-- Shipping Notice -->
                                <?php if ($total_amount < 1000): ?>
                                    <div class="text-sm text-blue-600 bg-blue-50 p-3 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.2 6.5 10.266a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                                            </svg>
                                            Spend ₱<?php echo number_format(1000 - $total_amount, 2); ?> more for FREE shipping!
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-green-600 bg-green-50 p-3 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            You've earned FREE shipping!
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Total -->
                            <div class="border-t border-gray-200 pt-4 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-800">Total</span>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-[#10854d]">
                                            ₱<?php echo number_format($total_amount + ($total_amount >= 1000 ? 0 : 50) + 10, 2); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo count($cart_items); ?> item(s)
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <form method="POST" action="" id="checkoutForm">
                                <button type="submit" 
                                        name="checkout" 
                                        class="w-full py-4 bg-[#10854d] text-white font-bold rounded-full text-lg hover:bg-[#0d6e40] transition-colors shadow-lg flex items-center justify-center"
                                        onclick="return confirmCheckout()">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    Proceed to Checkout
                                </button>
                            </form>
                            
                            <!-- Secure Checkout Notice -->
                            <div class="mt-4 text-center">
                                <div class="flex items-center justify-center text-sm text-gray-500 mb-2">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    100% Secure Checkout
                                </div>
                                <div class="text-xs text-gray-400">
                                    By placing your order, you agree to our Terms of Service
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="font-bold text-gray-700 mb-4">Accepted Payment Methods</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="p-3 bg-gray-100 rounded-lg text-center">
                                    <div class="text-sm font-medium text-gray-700">Cash on Delivery</div>
                                </div>
                                <div class="p-3 bg-gray-100 rounded-lg text-center">
                                    <div class="text-sm font-medium text-gray-700">GCash</div>
                                </div>
                                <div class="p-3 bg-gray-100 rounded-lg text-center">
                                    <div class="text-sm font-medium text-gray-700">Bank Transfer</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Update quantity with buttons
        function updateQuantity(cartId, change) {
            const input = document.querySelector(`input[name="quantities[${cartId}]"]`);
            let newValue = parseInt(input.value) + change;
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (newValue > max) newValue = max;
            if (newValue < min) newValue = min;
            
            input.value = newValue;
        }
        
        // Validate quantity input
        function validateQuantity(input, max) {
            let value = parseInt(input.value);
            if (isNaN(value) || value < 1) {
                input.value = 1;
            } else if (value > max) {
                input.value = max;
                alert(`Maximum quantity is ${max} due to stock limits.`);
            }
        }
        
        // Clear cart confirmation
        function clearCart() {
            if (confirm('Are you sure you want to clear your entire cart?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'clear_cart';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Checkout confirmation
        function confirmCheckout() {
            return confirm('Proceed to checkout?\n\nYou will be redirected to complete your order.');
        }
        
        // Auto-update cart when quantity changes (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Optional: Auto-submit form when quantity changes
                    // document.getElementById('cartForm').submit();
                });
            });
        });
    </script>
</body>
</html>