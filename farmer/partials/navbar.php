<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

$first_name_value = $first_name ?? ($_SESSION['first_name'] ?? ($_SESSION['name'] ?? 'Farmer'));
$last_name_value = $last_name ?? ($_SESSION['last_name'] ?? '');
$full_name_value = $full_name ?? trim((string)$first_name_value . ' ' . (string)$last_name_value);

require_once __DIR__ . '/../../shared/profile_photo.php';

$profile_image_value = (string)($_SESSION['profile_image'] ?? '');
if (($profile_image_value === '' || $profile_image_value === 'default-avatar.png') && isset($pdo, $_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $profile_image_value = (string)($row['profile_image'] ?? '');
        if ($profile_image_value !== '' && $profile_image_value !== 'default-avatar.png') {
            $_SESSION['profile_image'] = $profile_image_value;
        }
    } catch (PDOException $e) {
        // Ignore navbar photo failures
    }
}
$profile_photo_url = harvee_profile_photo_url($profile_image_value, '../');
?>
<nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="dashboard.php" class="flex items-center space-x-2">
                <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-8 h-8 object-contain">
                <span class="text-xl font-bold gradient-text">Harvee Farm</span>
            </a>

            <!-- Main Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="dashboard.php" class="nav-link text-gray-700 font-medium <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="products/products.php" class="nav-link text-gray-700 font-medium <?php echo $current_page === 'products.php' ? 'active' : ''; ?> hover:text-[#10854d]">
                    <i class="fas fa-box mr-1"></i> Products
                </a>
                <a href="orders.php" class="nav-link text-gray-700 font-medium <?php echo $current_page === 'orders.php' ? 'active' : ''; ?> hover:text-[#10854d]">
                    <i class="fas fa-shopping-bag mr-1"></i> Orders
                </a>
                <a href="reviews.php" class="nav-link text-gray-700 font-medium <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?> hover:text-[#10854d]">
                    <i class="fas fa-star mr-1"></i> Reviews
                </a>
                <a href="customers.php" class="nav-link text-gray-700 font-medium <?php echo $current_page === 'customers.php' ? 'active' : ''; ?> hover:text-[#10854d]">
                    <i class="fas fa-users mr-1"></i> Customers
                </a>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-3">
                <a href="products/add.php" class="hidden md:inline-flex items-center px-4 py-2 bg-[#10854d] text-white text-sm font-medium rounded-lg hover:bg-[#0d6e40] transition-all shadow-md hover:shadow-lg">
                    <i class="fas fa-plus mr-2"></i> Add Product
                </a>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 p-1.5 hover:bg-gray-100 rounded-full transition-all">
                        <div class="w-9 h-9 bg-[#10854d] rounded-full flex items-center justify-center shadow-md overflow-hidden">
                            <?php if ($profile_photo_url !== ''): ?>
                                <img src="<?php echo htmlspecialchars($profile_photo_url); ?>" alt="Profile photo" class="w-9 h-9 object-cover">
                            <?php else: ?>
                                <span class="text-white font-bold text-sm">
                                    <?php echo strtoupper(substr((string)$first_name_value, 0, 1)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="hidden md:inline text-gray-700 font-medium">
                            <?php echo htmlspecialchars((string)$first_name_value); ?>
                        </span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>

                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-100" style="display: none;">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars((string)$full_name_value); ?></p>
                            <p class="text-xs text-gray-500">Farmer</p>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#10854d]">
                            <i class="fas fa-user mr-2"></i> My Profile
                        </a>
                        <a href="settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#10854d]">
                            <i class="fas fa-cog mr-2"></i> Settings
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<?php if (!defined('HARVEE_FARMER_ALPINE_INCLUDED')): ?>
    <?php define('HARVEE_FARMER_ALPINE_INCLUDED', true); ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php endif; ?>
