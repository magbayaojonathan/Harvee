<?php
session_start();
require_once 'config/database.php';

// Fetch featured products
$featured_products = [];
$categories = [];
$stats = [];
$testimonials = [];

try {
    // Get featured products
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock_quantity,
               u.first_name as farmer_name, c.name as category_name
        FROM products p 
        JOIN users u ON p.farmer_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND p.stock_quantity > 0
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
    
    // Get categories for navigation
    $cat_stmt = $pdo->prepare("SELECT id, name, icon FROM categories WHERE is_active = 1 LIMIT 6");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();
    
    // Get stats
    $stats['products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    $stats['farmers'] = $pdo->query("SELECT COUNT(DISTINCT farmer_id) FROM products WHERE is_active = 1")->fetchColumn();
    $stats['customers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

    // Get real approved reviews for homepage testimonials
    $testimonial_stmt = $pdo->prepare("
        SELECT r.rating, r.title, r.comment, r.images, r.created_at,
               u.first_name, u.last_name, u.role,
               p.name AS product_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN products p ON r.product_id = p.id
        WHERE r.is_approved = 1
          AND (TRIM(COALESCE(r.comment, '')) <> '' OR TRIM(COALESCE(r.title, '')) <> '')
        ORDER BY r.created_at DESC
        LIMIT 3
    ");
    $testimonial_stmt->execute();
    $testimonials = $testimonial_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Homepage error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvee - Fresh from Farm to Table</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 50%, #059669 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,170.7C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(16, 133, 77, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(16, 133, 77, 0.2), 0 10px 10px -5px rgba(16, 133, 77, 0.1);
            border-color: rgba(16, 133, 77, 0.3);
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .category-item {
            transition: all 0.3s ease;
        }
        .category-item:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #10854d, #059669);
            color: white;
        }
        .stats-item {
            position: relative;
            overflow: hidden;
        }
        .stats-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10854d, #4ade80);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .stats-item:hover::after {
            transform: scaleX(1);
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .gradient-text {
            background: linear-gradient(135deg, #10854d, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .nav-link {
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #10854d;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10854d, #059669);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(16, 133, 77, 0.4);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white/90 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center space-x-2">
                    <img src="assets/images/logo.png" alt="Harvee Logo" 
                         class="h-14 w-auto"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-14 h-14 bg-gradient-to-r from-[#10854d] to-[#059669] rounded-lg flex items-center justify-center" style="display: none;">
                        <i class="fas fa-leaf text-white text-2xl"></i>
                    </div>
                    <span class="text-2xl md:text-3xl font-black gradient-text tracking-widest drop-shadow-lg">Harvee</span>
                </a>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="nav-link text-gray-700 font-medium border-b-2 border-[#10854d]">Home</a>
                    <a href="customer/browse.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">Products</a>
                    <a href="#features" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">Features</a>
                    <a href="#how-it-works" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">How It Works</a>
                    <a href="about.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">About</a>
                    <a href="contact.php" class="nav-link text-gray-700 font-medium hover:text-[#10854d]">Contact</a>
                </div>

                <!-- Auth Buttons -->
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] === 'customer'): ?>
                            <a href="customer/cart.php" class="relative p-2 hover:bg-gray-100 rounded-full transition-colors">
                                <i class="fas fa-shopping-cart text-gray-700 text-xl"></i>
                            </a>
                            <a href="customer/dashboard.php" class="hidden md:inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                        <?php elseif($_SESSION['role'] === 'farmer'): ?>
                            <a href="farmer/dashboard.php" class="hidden md:inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-tachometer-alt mr-2"></i> Farmer Dashboard
                            </a>
                        <?php elseif($_SESSION['role'] === 'driver'): ?>
                            <a href="driver/dashboard.php" class="hidden md:inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-truck mr-2"></i> Driver Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="auth/login.php" class="px-4 py-2 text-gray-700 hover:text-[#10854d] transition-colors">Login</a>
                        <a href="auth/register.php" class="px-4 py-2 bg-[#10854d] text-white rounded-lg hover:bg-[#0d6e40] transition-colors btn-primary">
                            Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient text-white relative overflow-hidden">
        <div class="container mx-auto px-4 py-20 md:py-28 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="text-center md:text-left">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                        Click, <span class="text-yellow-300">Farm</span><br>and Enjoy!
                    </h1>
                    <p class="text-xl md:text-2xl mb-8 text-green-50 max-w-2xl mx-auto md:mx-0">
                        Connect directly with local farmers and get the freshest produce delivered to your doorstep.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="customer/browse.php" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all text-center btn-primary">
                                <i class="fas fa-store mr-2"></i>Browse Products
                            </a>
                        <?php else: ?>
                            <a href="auth/register.php?role=customer" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all text-center">
                                <i class="fas fa-user-plus mr-2"></i>Shop as Customer
                            </a>
                            <a href="auth/register.php?role=farmer" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-semibold hover:bg-white hover:text-[#10854d] transition-all text-center">
                                <i class="fas fa-tractor mr-2"></i>Sell as Farmer
                            </a>
                            <a href="auth/register.php?role=driver" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-semibold hover:bg-white hover:text-[#10854d] transition-all text-center">
                                <i class="fas fa-truck mr-2"></i>Drive for Harvee
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mt-12">
                        <div class="stats-item text-center">
                            <div class="text-3xl font-bold"><?php echo number_format($stats['products'] ?? 150); ?>+</div>
                            <div class="text-sm text-green-200">Fresh Products</div>
                        </div>
                        <div class="stats-item text-center">
                            <div class="text-3xl font-bold"><?php echo number_format($stats['farmers'] ?? 50); ?>+</div>
                            <div class="text-sm text-green-200">Local Farmers</div>
                        </div>
                        <div class="stats-item text-center">
                            <div class="text-3xl font-bold"><?php echo number_format($stats['customers'] ?? 1000); ?>+</div>
                            <div class="text-sm text-green-200">Happy Customers</div>
                        </div>
                    </div>
                </div>
                
                <div class="hidden md:block relative">
                    <img src="assets/images/farm-hero.png" alt="Fresh Farm Produce" class="rounded-2xl shadow-2xl floating" 
                         onerror="this.src='https://images.unsplash.com/photo-1464226184884-fa280b87c399?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                    
                    <!-- Badge inside image at bottom right -->
                    <div class="absolute bottom-6 right-6 bg-white/95 backdrop-blur-sm rounded-xl shadow-xl p-3 flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-leaf text-xl text-[#10854d]"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">100% Organic</p>
                            <p class="text-xs text-gray-500">Farm Fresh</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <?php if(!empty($categories)): ?>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-4">Shop by Category</h2>
            <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Browse our wide selection of fresh produce categories</p>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach($categories as $category): ?>
                <a href="customer/browse.php?category=<?php echo urlencode($category['name']); ?>" 
                   class="category-item bg-gray-50 rounded-xl p-6 text-center hover:shadow-lg transition-all group">
                    <div class="text-4xl mb-3"><?php echo htmlspecialchars($category['icon'] ?? '🥬'); ?></div>
                    <h3 class="font-semibold text-gray-800 group-hover:text-white transition-colors"><?php echo htmlspecialchars($category['name']); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Why Choose Harvee?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Experience the best way to buy fresh produce directly from local farmers</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="feature-card bg-white rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-leaf text-3xl text-[#10854d]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">100% Fresh</h3>
                    <p class="text-gray-600">Harvested at peak ripeness and delivered straight to you</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-hand-holding-heart text-3xl text-[#10854d]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Support Local</h3>
                    <p class="text-gray-600">Help local farmers thrive and strengthen your community</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-tags text-3xl text-[#10854d]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Best Prices</h3>
                    <p class="text-gray-600">No middlemen means better prices for everyone</p>
                </div>
                
                <div class="feature-card bg-white rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-truck text-3xl text-[#10854d]"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Fast Delivery</h3>
                    <p class="text-gray-600">Quick delivery while maintaining product freshness</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <?php if(!empty($featured_products)): ?>
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Featured Products</h2>
                    <p class="text-gray-600">
                        Click, <span class="text-yellow-500 font-semibold">Farm</span>, Enjoy!
                    </p>
                </div>
                <a href="customer/browse.php" class="text-[#10854d] hover:underline font-medium hidden md:block">
                    View All Products <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($featured_products as $product): ?>
                <div class="product-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="relative h-48 bg-gradient-to-br from-green-50 to-gray-100">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-seedling text-5xl text-gray-300"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($product['stock_quantity'] < 10): ?>
                            <span class="absolute top-3 right-3 px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                                Low Stock
                            </span>
                        <?php endif; ?>
                        
                        <span class="absolute bottom-3 left-3 px-3 py-1 bg-[#10854d] text-white text-sm font-bold rounded-full">
                            ₱<?php echo number_format($product['price'], 2); ?>
                        </span>
                    </div>
                    
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 text-lg mb-2 truncate">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            <?php echo htmlspecialchars(substr($product['description'] ?? 'Fresh farm product', 0, 60)); ?>...
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($product['farmer_name'] ?? 'Local Farmer'); ?>
                            </div>
                            
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" 
                               class="px-4 py-2 bg-[#10854d] text-white text-sm rounded-lg hover:bg-[#0d6e40] transition-colors">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Mobile View All Link -->
            <div class="text-center mt-8 md:hidden">
                <a href="customer/browse.php" class="inline-flex items-center text-[#10854d] font-medium">
                    View All Products <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">How It Works</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Get started with Harvee in four simple steps</p>
            </div>
            
            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-[#10854d] rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                        1
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Create Account</h3>
                    <p class="text-gray-600">Sign up as a customer or farmer in minutes</p>
                    
                    <!-- Connector Line (Desktop only) -->
                    <div class="hidden lg:block absolute top-8 left-[60%] w-full h-0.5 bg-gradient-to-r from-[#10854d] to-transparent"></div>
                </div>
                
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-[#10854d] rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                        2
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Browse & Add</h3>
                    <p class="text-gray-600">Explore fresh products and add to your cart</p>
                    
                    <!-- Connector Line (Desktop only) -->
                    <div class="hidden lg:block absolute top-8 left-[60%] w-full h-0.5 bg-gradient-to-r from-[#10854d] to-transparent"></div>
                </div>
                
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-[#10854d] rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                        3
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Place Order</h3>
                    <p class="text-gray-600">Securely checkout with multiple payment options</p>
                    
                    <!-- Connector Line (Desktop only) -->
                    <div class="hidden lg:block absolute top-8 left-[60%] w-full h-0.5 bg-gradient-to-r from-[#10854d] to-transparent"></div>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-[#10854d] rounded-2xl flex items-center justify-center mx-auto mb-6 text-white text-2xl font-bold">
                        4
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Enjoy Fresh</h3>
                    <p class="text-gray-600">Get fresh produce delivered to your doorstep</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">What Our Users Say</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Join thousands of satisfied customers and farmers</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <?php if (!empty($testimonials)): ?>
                    <?php foreach ($testimonials as $review): ?>
                        <?php
                            $full_name = trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''));
                            $first_name = trim((string)($review['first_name'] ?? ''));
                            $initial = $first_name !== '' ? strtoupper(substr($first_name, 0, 1)) : 'U';
                            $role_label = ucfirst((string)($review['role'] ?? 'user'));
                            $rating = max(1, min(5, (int)($review['rating'] ?? 5)));
                            $raw_text = trim((string)($review['comment'] ?? ''));
                            if ($raw_text === '') {
                                $raw_text = trim((string)($review['title'] ?? ''));
                            }
                            $display_text = mb_strlen($raw_text) > 170 ? mb_substr($raw_text, 0, 167) . '...' : $raw_text;
                            $product_name = trim((string)($review['product_name'] ?? ''));
                            $images_json = $review['images'] ?? '';
                            $review_images = [];
                            if (is_string($images_json) && $images_json !== '') {
                                $decoded_images = json_decode($images_json, true);
                                if (is_array($decoded_images)) {
                                    $review_images = $decoded_images;
                                }
                            }
                            $first_review_image = '';
                            if (!empty($review_images) && is_string($review_images[0])) {
                                $first_review_image = trim($review_images[0]);
                            }
                        ?>
                        <div class="bg-gray-50 rounded-2xl p-8">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-[#10854d] rounded-full flex items-center justify-center text-white font-bold text-xl">
                                    <?php echo htmlspecialchars($initial); ?>
                                </div>
                                <div class="ml-4">
                                    <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($full_name !== '' ? $full_name : 'Anonymous User'); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($role_label); ?></p>
                                </div>
                            </div>
                            <p class="text-gray-600 italic">"<?php echo htmlspecialchars($display_text !== '' ? $display_text : 'Great experience using Harvee.'); ?>"</p>
                            <?php if ($product_name !== ''): ?>
                                <p class="text-xs text-gray-500 mt-2">Reviewed product: <?php echo htmlspecialchars($product_name); ?></p>
                            <?php endif; ?>
                            <?php if ($first_review_image !== ''): ?>
                                <div class="mt-3">
                                    <img src="<?php echo htmlspecialchars($first_review_image); ?>" alt="Review photo" class="w-full h-28 object-cover rounded-lg border border-gray-200">
                                </div>
                            <?php endif; ?>
                            <div class="flex mt-4 text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="md:col-span-3 bg-gray-50 rounded-2xl p-8 text-center">
                        <p class="text-gray-600">No approved reviews yet. Customer feedback will appear here once reviews are submitted and approved.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-[#10854d] to-[#059669] text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Start Your Fresh Journey?</h2>
            <p class="text-xl mb-10 text-green-50 max-w-2xl mx-auto">Join thousands of customers and farmers already using Harvee</p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'farmer'): ?>
                        <a href="farmer/dashboard.php" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all">
                            <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                        </a>
                    <?php elseif($_SESSION['role'] === 'driver'): ?>
                        <a href="driver/dashboard.php" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all">
                            <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="customer/dashboard.php" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all">
                            <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="auth/register.php?role=customer" class="px-8 py-4 bg-white text-[#10854d] rounded-xl font-semibold hover:shadow-xl transition-all">
                        <i class="fas fa-user-plus mr-2"></i> Sign Up as Customer
                    </a>
                    <a href="auth/register.php?role=farmer" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-semibold hover:bg-white hover:text-[#10854d] transition-all">
                        <i class="fas fa-tractor mr-2"></i> Become a Farmer
                    </a>
                    <a href="auth/register.php?role=driver" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-semibold hover:bg-white hover:text-[#10854d] transition-all">
                        <i class="fas fa-truck mr-2"></i> Join as Driver
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <div>
                    <div class="flex items-center space-x-2 mb-6">
                        <div class="w-8 h-8 bg-gradient-to-r from-[#10854d] to-[#059669] rounded-lg flex items-center justify-center">
                            <i class="fas fa-leaf text-white"></i>
                        </div>
                        <span class="text-xl font-bold">Harvee</span>
                    </div>
                    <p class="text-gray-400 mb-4">Connecting farmers directly to customers for fresh, quality produce.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-[#10854d] transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Quick Links</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="about.php" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="customer/browse.php" class="hover:text-white transition-colors">Products</a></li>
                        <li><a href="farmer/register.php" class="hover:text-white transition-colors">Become a Farmer</a></li>
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Support</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="faq.php" class="hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="shipping.php" class="hover:text-white transition-colors">Shipping Policy</a></li>
                        <li><a href="returns.php" class="hover:text-white transition-colors">Returns</a></li>
                        <li><a href="privacy.php" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-6">Contact Info</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3"></i>
                            123 Market Street, Farming District, Philippines
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3"></i>
                            +63 (123) 456-7890
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3"></i>
                            support@harvee.com
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center">
                <p class="text-gray-400">© <?php echo date('Y'); ?> Harvee. All rights reserved. Fresh from farm to your table.</p>
            </div>
        </div>
    </footer>

    <!-- Floating Action Button for Mobile -->
    <div class="fixed bottom-6 right-6 md:hidden">
        <a href="customer/browse.php" class="w-14 h-14 bg-[#10854d] rounded-full flex items-center justify-center text-white shadow-lg hover:bg-[#0d6e40] transition-colors">
            <i class="fas fa-shopping-bag text-xl"></i>
        </a>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-app.js";
        import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-analytics.js";

        const firebaseConfig = {
            apiKey: "AIzaSyC2mvMziVuP3i6UKXgD2Z8ewKMK5MpBkPY",
            authDomain: "harvee-1039e.firebaseapp.com",
            projectId: "harvee-1039e",
            storageBucket: "harvee-1039e.firebasestorage.app",
            messagingSenderId: "146835506130",
            appId: "1:146835506130:web:d974ad5209dac07db5be0b",
            measurementId: "G-DNVWZZEREE"
        };

        const app = initializeApp(firebaseConfig);
        getAnalytics(app);
    </script>
</body>
</html>
