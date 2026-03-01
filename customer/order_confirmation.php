<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_SESSION['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

$order = null;
$items = [];
$error = '';

try {
    $order_stmt = $pdo->prepare("
        SELECT
            o.*,
            u.name,
            u.first_name,
            u.last_name,
            u.email,
            u.phone
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.id = ? AND o.customer_id = ?
        LIMIT 1
    ");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    $items_stmt = $pdo->prepare("
        SELECT
            oi.product_name,
            oi.quantity,
            oi.unit_price,
            oi.total_price,
            u.first_name AS farmer_first,
            u.last_name AS farmer_last
        FROM order_items oi
        LEFT JOIN users u ON oi.farmer_id = u.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Unable to load order confirmation.';
    error_log('Order confirmation error: ' . $e->getMessage());
}

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += (float)($item['total_price'] ?? 0);
}

$recorded_total = (float)($order['total'] ?? 0);
$extra_fees = max(0, $recorded_total - $subtotal);
$receipt_no = 'RCPT-' . str_pad((string)$order_id, 8, '0', STR_PAD_LEFT);
$customer_name = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
if ($customer_name === '') {
    $customer_name = $order['name'] ?? 'Customer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($receipt_no); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .receipt-wrap {
            background: white;
            border: 2px dashed #cbd5e1;
            position: relative;
        }
        .receipt-wrap:before,
        .receipt-wrap:after {
            content: '';
            position: absolute;
            left: -10px;
            width: 20px;
            height: 20px;
            background: #f1f5f9;
            border-radius: 50%;
        }
        .receipt-wrap:before { top: 160px; }
        .receipt-wrap:after { bottom: 160px; }
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .receipt-wrap { border: none; }
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto no-print mb-4 flex items-center justify-between">
            <a href="orders.php" class="inline-flex items-center text-[#10854d] hover:underline">
                <i class="fas fa-arrow-left mr-2"></i> Back to Orders
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
        </div>

        <div class="max-w-3xl mx-auto receipt-wrap rounded-2xl shadow-lg overflow-hidden">
            <div class="px-8 py-6 bg-gradient-to-r from-[#10854d] to-[#0d6e40] text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-wide">HARVEE MARKET</h1>
                        <p class="text-white/90 text-sm">Official Order Receipt</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase text-white/80">Receipt No.</p>
                        <p class="font-bold"><?php echo htmlspecialchars($receipt_no); ?></p>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6 border-b border-dashed border-slate-300">
                <?php if ($error): ?>
                    <p class="text-red-600"><?php echo htmlspecialchars($error); ?></p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <p class="text-slate-500">Billed To</p>
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($customer_name); ?></p>
                            <p class="text-slate-600"><?php echo htmlspecialchars($order['email'] ?? ''); ?></p>
                            <p class="text-slate-600"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></p>
                        </div>
                        <div class="md:text-right">
                            <p><span class="text-slate-500">Order #:</span> <span class="font-semibold text-slate-800"><?php echo str_pad((string)$order_id, 8, '0', STR_PAD_LEFT); ?></span></p>
                            <p><span class="text-slate-500">Date:</span> <span class="font-semibold text-slate-800"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></span></p>
                            <p><span class="text-slate-500">Status:</span> <span class="font-semibold text-slate-800 capitalize"><?php echo htmlspecialchars($order['order_status'] ?? 'pending'); ?></span></p>
                            <p><span class="text-slate-500">Payment:</span> <span class="font-semibold text-slate-800 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $order['payment_status'] ?? 'pending')); ?></span></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="px-8 py-6">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Items</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b">
                                <th class="py-2">Description</th>
                                <th class="py-2 text-right">Qty</th>
                                <th class="py-2 text-right">Unit</th>
                                <th class="py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-slate-500">No line items found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                        $farmer_name = trim(($item['farmer_first'] ?? '') . ' ' . ($item['farmer_last'] ?? ''));
                                        if ($farmer_name === '') { $farmer_name = 'Farmer'; }
                                    ?>
                                    <tr class="border-b border-slate-100">
                                        <td class="py-3">
                                            <p class="font-medium text-slate-800"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></p>
                                            <p class="text-xs text-slate-500">Sold by <?php echo htmlspecialchars($farmer_name); ?></p>
                                        </td>
                                        <td class="py-3 text-right text-slate-700"><?php echo (int)($item['quantity'] ?? 0); ?></td>
                                        <td class="py-3 text-right text-slate-700">&#8369;<?php echo number_format((float)($item['unit_price'] ?? 0), 2); ?></td>
                                        <td class="py-3 text-right font-semibold text-slate-800">&#8369;<?php echo number_format((float)($item['total_price'] ?? 0), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-8 pb-8">
                <div class="ml-auto max-w-sm text-sm space-y-2 border-t border-dashed border-slate-300 pt-4">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="font-medium text-slate-800">&#8369;<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Other Fees</span>
                        <span class="font-medium text-slate-800">&#8369;<?php echo number_format($extra_fees, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-base border-t border-slate-200 pt-2">
                        <span class="font-bold text-slate-800">Total Paid</span>
                        <span class="font-extrabold text-[#10854d]">&#8369;<?php echo number_format($recorded_total, 2); ?></span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-6 text-center">Thank you for shopping with Harvee Market.</p>
            </div>
        </div>
    </div>
</body>
</html>
