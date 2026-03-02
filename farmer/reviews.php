<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'Farmer';

$rating_filter = (int)($_GET['rating'] ?? 0);
if ($rating_filter < 1 || $rating_filter > 5) {
    $rating_filter = 0;
}

$reviews = [];
$stats = [
    'total_reviews' => 0,
    'avg_rating' => 0,
    'five_star' => 0,
    'with_images' => 0
];

try {
    $stats_stmt = $pdo->prepare("\n        SELECT\n            COUNT(*) AS total_reviews,\n            ROUND(AVG(r.rating), 2) AS avg_rating,\n            SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) AS five_star,\n            SUM(CASE WHEN r.images IS NOT NULL AND r.images <> '' AND r.images <> '[]' THEN 1 ELSE 0 END) AS with_images\n        FROM reviews r\n        JOIN products p ON r.product_id = p.id\n        WHERE p.farmer_id = ? AND r.is_approved = 1\n    ");
    $stats_stmt->execute([$user_id]);
    $stats_row = $stats_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stats['total_reviews'] = (int)($stats_row['total_reviews'] ?? 0);
    $stats['avg_rating'] = (float)($stats_row['avg_rating'] ?? 0);
    $stats['five_star'] = (int)($stats_row['five_star'] ?? 0);
    $stats['with_images'] = (int)($stats_row['with_images'] ?? 0);

    $sql = "\n        SELECT r.id, r.rating, r.title, r.comment, r.images, r.created_at,\n               p.id AS product_id, p.name AS product_name,\n               u.first_name, u.last_name\n        FROM reviews r\n        JOIN products p ON r.product_id = p.id\n        JOIN users u ON r.user_id = u.id\n        WHERE p.farmer_id = ? AND r.is_approved = 1\n    ";
    $params = [$user_id];

    if ($rating_filter > 0) {
        $sql .= " AND r.rating = ?";
        $params[] = $rating_filter;
    }

    $sql .= " ORDER BY r.created_at DESC";

    $review_stmt = $pdo->prepare($sql);
    $review_stmt->execute($params);
    $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('farmer reviews error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reviews - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f6f9f8 0%, #f0f7f3 100%); }
        .card { transition: all 0.25s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 14px 25px -15px rgba(16, 133, 77, 0.25); }
        .nav-link { transition: all 0.25s ease; }
        .nav-link.active { color: #10854d; border-bottom: 2px solid #10854d; }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="dashboard.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-[#10854d] rounded-lg flex items-center justify-center"><span class="text-white font-bold text-xl">H</span></div>
                    <span class="text-xl font-bold text-[#10854d]">Harvee Farm</span>
                </a>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="dashboard.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-home mr-1"></i> Dashboard</a>
                    <a href="products/products.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-box mr-1"></i> Products</a>
                    <a href="orders.php" class="nav-link text-gray-700 font-medium"><i class="fas fa-shopping-bag mr-1"></i> Orders</a>
                    <a href="reviews.php" class="nav-link active text-gray-700 font-medium"><i class="fas fa-star mr-1"></i> Reviews</a>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600 hidden md:inline">Hi, <?php echo htmlspecialchars($first_name); ?></span>
                    <a href="../auth/logout.php" class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-gradient-to-br from-[#10854d] to-[#0d6e40] rounded-2xl p-8 text-white mb-6">
            <h1 class="text-3xl font-bold mb-1">Customer Reviews</h1>
            <p class="text-white/90">See what customers are saying about your products.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="card bg-white rounded-xl p-4 border border-gray-100"><p class="text-sm text-gray-500">Total Reviews</p><p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_reviews']; ?></p></div>
            <div class="card bg-white rounded-xl p-4 border border-gray-100"><p class="text-sm text-gray-500">Average Rating</p><p class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats['avg_rating'], 2); ?></p></div>
            <div class="card bg-white rounded-xl p-4 border border-gray-100"><p class="text-sm text-gray-500">5-Star Reviews</p><p class="text-3xl font-bold text-green-600"><?php echo $stats['five_star']; ?></p></div>
            <div class="card bg-white rounded-xl p-4 border border-gray-100"><p class="text-sm text-gray-500">With Images</p><p class="text-3xl font-bold text-indigo-600"><?php echo $stats['with_images']; ?></p></div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-100 mb-6">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-600 mr-2">Filter by rating:</span>
                <a href="reviews.php" class="px-3 py-1.5 rounded-full text-sm <?php echo $rating_filter === 0 ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700'; ?>">All</a>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <a href="reviews.php?rating=<?php echo $i; ?>" class="px-3 py-1.5 rounded-full text-sm <?php echo $rating_filter === $i ? 'bg-[#10854d] text-white' : 'bg-gray-100 text-gray-700'; ?>"><?php echo $i; ?> Stars</a>
                <?php endfor; ?>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="bg-white rounded-xl p-10 text-center border border-gray-100 text-gray-500">
                No reviews yet for this filter.
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <?php
                        $review_images = [];
                        if (!empty($review['images'])) {
                            $decoded = json_decode((string)$review['images'], true);
                            if (is_array($decoded)) {
                                $review_images = $decoded;
                            }
                        }
                    ?>
                    <div class="card bg-white rounded-xl border border-gray-100 p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                            <div>
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($review['product_name']); ?></p>
                                <p class="text-sm text-gray-500">By <?php echo htmlspecialchars(trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''))); ?> | <?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                            </div>
                            <div class="flex items-center gap-1 text-amber-500">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fa<?php echo $s <= (int)$review['rating'] ? 's' : 'r'; ?> fa-star"></i>
                                <?php endfor; ?>
                                <span class="ml-2 text-sm font-semibold text-gray-700"><?php echo (int)$review['rating']; ?>/5</span>
                            </div>
                        </div>

                        <?php if (!empty($review['title'])): ?>
                            <p class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($review['title']); ?></p>
                        <?php endif; ?>
                        <p class="text-gray-700 mb-3"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>

                        <?php if (!empty($review_images)): ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($review_images as $img): ?>
                                    <a href="../<?php echo htmlspecialchars($img); ?>" target="_blank">
                                        <img src="../<?php echo htmlspecialchars($img); ?>" alt="Review image" class="w-20 h-20 rounded object-cover border border-gray-200">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
