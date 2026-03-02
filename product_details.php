<?php
session_start();
require_once 'config/database.php';

// Get product ID
$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    http_response_code(404);
    echo '<div class="text-center p-8">
        <p class="text-gray-600">Product not found.</p>
    </div>';
    exit;
}

try {
    // Fetch product details with farmer info
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            c.name AS category_name,
            u.first_name,
            u.last_name,
            u.email
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.farmer_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo '<div class="text-center p-8">
            <p class="text-gray-600">Product not found.</p>
        </div>';
        exit;
    }
    
    // Check stock status
    $stock_quantity = (int)($product['stock_quantity'] ?? 0);
    $is_low_stock = $stock_quantity > 0 && $stock_quantity < 10;
    $is_out_of_stock = $stock_quantity <= 0;
    
?>
    <div class="space-y-6">
        <!-- Product Header -->
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Product Image & Info -->
            <div class="md:w-1/2">
                <!-- Image -->
                <div class="w-full h-80 bg-gradient-to-br from-green-50 to-gray-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                
                <!-- Product Badges -->
                <div class="flex gap-2 mb-4">
                    <?php if ($is_low_stock && !$is_out_of_stock): ?>
                        <span class="px-3 py-1 bg-red-100 text-red-700 text-sm font-semibold rounded-full">
                            Low Stock
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($is_out_of_stock): ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm font-semibold rounded-full">
                            Out of Stock
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['category_name'])): ?>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Details -->
            <div class="md:w-1/2">
                <!-- Product Name & Price -->
                <h2 class="text-3xl font-bold text-gray-800 mb-3">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h2>
                
                <div class="mb-6">
                    <div class="flex items-baseline gap-3">
                        <span class="text-4xl font-bold text-[#10854d]">₱<?php echo number_format($product['price'], 2); ?></span>
                        <span class="text-gray-500 text-lg">per unit</span>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-700 mb-2">Description</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?>
                    </p>
                </div>
                
                <!-- Stock Info -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-medium">Available Stock</span>
                        <span class="text-2xl font-bold <?php echo $is_out_of_stock ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo $stock_quantity; ?> units
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Farmer/Owner Information -->
        <div class="border-t pt-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">About the Seller</h3>
            
            <div class="bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-lg">
                <div class="flex items-start gap-4">
                    <!-- Farmer Avatar -->
                    <div class="w-16 h-16 bg-[#10854d] rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-2xl">
                            <?php echo strtoupper(substr($product['first_name'] ?? 'F', 0, 1)); ?>
                        </span>
                    </div>
                    
                    <!-- Farmer Details -->
                    <div class="flex-1">
                        <h4 class="text-lg font-bold text-gray-800">
                            <?php echo htmlspecialchars(($product['first_name'] ?? 'Farmer') . ' ' . ($product['last_name'] ?? 'Unknown')); ?>
                        </h4>
                        <p class="text-gray-600 text-sm mb-3">
                            <i class="fas fa-seedling text-green-600 mr-2"></i>
                            Fresh Produce Provider
                        </p>
                        
                        <div class="space-y-2">
                            <?php if ($product['email']): ?>
                                <div class="flex items-center text-gray-700 text-sm">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-4"></i>
                                    <a href="mailto:<?php echo htmlspecialchars($product['email']); ?>" 
                                       class="text-[#10854d] hover:underline">
                                        <?php echo htmlspecialchars($product['email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-gray-700 text-sm">
                                <i class="fas fa-star text-yellow-400 mr-3 w-4"></i>
                                <span>Trusted Farm Seller</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Action -->
                    <a href="browse.php" class="px-4 py-2 bg-white text-[#10854d] font-medium rounded-lg hover:bg-gray-100 border border-[#10854d] transition-colors">
                        More from this Seller
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Add to Cart Form -->
        <?php if (!$is_out_of_stock): ?>
            <div class="border-t pt-6">
                <form method="POST" action="/HARVEE/customer/cart.php" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-gray-700 font-medium mb-2">Quantity</label>
                        <input type="number" 
                               name="quantity" 
                               value="1" 
                               min="1" 
                               max="<?php echo min($stock_quantity, 50); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20">
                    </div>
                    
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    <input type="hidden" name="add_to_cart" value="1">
                    
                    <button type="submit" class="px-6 py-3 bg-[#10854d] text-white font-bold rounded-lg hover:bg-[#0d6e40] transition-colors flex items-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="border-t pt-6">
                <button disabled class="w-full px-6 py-3 bg-gray-300 text-gray-600 font-bold rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                    <i class="fas fa-ban"></i>
                    Out of Stock
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Product Meta Info -->
        <div class="border-t pt-6 grid grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-gray-500 text-sm">Product ID</p>
                <p class="font-medium text-gray-800">#<?php echo $product['id']; ?></p>
            </div>
            <div class="text-center">
                <p class="text-gray-500 text-sm">Added</p>
                <p class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
            </div>
            <div class="text-center">
                <p class="text-gray-500 text-sm">Last Updated</p>
                <p class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($product['updated_at'] ?? $product['created_at'])); ?></p>
            </div>
        </div>
    </div>
<?php
} catch (PDOException $e) {
    http_response_code(500);
    echo '<div class="text-center p-8">
        <p class="text-red-600">Error loading product details. Please try again.</p>
    </div>';
}
?>
