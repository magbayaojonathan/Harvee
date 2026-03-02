<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header('Location: browse.php');
    exit();
}

$product = null;
$reviews = [];
$avg_rating = 0;
$review_count = 0;

try {
    $stmt = $pdo->prepare("\n        SELECT p.*, c.name AS category_name,\n               u.first_name AS farmer_first_name, u.last_name AS farmer_last_name\n        FROM products p\n        LEFT JOIN categories c ON c.id = p.category_id\n        LEFT JOIN users u ON u.id = p.farmer_id\n        WHERE p.id = ?\n        LIMIT 1\n    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: browse.php');
        exit();
    }

    $review_stmt = $pdo->prepare("\n        SELECT r.rating, r.title, r.comment, r.images, r.created_at,\n               u.first_name, u.last_name\n        FROM reviews r\n        JOIN users u ON u.id = r.user_id\n        WHERE r.product_id = ? AND r.is_approved = 1\n        ORDER BY r.created_at DESC\n        LIMIT 10\n    ");
    $review_stmt->execute([$product_id]);
    $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($reviews)) {
        $sum = 0;
        foreach ($reviews as $r) {
            $sum += (int)$r['rating'];
        }
        $review_count = count($reviews);
        $avg_rating = $review_count > 0 ? $sum / $review_count : 0;
    }
} catch (PDOException $e) {
    error_log('customer product page error: ' . $e->getMessage());
}

if (!$product) {
    header('Location: browse.php');
    exit();
}

$stock_quantity = (int)($product['stock_quantity'] ?? 0);
$is_out_of_stock = $stock_quantity <= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'] ?? 'Product'); ?> - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; }
    </style>
</head>
<body>
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="browse.php" class="text-gray-700 hover:text-[#10854d]"><i class="fas fa-arrow-left mr-2"></i>Back to Browse</a>
                <h1 class="text-xl font-bold text-[#10854d]">Product Details</h1>
            </div>
            <a href="cart.php" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                <i class="fas fa-shopping-cart mr-2"></i>Cart
            </a>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 card p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($product['name'] ?? ''); ?></h2>
                        <p class="text-gray-500 mt-1"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-extrabold text-[#10854d]">&#8369;<?php echo number_format((float)($product['price'] ?? 0), 2); ?></p>
                        <p class="text-xs text-gray-500">per <?php echo htmlspecialchars($product['unit'] ?? 'unit'); ?></p>
                    </div>
                </div>

                <div class="h-72 bg-gradient-to-br from-green-50 to-gray-100 rounded-xl flex items-center justify-center mb-5">
                    <i class="fas fa-seedling text-6xl text-gray-300"></i>
                </div>

                <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-500">Seller:</span>
                        <span class="font-semibold text-gray-800 ml-1"><?php echo htmlspecialchars(trim(($product['farmer_first_name'] ?? '') . ' ' . ($product['farmer_last_name'] ?? '')) ?: 'Farmer'); ?></span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <span class="text-gray-500">Stock:</span>
                        <span class="font-semibold ml-1 <?php echo $is_out_of_stock ? 'text-red-600' : 'text-green-600'; ?>"><?php echo $stock_quantity; ?> available</span>
                    </div>
                </div>

                <?php if (!$is_out_of_stock): ?>
                    <form method="POST" action="cart.php" class="flex items-end gap-3">
                        <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
                        <input type="hidden" name="add_to_cart" value="1">
                        <div class="w-32">
                            <label class="block text-sm text-gray-600 mb-1">Quantity</label>
                            <input type="number" name="quantity" min="1" max="<?php echo max(1, min($stock_quantity, 50)); ?>" value="1" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <button type="submit" class="px-5 py-2.5 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40]">
                            <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                        </button>
                    </form>
                <?php else: ?>
                    <button disabled class="px-5 py-2.5 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed">
                        Out of Stock
                    </button>
                <?php endif; ?>
            </section>

            <aside class="card p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Customer Reviews</h3>
                <div class="mb-4">
                    <p class="text-2xl font-bold text-amber-500"><?php echo number_format($avg_rating, 1); ?>/5</p>
                    <p class="text-sm text-gray-500"><?php echo $review_count; ?> review(s)</p>
                </div>

                <?php if (empty($reviews)): ?>
                    <p class="text-sm text-gray-500">No reviews yet.</p>
                <?php else: ?>
                    <div class="space-y-4 max-h-[560px] overflow-y-auto pr-1">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                                $images = [];
                                if (!empty($review['images'])) {
                                    $decoded = json_decode((string)$review['images'], true);
                                    if (is_array($decoded)) {
                                        $images = $decoded;
                                    }
                                }
                            ?>
                            <div class="border border-gray-100 rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-semibold text-sm text-gray-800"><?php echo htmlspecialchars(trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''))); ?></p>
                                    <span class="text-xs text-amber-600 font-bold"><?php echo (int)$review['rating']; ?>/5</span>
                                </div>
                                <?php if (!empty($review['title'])): ?>
                                    <p class="text-sm font-semibold text-gray-700"><?php echo htmlspecialchars($review['title']); ?></p>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                                <?php if (!empty($images)): ?>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <?php foreach ($images as $img): ?>
                                            <a href="../<?php echo htmlspecialchars($img); ?>" target="_blank">
                                                <img src="../<?php echo htmlspecialchars($img); ?>" class="w-14 h-14 rounded object-cover border border-gray-200" alt="Review image">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>
</body>
</html>
