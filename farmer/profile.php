<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in as farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $name = $first_name . ' ' . $last_name;
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($first_name)) {
            $errors[] = "First name is required";
        }
        
        if (empty($last_name)) {
            $errors[] = "Last name is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check if email already exists (excluding current user)
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists";
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, name = ?, email = ?, phone = ?, address = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $name, $email, $phone, $address, $user_id]);
                
                // Update session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['name'] = $name;
                
                $message = "Profile updated successfully!";
                $message_type = "success";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
            } catch (PDOException $e) {
                $message = "Error updating profile: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = "error";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $message = "Password changed successfully!";
                $message_type = "success";
                
            } catch (PDOException $e) {
                $message = "Error changing password: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = "error";
        }
    }
}

// Get farmer statistics
$stats = [];

// Total products listed
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE farmer_id = ?");
$stmt->execute([$user_id]);
$stats['total_products'] = $stmt->fetch()['total'] ?? 0;

// Total orders received (through their products)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT oi.order_id) as total 
    FROM order_items oi 
    WHERE oi.farmer_id = ?
");
$stmt->execute([$user_id]);
$stats['total_orders'] = $stmt->fetch()['total'] ?? 0;

// Total revenue - FIXED: using unit_price and total_price
$stmt = $pdo->prepare("
    SELECT SUM(oi.total_price) as total 
    FROM order_items oi 
    WHERE oi.farmer_id = ?
");
$stmt->execute([$user_id]);
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Pending orders - FIXED: using order_status column
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT oi.order_id) as total 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.farmer_id = ? AND o.order_status = 'pending'
");
$stmt->execute([$user_id]);
$stats['pending_orders'] = $stmt->fetch()['total'] ?? 0;

// Low stock products (less than 10 items) - FIXED: using stock_quantity
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM products 
    WHERE farmer_id = ? AND stock_quantity < 10
");
$stmt->execute([$user_id]);
$stats['low_stock'] = $stmt->fetch()['total'] ?? 0;

// Get recent products - FIXED: using correct column names
$stmt = $pdo->prepare("
    SELECT id, name, price, stock_quantity, created_at 
    FROM products 
    WHERE farmer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_products = $stmt->fetchAll();

// Get recent orders for farmer's products - FIXED: using correct column names
$stmt = $pdo->prepare("
    SELECT o.id as order_id, o.created_at, o.order_status as status,
           oi.quantity, oi.unit_price, oi.total_price,
           p.name as product_name,
           u.first_name, u.last_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.customer_id = u.id
    WHERE oi.farmer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Member since
$member_since = date('F Y', strtotime($user['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Profile - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%);
        }
        .profile-sidebar {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 100%);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .product-item, .order-item {
            transition: all 0.3s ease;
        }
        .product-item:hover, .order-item:hover {
            background-color: #f9fafb;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-shipped { background: #c7d2fe; color: #3730a3; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                    <h1 class="text-2xl font-bold text-[#10854d]">My Farm Profile</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <div class="flex items-center">
                    <?php if ($message_type === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    <div><?php echo $message; ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Profile Sidebar -->
            <div class="lg:w-1/3">
                <div class="profile-sidebar rounded-xl shadow-lg overflow-hidden sticky top-20">
                    <!-- Profile Header -->
                    <div class="p-6 text-center text-white">
                        <div class="w-24 h-24 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                            <span class="text-3xl font-bold text-[#10854d]">
                                <?php echo strtoupper(substr($user['first_name'] ?? $user['name'] ?? 'F', 0, 1)); ?>
                            </span>
                        </div>
                        <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($user['first_name'] ?? $user['name'] ?? 'Farmer'); ?></h2>
                        <p class="text-green-100 text-sm mb-2">Farmer</p>
                        <p class="text-green-100 text-sm mb-4">Member since <?php echo $member_since; ?></p>
                        
                        <!-- Farm Stats -->
                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-green-600">
                            <div>
                                <div class="text-2xl font-bold"><?php echo $stats['total_products']; ?></div>
                                <div class="text-xs text-green-100">Products</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">₱<?php echo number_format($stats['total_revenue'], 0); ?></div>
                                <div class="text-xs text-green-100">Revenue</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="bg-white p-4">
                        <h3 class="font-semibold text-gray-700 mb-3">Farm Management</h3>
                        <div class="space-y-2">
                            <a href="products/products.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span class="text-gray-700">Manage Products</span>
                            </a>
                            <a href="products/add.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <span class="text-gray-700">Add New Product</span>
                            </a>
                            <a href="orders.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span class="text-gray-700">View Orders</span>
                            </a>
                            <a href="reviews.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.093 3.362a1 1 0 00.95.69h3.534c.969 0 1.371 1.24.588 1.81l-2.86 2.078a1 1 0 00-.364 1.118l1.093 3.362c.3.921-.755 1.688-1.538 1.118l-2.86-2.078a1 1 0 00-1.176 0l-2.86 2.078c-.783.57-1.838-.197-1.539-1.118l1.093-3.362a1 1 0 00-.364-1.118L2.88 8.79c-.783-.57-.38-1.81.588-1.81h3.534a1 1 0 00.95-.69l1.093-3.362z"/>
                                </svg>
                                <span class="text-gray-700">Customer Reviews</span>
                            </a>
                            <a href="../auth/logout.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-red-600">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="lg:w-2/3 space-y-6">
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Farm Information</h2>
                    </div>
                    
                    <form method="POST" action="" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                <input type="text" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Farm Address</label>
                                <textarea name="address" 
                                          rows="2"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                    name="update_profile"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Update Farm Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Change Password</h2>
                    </div>
                    
                    <form method="POST" action="" class="p-6" onsubmit="return validatePasswordForm()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                    name="change_password"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Farm Statistics Dashboard -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Farm Statistics</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="stat-card bg-blue-50 p-4 rounded-lg">
                                <div class="text-blue-600 text-2xl font-bold"><?php echo $stats['total_products']; ?></div>
                                <div class="text-sm text-gray-600">Total Products</div>
                            </div>
                            
                            <div class="stat-card bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 text-2xl font-bold"><?php echo $stats['total_orders']; ?></div>
                                <div class="text-sm text-gray-600">Orders Received</div>
                            </div>
                            
                            <div class="stat-card bg-yellow-50 p-4 rounded-lg">
                                <div class="text-yellow-600 text-2xl font-bold"><?php echo $stats['pending_orders']; ?></div>
                                <div class="text-sm text-gray-600">Pending Orders</div>
                            </div>
                            
                            <div class="stat-card bg-red-50 p-4 rounded-lg">
                                <div class="text-red-600 text-2xl font-bold"><?php echo $stats['low_stock']; ?></div>
                                <div class="text-sm text-gray-600">Low Stock Items</div>
                            </div>
                        </div>

                        <!-- Recent Products -->
                        <?php if (!empty($recent_products)): ?>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-700 mb-3">Recent Products</h3>
                            <div class="space-y-2">
                                <?php foreach ($recent_products as $product): ?>
                                <div class="product-item flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($product['name']); ?></span>
                                        <span class="text-sm text-gray-500 ml-2">Stock: <?php echo $product['stock_quantity']; ?></span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="font-bold text-[#10854d]">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <a href="products/edit.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Recent Orders -->
                        <?php if (!empty($recent_orders)): ?>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Recent Orders</h3>
                            <div class="space-y-2">
                                <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item p-3 bg-gray-50 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="font-medium text-gray-800">Order #<?php echo str_pad($order['order_id'], 8, '0', STR_PAD_LEFT); ?></span>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($order['product_name']); ?> × <?php echo $order['quantity']; ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Customer: <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> • 
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold text-[#10854d]">₱<?php echo number_format($order['total_price'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Password validation
        function validatePasswordForm() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass.length < 6) {
                alert('New password must be at least 6 characters long');
                return false;
            }
            
            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return false;
            }
            
            return true;
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
