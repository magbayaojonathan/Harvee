<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$error = '';
$success = '';

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Ensure the order belongs to current customer
$order_stmt = $pdo->prepare("
    SELECT id, order_number, customer_id, farmer_id, created_at
    FROM orders
    WHERE id = ? AND customer_id = ?
");
$order_stmt->execute([$order_id, $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Fetch farmers related to this order
$farmer_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name, u.first_name, u.last_name, u.email, u.phone
    FROM order_items oi
    JOIN users u ON oi.farmer_id = u.id
    WHERE oi.order_id = ?
");
$farmer_stmt->execute([$order_id]);
$farmers = $farmer_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($farmers) && !empty($order['farmer_id'])) {
    $single_farmer_stmt = $pdo->prepare("
        SELECT id, name, first_name, last_name, email, phone
        FROM users
        WHERE id = ?
    ");
    $single_farmer_stmt->execute([(int)$order['farmer_id']]);
    $single = $single_farmer_stmt->fetch(PDO::FETCH_ASSOC);
    if ($single) {
        $farmers[] = $single;
    }
}

if (empty($farmers)) {
    $error = 'No farmer information found for this order.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $allowed_receiver_ids = array_map(static fn($f) => (int)$f['id'], $farmers);
    if (!in_array($receiver_id, $allowed_receiver_ids, true)) {
        $error = 'Invalid farmer selected.';
    } elseif ($message === '') {
        $error = 'Message is required.';
    } else {
        if ($subject === '') {
            $subject = 'Order #' . str_pad((string)$order_id, 8, '0', STR_PAD_LEFT) . ' inquiry';
        }

        $insert_stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $insert_stmt->execute([$user_id, $receiver_id, $subject, $message]);

        $success = 'Message sent to farmer successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Farmer - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <a href="order_details.php?id=<?php echo $order_id; ?>" class="inline-flex items-center text-[#10854d] hover:underline mb-6">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Order Details
        </a>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-2xl font-bold text-[#10854d] mb-2">Contact Farmer</h1>
            <p class="text-gray-600 mb-6">
                Order #<?php echo str_pad((string)$order_id, 8, '0', STR_PAD_LEFT); ?>
            </p>

            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($farmers)): ?>
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($farmers as $farmer): ?>
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <p class="font-semibold text-gray-800">
                                <?php
                                $display_name = trim((string)($farmer['name'] ?? ''));
                                if ($display_name === '') {
                                    $display_name = trim((string)($farmer['first_name'] ?? '') . ' ' . (string)($farmer['last_name'] ?? ''));
                                }
                                echo htmlspecialchars($display_name !== '' ? $display_name : 'Farmer');
                                ?>
                            </p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($farmer['email'] ?? 'No email'); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($farmer['phone'] ?? 'No phone'); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Farmer</label>
                        <select name="receiver_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            <?php foreach ($farmers as $farmer): ?>
                                <?php
                                $display_name = trim((string)($farmer['name'] ?? ''));
                                if ($display_name === '') {
                                    $display_name = trim((string)($farmer['first_name'] ?? '') . ' ' . (string)($farmer['last_name'] ?? ''));
                                }
                                ?>
                                <option value="<?php echo (int)$farmer['id']; ?>">
                                    <?php echo htmlspecialchars($display_name !== '' ? $display_name : 'Farmer #' . (int)$farmer['id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" name="subject" maxlength="255" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" placeholder="Question about your order">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea name="message" rows="6" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" placeholder="Type your message here..."></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                        Send Message
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
