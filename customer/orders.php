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
$message_type = '';

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    
    try {
        // Check if order can be cancelled (only pending orders)
        $stmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();
        
        if ($order && $order['order_status'] === 'pending') {
            // Update order status to cancelled
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
            
            // Restore product stock
            $stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            foreach ($order_items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $message = "Order #" . $order_id . " has been cancelled successfully.";
            $message_type = "success";
        } else {
            $message = "This order cannot be cancelled.";
            $message_type = "error";
        }
    } catch (Exception $e) {
        $message = "Error cancelling order: " . $e->getMessage();
        $message_type = "error";
    }
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$query = "
    SELECT o.*, 
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.customer_id = ?
";

if ($filter !== 'all') {
    if ($filter === 'paid') {
        $query .= " AND o.payment_status = 'paid'";
    } elseif ($filter === 'completed') {
        $query .= " AND o.order_status = 'delivered'";
    } else {
        $query .= " AND o.order_status = ?";
    }
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);

if ($filter !== 'all') {
    if ($filter === 'paid' || $filter === 'completed') {
        $stmt->execute([$user_id]);
    } else {
        $stmt->execute([$user_id, $filter]);
    }
} else {
    $stmt->execute([$user_id]);
}

$orders = $stmt->fetchAll();

$stats_stmt = $pdo->prepare("SELECT order_status, payment_status, total FROM orders WHERE customer_id = ?");
$stats_stmt->execute([$user_id]);
$all_orders = $stats_stmt->fetchAll();

// Get order statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'paid' => 0,
    'shipped' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'total_spent' => 0
];

foreach ($all_orders as $order) {
    $stats['total']++;
    if (($order['order_status'] ?? '') === 'pending') {
        $stats['pending']++;
    }
    if (($order['payment_status'] ?? '') === 'paid') {
        $stats['paid']++;
    }
    if (($order['order_status'] ?? '') === 'shipped') {
        $stats['shipped']++;
    }
    if (($order['order_status'] ?? '') === 'delivered') {
        $stats['completed']++;
    }
    if (($order['order_status'] ?? '') === 'cancelled') {
        $stats['cancelled']++;
    }
    if (($order['order_status'] ?? '') !== 'cancelled') {
        $stats['total_spent'] += $order['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-paid {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-shipped {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
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
                    <h1 class="text-2xl font-bold text-[#10854d]">My Orders</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="cart.php" class="text-[#10854d] hover:underline flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        View Cart
                    </a>
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
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Total Orders</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Pending</div>
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Paid</div>
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['paid']; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Shipped</div>
                <div class="text-2xl font-bold text-sky-600"><?php echo $stats['shipped']; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Completed</div>
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['completed']; ?></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-gray-500 text-sm mb-1">Total Spent</div>
                <div class="text-2xl font-bold text-[#10854d]">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between">
                <div class="flex items-center space-x-2 mb-2 sm:mb-0">
                    <span class="text-gray-700 font-medium">Filter by:</span>
                    <div class="flex flex-wrap gap-2">
                        <a href="?filter=all" 
                           class="px-4 py-2 rounded-full text-sm font-medium <?php echo $filter === 'all' ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            All
                        </a>
                        <a href="?filter=pending" 
                           class="px-4 py-2 rounded-full text-sm font-medium <?php echo $filter === 'pending' ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Pending
                        </a>
                        <a href="?filter=paid" 
                           class="px-4 py-2 rounded-full text-sm font-medium <?php echo $filter === 'paid' ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Paid
                        </a>
                        <a href="?filter=shipped" 
                           class="px-4 py-2 rounded-full text-sm font-medium <?php echo $filter === 'shipped' ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Shipped
                        </a>
                        <a href="?filter=completed" 
                           class="px-4 py-2 rounded-full text-sm font-medium <?php echo $filter === 'completed' ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Completed
                        </a>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    Showing <?php echo count($orders); ?> order(s)
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-700 mb-3">No orders found</h2>
                <p class="text-gray-500 mb-6 max-w-md mx-auto">
                    You haven't placed any orders yet. Start shopping to see your orders here!
                </p>
                <a href="browse.php" class="inline-block px-8 py-3 bg-[#10854d] text-white font-medium rounded-full hover:bg-[#0d6e40] transition-colors shadow-lg">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): 
                    // Get order items for this order
                    $stmt = $pdo->prepare("
                        SELECT oi.*,
                               u.name as farmer_name
                        FROM order_items oi
                        LEFT JOIN users u ON oi.farmer_id = u.id
                        WHERE oi.order_id = ?
                    ");
                    $stmt->execute([$order['id']]);
                    $order_items = $stmt->fetchAll();
                ?>
                    <div class="order-card bg-white rounded-xl shadow-sm overflow-hidden">
                        <!-- Order Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-2 sm:mb-0">
                                <div>
                                    <span class="text-sm text-gray-500">Order #</span>
                                    <span class="font-mono font-bold text-gray-800"><?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-500 mr-2">Status:</span>
                                    <?php
                                    $status_class = '';
                                    $display_status = $order['order_status'] ?? 'pending';
                                    if ($display_status === 'delivered') {
                                        $display_status = 'completed';
                                    } elseif ($display_status === 'confirmed' || $display_status === 'processing') {
                                        $display_status = 'pending';
                                    }

                                    switch($display_status) {
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'paid':
                                            $status_class = 'status-paid';
                                            break;
                                        case 'shipped':
                                            $status_class = 'status-shipped';
                                            break;
                                        case 'completed':
                                            $status_class = 'status-completed';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($display_status); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-sm text-gray-500">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                </div>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                   class="text-[#10854d] hover:text-[#0d6e40] font-medium text-sm">
                                    View Details →
                                </a>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="divide-y divide-gray-100">
                            <?php foreach (array_slice($order_items, 0, 2) as $item): ?>
                                <div class="px-6 py-4">
                                    <div class="flex justify-between items-center">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800 mb-1">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                Farmer: <?php echo htmlspecialchars($item['farmer_name']); ?> • 
                                                Qty: <?php echo $item['quantity']; ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-[#10854d]">
                                                ₱<?php echo number_format(($item['unit_price'] ?? 0) * ($item['quantity'] ?? 0), 2); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ₱<?php echo number_format($item['unit_price'] ?? 0, 2); ?> each
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($order_items) > 2): ?>
                                <div class="px-6 py-3 bg-gray-50 text-sm text-gray-500">
                                    + <?php echo count($order_items) - 2; ?> more item(s)
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Order Footer -->
                        <div class="bg-gray-50 px-6 py-4 border-t">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <span class="text-sm text-gray-500">Items:</span>
                                        <span class="font-bold text-gray-800 ml-1"><?php echo $order['item_count']; ?></span>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-500">Total:</span>
                                        <span class="font-bold text-[#10854d] ml-1">₱<?php echo number_format($order['total'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Order Actions -->
                                <div class="flex gap-2">
                                    <?php if (($order['order_status'] ?? '') === 'pending'): ?>
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" 
                                                    name="cancel_order"
                                                    class="px-4 py-2 bg-red-100 text-red-600 rounded-full text-sm font-medium hover:bg-red-200 transition-colors">
                                                Cancel Order
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (($order['order_status'] ?? '') === 'delivered'): ?>
                                        <a href="rate_order.php?id=<?php echo $order['id']; ?>" 
                                           class="px-4 py-2 bg-yellow-100 text-yellow-600 rounded-full text-sm font-medium hover:bg-yellow-200 transition-colors">
                                            Rate Products
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-300 transition-colors">
                                        Track Order
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
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
