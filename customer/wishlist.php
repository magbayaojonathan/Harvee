<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? ($_SESSION['name'] ?? 'Customer');
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        $wishlist_id = (int)($_POST['wishlist_id'] ?? 0);
        if ($wishlist_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
            $stmt->execute([$wishlist_id, $user_id]);
            $message = 'Item removed from wishlist.';
            $message_type = 'success';
        }
    }

    if (isset($_POST['move_to_cart'])) {
        $wishlist_id = (int)($_POST['wishlist_id'] ?? 0);
        if ($wishlist_id > 0) {
            $stmt = $pdo->prepare("
                SELECT w.id AS wishlist_id, p.id AS product_id, p.price, p.stock_quantity
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.id = ? AND w.user_id = ?
            ");
            $stmt->execute([$wishlist_id, $user_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                $message = 'Wishlist item not found.';
                $message_type = 'error';
            } elseif ((int)$item['stock_quantity'] <= 0) {
                $message = 'Product is out of stock.';
                $message_type = 'error';
            } else {
                $cart_stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $cart_stmt->execute([$user_id, (int)$item['product_id']]);
                $existing = $cart_stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $new_qty = (int)$existing['quantity'] + 1;
                    if ($new_qty > (int)$item['stock_quantity']) {
                        $new_qty = (int)$item['stock_quantity'];
                    }
                    $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $update_stmt->execute([$new_qty, (int)$existing['id']]);
                } else {
                    $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, price_at_time) VALUES (?, ?, 1, ?)");
                    $insert_stmt->execute([$user_id, (int)$item['product_id'], $item['price']]);
                }

                $remove_stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
                $remove_stmt->execute([$wishlist_id, $user_id]);

                $message = 'Item moved to cart.';
                $message_type = 'success';
            }
        }
    }
}

$wishlist_items = [];
$wishlist_total = 0;

try {
    $stmt = $pdo->prepare("
        SELECT
            w.id AS wishlist_id,
            w.created_at AS added_at,
            p.id AS product_id,
            p.name AS product_name,
            p.description,
            p.price,
            p.stock_quantity,
            c.name AS category_name,
            u.first_name AS farmer_first,
            u.last_name AS farmer_last
        FROM wishlist w
        LEFT JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.farmer_id = u.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($wishlist_items as $item) {
        $wishlist_total += (float)($item['price'] ?? 0);
    }
} catch (PDOException $e) {
    error_log('Wishlist page error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .wishlist-card {
            transition: all 0.2s ease;
        }
        .wishlist-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        .gradient-text {
            background: linear-gradient(135deg, #10854d, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="flex items-center space-x-2 text-gray-700 hover:text-[#10854d]">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold gradient-text">My Wishlist</h1>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="browse.php" class="text-[#10854d] hover:underline">Browse Products</a>
                    <span class="text-gray-300">|</span>
                    <a href="cart.php" class="text-[#10854d] hover:underline">View Cart</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Saved Items</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($wishlist_items); ?></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Estimated Value</p>
                <p class="text-2xl font-bold text-[#10854d]">&#8369;<?php echo number_format($wishlist_total, 2); ?></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm">
                <p class="text-sm text-gray-500">Customer</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($first_name); ?></p>
            </div>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-heart text-2xl text-gray-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-700 mb-2">Your wishlist is empty</h2>
                <p class="text-gray-500 mb-6">Save products you like so you can buy them later.</p>
                <a href="browse.php" class="inline-block px-6 py-3 bg-[#10854d] text-white rounded-full hover:bg-[#0d6e40]">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-card bg-white rounded-xl shadow-sm p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($item['product_name'] ?? 'Product unavailable'); ?>
                                    </h3>
                                    <?php if (!empty($item['category_name'])): ?>
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 mb-2">
                                    <?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?>
                                </p>
                                <div class="text-sm text-gray-600">
                                    Farmer: <?php echo htmlspecialchars(trim(($item['farmer_first'] ?? '') . ' ' . ($item['farmer_last'] ?? '')) ?: 'Unknown'); ?>
                                </div>
                                <div class="text-sm mt-1 <?php echo ((int)($item['stock_quantity'] ?? 0) > 0) ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo ((int)($item['stock_quantity'] ?? 0) > 0) ? 'In stock' : 'Out of stock'; ?>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="text-xl font-bold text-[#10854d] min-w-[110px] text-right">
                                    &#8369;<?php echo number_format((float)($item['price'] ?? 0), 2); ?>
                                </div>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="wishlist_id" value="<?php echo (int)$item['wishlist_id']; ?>">
                                    <button
                                        type="submit"
                                        name="move_to_cart"
                                        class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40] text-sm"
                                        <?php echo ((int)($item['stock_quantity'] ?? 0) <= 0) ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''; ?>
                                    >
                                        <i class="fas fa-cart-plus mr-1"></i> Move to Cart
                                    </button>
                                </form>
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Remove this item from wishlist?');">
                                    <input type="hidden" name="wishlist_id" value="<?php echo (int)$item['wishlist_id']; ?>">
                                    <button type="submit" name="remove_item" class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 text-sm">
                                        <i class="fas fa-trash mr-1"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
