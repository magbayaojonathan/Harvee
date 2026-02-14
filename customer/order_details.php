<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email, u.phone, u.address
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Get order items with product and farmer details
$stmt = $pdo->prepare("
    SELECT oi.*, p.product_name, p.description,
           u.id as farmer_id, u.name as farmer_name, u.email as farmer_email, u.phone as farmer_phone
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.farmer_id = u.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal >= 1000 ? 0 : 50;
$service_fee = 10;
$total = $subtotal + $shipping + $service_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?> - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Back Button -->
        <a href="orders.php" class="inline-flex items-center text-[#10854d] hover:underline mb-6">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Orders
        </a>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>
                    </h1>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                        <?php
                        switch($order['status']) {
                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'paid': echo 'bg-blue-100 text-blue-800'; break;
                            case 'shipped': echo 'bg-sky-100 text-sky-800'; break;
                            case 'completed': echo 'bg-green-100 text-green-800'; break;
                            case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                        }
                        ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <p class="text-gray-500 mt-1">
                    Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                </p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Order Items -->
                    <div class="lg:col-span-2">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Order Items</h2>
                        <div class="space-y-4">
                            <?php foreach ($order_items as $item): ?>
                                <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                        <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <div class="text-sm">
                                            <span class="text-gray-600">Farmer:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($item['farmer_name']); ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Quantity: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-[#10854d]">
                                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Order Summary</h2>
                            
                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium">₱<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-medium"><?php echo $shipping > 0 ? '₱' . number_format($shipping, 2) : 'FREE'; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Service Fee</span>
                                    <span class="font-medium">₱<?php echo number_format($service_fee, 2); ?></span>
                                </div>
                                <div class="border-t border-gray-200 pt-3 mt-3">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-gray-800">Total</span>
                                        <span class="font-bold text-[#10854d] text-xl">₱<?php echo number_format($total, 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Information -->
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="font-semibold text-gray-800 mb-3">Customer Information</h3>
                                <div class="space-y-2 text-sm">
                                    <p><span class="text-gray-500">Name:</span> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><span class="text-gray-500">Email:</span> <?php echo htmlspecialchars($order['email']); ?></p>
                                    <p><span class="text-gray-500">Phone:</span> <?php echo htmlspecialchars($order['phone'] ?? 'Not provided'); ?></p>
                                    <p><span class="text-gray-500">Address:</span> <?php echo htmlspecialchars($order['address'] ?? 'Not provided'); ?></p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-6 space-y-3">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <form method="POST" action="orders.php" onsubmit="return confirm('Cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="cancel_order" 
                                                class="w-full py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                                            Cancel Order
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button onclick="window.print()" 
                                        class="w-full py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                    Print Order
                                </button>
                                
                                <a href="contact_farmer.php?order_id=<?php echo $order['id']; ?>" 
                                   class="block text-center w-full py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                    Contact Farmer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>