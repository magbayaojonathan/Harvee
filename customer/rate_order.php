<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? $_POST['order_id'] ?? 0);
$message = '';
$message_type = '';
$order = ['order_number' => ''];
$review_items = [];

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

try {
    $order_stmt = $pdo->prepare("SELECT id, order_number, order_status, created_at FROM orders WHERE id = ? AND customer_id = ?");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    if (($order['order_status'] ?? '') !== 'delivered') {
        $message = 'You can only review products after delivery.';
        $message_type = 'error';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && ($order['order_status'] ?? '') === 'delivered') {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        $item_stmt = $pdo->prepare("\n            SELECT oi.product_id, oi.farmer_id, p.name AS product_name\n            FROM order_items oi\n            JOIN products p ON p.id = oi.product_id\n            WHERE oi.order_id = ? AND oi.product_id = ?\n            LIMIT 1\n        ");
        $item_stmt->execute([$order_id, $product_id]);
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $message = 'Invalid product review request.';
            $message_type = 'error';
        } elseif ($rating < 1 || $rating > 5) {
            $message = 'Rating must be between 1 and 5.';
            $message_type = 'error';
        } elseif ($comment === '') {
            $message = 'Please add a comment for your review.';
            $message_type = 'error';
        } else {
            $existing_stmt = $pdo->prepare("SELECT id, images FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
            $existing_stmt->execute([$user_id, $product_id]);
            $existing = $existing_stmt->fetch(PDO::FETCH_ASSOC);

            $image_paths = [];
            if (!empty($existing['images'])) {
                $decoded = json_decode((string)$existing['images'], true);
                if (is_array($decoded)) {
                    $image_paths = $decoded;
                }
            }

            if (isset($_FILES['review_images']) && is_array($_FILES['review_images']['name'] ?? null)) {
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $uploaded = [];
                $max_files = 3;
                $max_size = 5 * 1024 * 1024;
                $file_count = count($_FILES['review_images']['name']);
                $effective_count = min($file_count, $max_files);
                $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reviews';

                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
                    throw new RuntimeException('Could not create review upload directory.');
                }

                for ($i = 0; $i < $effective_count; $i++) {
                    $error = $_FILES['review_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                    if ($error === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if ($error !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('One of the selected images failed to upload.');
                    }

                    $size = (int)($_FILES['review_images']['size'][$i] ?? 0);
                    if ($size > $max_size) {
                        throw new RuntimeException('Each image must be 5MB or smaller.');
                    }

                    $tmp_name = $_FILES['review_images']['tmp_name'][$i] ?? '';
                    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
                        throw new RuntimeException('Invalid review image upload.');
                    }

                    $mime = function_exists('mime_content_type') ? mime_content_type($tmp_name) : '';
                    if (($mime === '' || $mime === false) && function_exists('getimagesize')) {
                        $img_info = @getimagesize($tmp_name);
                        if (is_array($img_info) && !empty($img_info['mime'])) {
                            $mime = $img_info['mime'];
                        }
                    }

                    if (!isset($allowed[$mime])) {
                        $extension = strtolower(pathinfo((string)($_FILES['review_images']['name'][$i] ?? ''), PATHINFO_EXTENSION));
                        $mime_from_ext = array_search($extension, $allowed, true);
                        if ($mime_from_ext !== false) {
                            $mime = $mime_from_ext;
                        } else {
                            throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
                        }
                    }

                    $filename = 'review_' . $order_id . '_' . $product_id . '_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
                    $destination = $upload_dir . DIRECTORY_SEPARATOR . $filename;
                    if (!move_uploaded_file($tmp_name, $destination)) {
                        throw new RuntimeException('Failed to save uploaded review image.');
                    }

                    $uploaded[] = 'assets/uploads/reviews/' . $filename;
                }

                if (!empty($uploaded)) {
                    $image_paths = $uploaded;
                }
            }

            $images_json = !empty($image_paths) ? json_encode(array_values($image_paths)) : null;

            if ($existing) {
                $update_stmt = $pdo->prepare("\n                    UPDATE reviews\n                    SET order_id = ?, farmer_id = ?, rating = ?, title = ?, comment = ?, images = ?, is_verified_purchase = 1, is_approved = 1, updated_at = NOW()\n                    WHERE id = ?\n                ");
                $update_stmt->execute([
                    $order_id,
                    $item['farmer_id'],
                    $rating,
                    $title !== '' ? $title : null,
                    $comment,
                    $images_json,
                    (int)$existing['id']
                ]);

                $message = 'Your review has been updated successfully.';
                $message_type = 'success';
            } else {
                $insert_stmt = $pdo->prepare("\n                    INSERT INTO reviews (product_id, order_id, user_id, farmer_id, rating, title, comment, images, is_verified_purchase, is_approved, created_at, updated_at)\n                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())\n                ");
                $insert_stmt->execute([
                    $product_id,
                    $order_id,
                    $user_id,
                    $item['farmer_id'],
                    $rating,
                    $title !== '' ? $title : null,
                    $comment,
                    $images_json
                ]);

                $message = 'Review submitted successfully. Thank you!';
                $message_type = 'success';
            }
        }
    }

    $items_stmt = $pdo->prepare("\n        SELECT oi.product_id, oi.quantity, oi.unit_price, p.name AS product_name,\n               r.id AS review_id, r.rating, r.title, r.comment, r.images\n        FROM order_items oi\n        JOIN products p ON p.id = oi.product_id\n        LEFT JOIN reviews r ON r.product_id = oi.product_id AND r.user_id = ?\n        WHERE oi.order_id = ?\n        ORDER BY p.name ASC\n    ");
    $items_stmt->execute([$user_id, $order_id]);
    $review_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $message = 'Unable to load review page right now.';
    $message_type = 'error';
    error_log('rate_order error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Products - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .review-card { transition: all 0.25s ease; }
        .review-card:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.2); }
        .star-radio input { display: none; }
        .star-radio label { cursor: pointer; font-size: 1.4rem; color: #d1d5db; }
        .star-radio input:checked ~ label { color: #d1d5db; }
        .star-radio label.selected,
        .star-radio label.selected ~ label { color: #f59e0b; }
    </style>
</head>
<body>
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="orders.php" class="text-gray-700 hover:text-[#10854d]">&larr; Back to Orders</a>
                <h1 class="text-2xl font-bold text-[#10854d]">Rate Purchased Products</h1>
            </div>
            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($order['order_number'] ?? ('Order #' . $order_id)); ?></span>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <?php if ($message !== ''): ?>
            <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($review_items ?? [])): ?>
            <div class="bg-white rounded-xl p-10 text-center text-gray-500">No reviewable items found for this order.</div>
        <?php else: ?>
            <div class="space-y-5">
                <?php foreach ($review_items as $item): ?>
                    <?php
                        $existing_images = [];
                        if (!empty($item['images'])) {
                            $decoded = json_decode((string)$item['images'], true);
                            if (is_array($decoded)) {
                                $existing_images = $decoded;
                            }
                        }
                    ?>
                    <div class="review-card bg-white rounded-xl border border-gray-100 p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></h2>
                                <p class="text-sm text-gray-500">Qty: <?php echo (int)$item['quantity']; ?> | Price: PHP <?php echo number_format((float)$item['unit_price'], 2); ?></p>
                            </div>
                            <?php if (!empty($item['review_id'])): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Already Reviewed (Edit Allowed)</span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return validateRating(this)">
                            <input type="hidden" name="order_id" value="<?php echo (int)$order_id; ?>">
                            <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Rating</label>
                                <div class="star-radio inline-flex flex-row-reverse gap-1" data-group="rating-<?php echo (int)$item['product_id']; ?>">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <?php $checked = ((int)($item['rating'] ?? 0) === $i) ? 'checked' : ''; ?>
                                        <input type="radio" id="rating-<?php echo (int)$item['product_id']; ?>-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $checked; ?>>
                                        <label for="rating-<?php echo (int)$item['product_id']; ?>-<?php echo $i; ?>">&#9733;</label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Title (optional)</label>
                                <input type="text" name="title" maxlength="255" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Short headline for your review">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Comment</label>
                                <textarea name="comment" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Share your experience with this product" required><?php echo htmlspecialchars($item['comment'] ?? ''); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Upload picture (up to 3 images, JPG/PNG/WEBP)</label>
                                <input type="file" name="review_images[]" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-gray-700">
                                <?php if (!empty($existing_images)): ?>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <?php foreach ($existing_images as $img): ?>
                                            <a href="../<?php echo htmlspecialchars($img); ?>" target="_blank" class="block">
                                                <img src="../<?php echo htmlspecialchars($img); ?>" alt="Review image" class="w-16 h-16 rounded object-cover border border-gray-200">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="submit_review" class="px-5 py-2.5 bg-[#10854d] text-white rounded-lg font-medium hover:bg-[#0d6e40]">
                                    <?php echo !empty($item['review_id']) ? 'Update Review' : 'Submit Review'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function refreshStars(group) {
            const container = document.querySelector('[data-group="' + group + '"]');
            if (!container) return;
            const radios = container.querySelectorAll('input[type="radio"]');
            const labels = container.querySelectorAll('label');
            let selected = 0;
            radios.forEach(function(radio) {
                if (radio.checked) selected = parseInt(radio.value, 10);
            });
            labels.forEach(function(label, index) {
                label.classList.remove('selected');
                const value = 5 - index;
                if (value <= selected) label.classList.add('selected');
            });
        }

        document.querySelectorAll('.star-radio').forEach(function(container) {
            const group = container.getAttribute('data-group');
            refreshStars(group);
            container.querySelectorAll('input[type="radio"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    refreshStars(group);
                });
            });
        });

        function validateRating(form) {
            const checked = form.querySelector('input[name="rating"]:checked');
            if (!checked) {
                alert('Please select a rating before submitting.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
