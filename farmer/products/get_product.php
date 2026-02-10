<?php
session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';

// Check if user is logged in as farmer/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'farmer' && $_SESSION['role'] !== 'admin')) {
    exit('Access denied');
}

$product_id = $_GET['id'] ?? 0;

if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stock_status = '';
        $stock_color = '';
        
        if ($product['stock'] == 0) {
            $stock_status = 'Out of Stock';
            $stock_color = 'text-red-600 bg-red-100';
        } elseif ($product['stock'] < 10) {
            $stock_status = 'Low Stock';
            $stock_color = 'text-yellow-600 bg-yellow-100';
        } else {
            $stock_status = 'In Stock';
            $stock_color = 'text-green-600 bg-green-100';
        }
        ?>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                    <i class="fas fa-seedling text-gray-400 text-2xl"></i>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                    <p class="text-gray-500 text-sm">Added on <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Category</p>
                    <p class="font-medium"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Price</p>
                    <p class="font-bold text-[#10854d]">₱<?php echo number_format($product['price'], 2); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Stock</p>
                    <p class="font-medium"><?php echo $product['stock']; ?> units</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="px-2 py-1 text-xs rounded-full <?php echo $stock_color; ?>">
                        <?php echo $stock_status; ?>
                    </span>
                </div>
            </div>
            
            <div>
                <p class="text-sm text-gray-500 mb-2">Description</p>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available')); ?></p>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
                <div class="flex justify-end space-x-3">
                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                       class="px-4 py-2 bg-[#10854d] text-white text-sm font-medium rounded-lg hover:bg-[#0d6e40]">
                        Edit Product
                    </a>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<p class="text-red-600">Product not found or access denied.</p>';
    }
} else {
    echo '<p class="text-red-600">Invalid product ID.</p>';
}
?>