<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'driver') {
    header('Location: ../auth/login.php');
    exit();
}

$driver_id = (int)$_SESSION['user_id'];
$driver_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($driver_name === '') {
    $driver_name = $_SESSION['name'] ?? 'Driver';
}

$message = '';
$message_type = '';
$search = trim($_GET['q'] ?? '');

function map_status_badge(string $status): string {
    switch ($status) {
        case 'confirmed':
            return 'bg-blue-100 text-blue-700';
        case 'processing':
            return 'bg-yellow-100 text-yellow-700';
        case 'shipped':
            return 'bg-indigo-100 text-indigo-700';
        case 'delivered':
            return 'bg-green-100 text-green-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['claim_delivery'])) {
            $order_id = (int)($_POST['order_id'] ?? 0);

            $claim_stmt = $pdo->prepare("\n                UPDATE orders\n                SET delivery_driver_id = ?,\n                    delivery_claimed_at = NOW(),\n                    order_status = CASE WHEN order_status = 'confirmed' THEN 'processing' ELSE order_status END\n                WHERE id = ?\n                  AND delivery_driver_id IS NULL\n                  AND order_status = 'confirmed'\n            ");
            $claim_stmt->execute([$driver_id, $order_id]);

            if ($claim_stmt->rowCount() > 0) {
                $track_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, updated_by) VALUES (?, 'processing', ?, ?)");
                $track_stmt->execute([$order_id, 'Delivery claimed by driver and prepared for dispatch.', $driver_id]);

                $message = 'Delivery assigned to you successfully.';
                $message_type = 'success';
            } else {
                $message = 'This order is not available anymore.';
                $message_type = 'error';
            }
        }

        if (isset($_POST['release_delivery'])) {
            $order_id = (int)($_POST['order_id'] ?? 0);

            $release_stmt = $pdo->prepare("\n                UPDATE orders\n                SET delivery_driver_id = NULL,\n                    delivery_claimed_at = NULL,\n                    order_status = 'confirmed'\n                WHERE id = ?\n                  AND delivery_driver_id = ?\n                  AND order_status IN ('confirmed', 'processing')\n            ");
            $release_stmt->execute([$order_id, $driver_id]);

            if ($release_stmt->rowCount() > 0) {
                $track_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, updated_by) VALUES (?, 'confirmed', ?, ?)");
                $track_stmt->execute([$order_id, 'Delivery released by driver and returned to available queue.', $driver_id]);

                $message = 'Delivery released back to available queue.';
                $message_type = 'success';
            } else {
                $message = 'Unable to release this delivery.';
                $message_type = 'error';
            }
        }

        if (isset($_POST['mark_shipped'])) {
            $order_id = (int)($_POST['order_id'] ?? 0);

            $ship_stmt = $pdo->prepare("\n                UPDATE orders\n                SET order_status = 'shipped'\n                WHERE id = ?\n                  AND delivery_driver_id = ?\n                  AND order_status IN ('processing', 'confirmed')\n            ");
            $ship_stmt->execute([$order_id, $driver_id]);

            if ($ship_stmt->rowCount() > 0) {
                $track_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, updated_by) VALUES (?, 'shipped', ?, ?)");
                $track_stmt->execute([$order_id, 'Order is out for delivery.', $driver_id]);

                $message = 'Order marked as out for delivery.';
                $message_type = 'success';
            } else {
                $message = 'Unable to update this delivery status.';
                $message_type = 'error';
            }
        }

        if (isset($_POST['mark_delivered'])) {
            $order_id = (int)($_POST['order_id'] ?? 0);
            $delivery_notes = trim($_POST['delivery_notes'] ?? '');

            if (!isset($_FILES['delivery_proof']) || ($_FILES['delivery_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Delivery proof image is required.');
            }

            $file = $_FILES['delivery_proof'];
            $max_size = 5 * 1024 * 1024;
            if (($file['size'] ?? 0) > $max_size) {
                throw new RuntimeException('Delivery proof must be 5MB or smaller.');
            }

            $tmp_name = $file['tmp_name'] ?? '';
            if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
                throw new RuntimeException('Invalid delivery proof upload.');
            }

            $mime = function_exists('mime_content_type') ? mime_content_type($tmp_name) : '';
            if (($mime === '' || $mime === false) && function_exists('getimagesize')) {
                $img_info = @getimagesize($tmp_name);
                if (is_array($img_info) && !empty($img_info['mime'])) {
                    $mime = $img_info['mime'];
                }
            }
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowed[$mime])) {
                $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
                $mime_from_ext = array_search($extension, $allowed, true);
                if ($mime_from_ext !== false) {
                    $mime = $mime_from_ext;
                } else {
                    throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
                }
            }

            $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'delivery_proofs';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
                throw new RuntimeException('Could not create delivery proof directory.');
            }

            $filename = 'delivery_' . $order_id . '_' . $driver_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            $destination = $upload_dir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp_name, $destination)) {
                throw new RuntimeException('Failed to upload delivery proof image.');
            }

            $proof_path = 'assets/uploads/delivery_proofs/' . $filename;

            $deliver_stmt = $pdo->prepare("\n                UPDATE orders\n                SET order_status = 'delivered',\n                    actual_delivery_date = NOW(),\n                    delivered_proof_image = ?,\n                    delivered_notes = ?\n                WHERE id = ?\n                  AND delivery_driver_id = ?\n                  AND order_status IN ('processing', 'shipped')\n            ");
            $deliver_stmt->execute([$proof_path, $delivery_notes, $order_id, $driver_id]);

            if ($deliver_stmt->rowCount() > 0) {
                $track_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, updated_by) VALUES (?, 'delivered', ?, ?)");
                $track_stmt->execute([$order_id, 'Order marked delivered with proof by assigned driver.', $driver_id]);

                $message = 'Order marked as delivered with proof image.';
                $message_type = 'success';
            } else {
                @unlink($destination);
                $message = 'Unable to mark this order as delivered.';
                $message_type = 'error';
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

$stats = [
    'available' => 0,
    'active' => 0,
    'delivered' => 0,
];
$available_orders = [];
$my_active_orders = [];
$recent_delivered = [];

try {
    $stats_stmt = $pdo->prepare("\n        SELECT\n            SUM(CASE WHEN delivery_driver_id IS NULL AND order_status = 'confirmed' THEN 1 ELSE 0 END) AS available_count,\n            SUM(CASE WHEN delivery_driver_id = ? AND order_status IN ('processing', 'shipped', 'confirmed') THEN 1 ELSE 0 END) AS active_count,\n            SUM(CASE WHEN delivery_driver_id = ? AND order_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count\n        FROM orders\n    ");
    $stats_stmt->execute([$driver_id, $driver_id]);
    $stats_row = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['available'] = (int)($stats_row['available_count'] ?? 0);
    $stats['active'] = (int)($stats_row['active_count'] ?? 0);
    $stats['delivered'] = (int)($stats_row['delivered_count'] ?? 0);

    $available_sql = "\n        SELECT o.id, o.order_number, o.order_status, o.created_at, o.shipping_name, o.shipping_phone, o.shipping_address,\n               u.first_name, u.last_name\n        FROM orders o\n        JOIN users u ON o.customer_id = u.id\n        WHERE o.delivery_driver_id IS NULL\n          AND o.order_status = 'confirmed'\n    ";
    $available_params = [];
    if ($search !== '') {
        $available_sql .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.shipping_name LIKE ? OR o.shipping_address LIKE ?)";
        $q = '%' . $search . '%';
        $available_params = [$q, $q, $q, $q, $q];
    }
    $available_sql .= " ORDER BY o.created_at ASC LIMIT 25";

    $available_stmt = $pdo->prepare($available_sql);
    $available_stmt->execute($available_params);
    $available_orders = $available_stmt->fetchAll(PDO::FETCH_ASSOC);

    $active_sql = "\n        SELECT o.id, o.order_number, o.order_status, o.created_at, o.shipping_name, o.shipping_phone, o.shipping_address,\n               u.first_name, u.last_name\n        FROM orders o\n        JOIN users u ON o.customer_id = u.id\n        WHERE o.delivery_driver_id = ?\n          AND o.order_status IN ('processing', 'shipped', 'confirmed')\n    ";
    $active_params = [$driver_id];
    if ($search !== '') {
        $active_sql .= " AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.shipping_name LIKE ? OR o.shipping_address LIKE ?)";
        $q = '%' . $search . '%';
        $active_params = [$driver_id, $q, $q, $q, $q, $q];
    }
    $active_sql .= " ORDER BY o.created_at ASC LIMIT 25";

    $active_stmt = $pdo->prepare($active_sql);
    $active_stmt->execute($active_params);
    $my_active_orders = $active_stmt->fetchAll(PDO::FETCH_ASSOC);

    $done_stmt = $pdo->prepare("\n        SELECT id, order_number, actual_delivery_date, delivered_proof_image\n        FROM orders\n        WHERE delivery_driver_id = ?\n          AND order_status = 'delivered'\n        ORDER BY actual_delivery_date DESC\n        LIMIT 12\n    ");
    $done_stmt->execute([$driver_id]);
    $recent_delivered = $done_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Driver module database fields are missing. Please run the driver migration SQL first.';
    $message_type = 'error';
    error_log('Driver dashboard error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(140deg, #f6f9f8 0%, #edf5ef 55%, #f7f9fb 100%); }
        .dashboard-card { transition: all 0.28s ease; background: #fff; border: 1px solid rgba(16, 133, 77, 0.12); }
        .dashboard-card:hover { transform: translateY(-2px); box-shadow: 0 18px 25px -15px rgba(16, 133, 77, 0.25); }
        .hero { background: linear-gradient(120deg, #0f8b50 0%, #0d6e40 45%, #10a164 100%); }
        .nav-pill { border: 1px solid rgba(16, 133, 77, 0.2); }
        .nav-pill:hover { background: #ecfdf3; color: #0f8b50; border-color: rgba(16, 133, 77, 0.35); }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-40 border-b border-green-100">
        <div class="container mx-auto px-4">
            <div class="h-16 flex items-center justify-between gap-4">
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold">H</span>
                    </div>
                    <span class="text-xl font-extrabold text-[#10854d] tracking-wide">Harvee Driver</span>
                </a>

                <div class="hidden lg:flex items-center gap-2">
                    <a href="#available" class="nav-pill px-3 py-1.5 rounded-full text-sm text-gray-700"><i class="fas fa-list-check mr-1"></i> Available</a>
                    <a href="#active" class="nav-pill px-3 py-1.5 rounded-full text-sm text-gray-700"><i class="fas fa-truck-fast mr-1"></i> Active</a>
                    <a href="#completed" class="nav-pill px-3 py-1.5 rounded-full text-sm text-gray-700"><i class="fas fa-check-circle mr-1"></i> Completed</a>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-gray-600 text-sm hidden md:inline">Signed in as <span class="font-semibold"><?php echo htmlspecialchars($driver_name); ?></span></span>
                    <a href="../auth/logout.php" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="hero rounded-2xl p-8 text-white mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold">Delivery Operations</h1>
                    <p class="text-white/90 mt-2">Farmers confirm orders. Drivers own the shipping and delivery flow with proof verification.</p>
                </div>
                <form method="GET" class="w-full md:w-[420px]">
                    <div class="flex items-center gap-2 bg-white/20 rounded-xl p-2 backdrop-blur-sm">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search order #, customer, or address" class="w-full bg-white text-gray-800 rounded-lg px-3 py-2 text-sm outline-none">
                        <button type="submit" class="px-3 py-2 bg-white text-[#10854d] rounded-lg text-sm font-semibold">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-sm text-gray-500">Available Deliveries</p>
                <p class="text-3xl font-bold text-[#10854d]"><?php echo $stats['available']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Confirmed by farmer and ready to claim</p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-sm text-gray-500">My Active Deliveries</p>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $stats['active']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Processing / out for delivery</p>
            </div>
            <div class="dashboard-card rounded-xl p-4">
                <p class="text-sm text-gray-500">Completed by Me</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['delivered']; ?></p>
                <p class="text-xs text-gray-500 mt-1">Delivered with proof image</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <section id="available" class="dashboard-card rounded-xl overflow-hidden scroll-mt-24">
                <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800"><i class="fas fa-list-check mr-2 text-[#10854d]"></i>Available Orders</h2>
                    <span class="text-xs text-gray-500"><?php echo count($available_orders); ?> shown</span>
                </div>
                <div class="p-5 space-y-4 max-h-[650px] overflow-y-auto">
                    <?php if (empty($available_orders)): ?>
                        <p class="text-gray-500 text-sm">No available deliveries right now.</p>
                    <?php else: ?>
                        <?php foreach ($available_orders as $order): ?>
                            <?php $customer_name = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between gap-3 mb-2">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['order_number'] ?: ('ORD-' . str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT))); ?></p>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo map_status_badge($order['order_status'] ?? 'pending'); ?>"><?php echo htmlspecialchars(ucfirst($order['order_status'] ?? 'pending')); ?></span>
                                </div>
                                <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($customer_name ?: ($order['shipping_name'] ?? 'N/A')); ?></p>
                                <p class="text-sm text-gray-600">Phone: <?php echo htmlspecialchars($order['shipping_phone'] ?? 'N/A'); ?></p>
                                <p class="text-sm text-gray-600">Address: <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Placed: <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                        <button type="submit" name="claim_delivery" class="px-4 py-2 bg-[#10854d] text-white text-sm rounded-lg hover:bg-[#0d6e40]">Claim Delivery</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="active" class="dashboard-card rounded-xl overflow-hidden scroll-mt-24">
                <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="font-bold text-gray-800"><i class="fas fa-truck-fast mr-2 text-[#10854d]"></i>My Active Deliveries</h2>
                    <span class="text-xs text-gray-500"><?php echo count($my_active_orders); ?> shown</span>
                </div>
                <div class="p-5 space-y-5 max-h-[650px] overflow-y-auto">
                    <?php if (empty($my_active_orders)): ?>
                        <p class="text-gray-500 text-sm">You have no active deliveries.</p>
                    <?php else: ?>
                        <?php foreach ($my_active_orders as $order): ?>
                            <?php $customer_name = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between gap-3 mb-2">
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['order_number'] ?: ('ORD-' . str_pad((string)$order['id'], 6, '0', STR_PAD_LEFT))); ?></p>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo map_status_badge($order['order_status'] ?? 'pending'); ?>"><?php echo htmlspecialchars(ucfirst($order['order_status'] ?? 'pending')); ?></span>
                                </div>
                                <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($customer_name ?: ($order['shipping_name'] ?? 'N/A')); ?></p>
                                <p class="text-sm text-gray-600">Phone: <?php echo htmlspecialchars($order['shipping_phone'] ?? 'N/A'); ?></p>
                                <p class="text-sm text-gray-600">Address: <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <form method="POST">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                        <button type="submit" name="mark_shipped" class="px-3 py-2 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">Out for Delivery</button>
                                    </form>
                                    <?php if (($order['order_status'] ?? '') !== 'shipped'): ?>
                                        <form method="POST" onsubmit="return confirm('Release this delivery back to queue?');">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <button type="submit" name="release_delivery" class="px-3 py-2 bg-amber-500 text-white text-xs rounded-lg hover:bg-amber-600">Release</button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" enctype="multipart/form-data" class="mt-3 p-3 bg-gray-50 rounded-lg space-y-2">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                    <label class="block text-xs font-semibold text-gray-600">Delivery Proof (required)</label>
                                    <input type="file" name="delivery_proof" accept="image/jpeg,image/png,image/webp" required class="block w-full text-xs text-gray-700">
                                    <textarea name="delivery_notes" rows="2" placeholder="Optional notes (receiver name, landmarks, etc.)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                                    <button type="submit" name="mark_delivered" class="w-full px-4 py-2 bg-[#10854d] text-white text-sm rounded-lg hover:bg-[#0d6e40]">Mark Delivered + Upload Proof</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section id="completed" class="dashboard-card rounded-xl mt-6 overflow-hidden scroll-mt-24">
            <div class="px-5 py-4 border-b bg-gray-50">
                <h2 class="font-bold text-gray-800"><i class="fas fa-check-circle mr-2 text-[#10854d]"></i>Recently Delivered</h2>
            </div>
            <div class="p-5 overflow-x-auto">
                <?php if (empty($recent_delivered)): ?>
                    <p class="text-gray-500 text-sm">No completed deliveries yet.</p>
                <?php else: ?>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">Order</th>
                                <th class="py-2">Delivered At</th>
                                <th class="py-2">Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_delivered as $row): ?>
                                <tr class="border-b">
                                    <td class="py-2"><?php echo htmlspecialchars($row['order_number'] ?: ('ORD-' . str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT))); ?></td>
                                    <td class="py-2"><?php echo !empty($row['actual_delivery_date']) ? date('M d, Y h:i A', strtotime($row['actual_delivery_date'])) : '-'; ?></td>
                                    <td class="py-2">
                                        <?php if (!empty($row['delivered_proof_image'])): ?>
                                            <a class="inline-flex items-center gap-2 text-[#10854d] hover:underline" href="../<?php echo htmlspecialchars($row['delivered_proof_image']); ?>" target="_blank">
                                                <img src="../<?php echo htmlspecialchars($row['delivered_proof_image']); ?>" alt="Proof" class="w-10 h-10 rounded object-cover border border-gray-200">
                                                <span>View</span>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
