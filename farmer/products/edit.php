<?php
session_start();
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';

// Check if user is logged in as farmer/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'farmer' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? 0;
$message = '';
$errors = [];

// Get product details
$product = null;
if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit();
    }
} else {
    header('Location: products.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    
    // Validate input
    if (empty($product_name)) {
        $errors[] = "Product name is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = "Valid price is required";
    }
    
    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors[] = "Valid stock quantity is required";
    }
    
    // If no errors, update product
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, description = ?, category = ?, price = ?, stock = ?
                WHERE id = ? AND farmer_id = ?
            ");
            
            $success = $stmt->execute([
                $product_name,
                $description,
                $category,
                $price,
                $stock,
                $product_id,
                $user_id
            ]);
            
            if ($success) {
                $message = "✓ Product updated successfully!";
                // Refresh product data
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND farmer_id = ?");
                $stmt->execute([$product_id, $user_id]);
                $product = $stmt->fetch();
            } else {
                $errors[] = "Failed to update product. Please try again.";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get existing categories for suggestions
$existing_categories = [];
try {
    $cat_stmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $cat_stmt->execute();
    $existing_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_categories = ['Vegetables', 'Fruits', 'Grains', 'Root Crops', 'Herbs', 'Legumes'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <svg class="w-8 h-8 text-[#10854d]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xl font-bold text-gray-800">Harvee Farm</span>
                    </a>
                    <div class="hidden md:block">
                        <span class="text-gray-500">/</span>
                        <span class="text-gray-700 font-medium ml-2">Edit Product</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="products.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[#10854d] mb-2">Edit Product</h1>
                <p class="text-gray-600">Update product information for: <span class="font-medium"><?php echo htmlspecialchars($product['product_name']); ?></span></p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg border border-red-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span class="font-bold">Please fix the following errors:</span>
                    </div>
                    <ul class="list-disc list-inside text-sm">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <form method="POST" action="" class="space-y-6">
                    <!-- Product Name -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Product Name</label>
                        <input type="text" 
                               name="product_name" 
                               value="<?php echo htmlspecialchars($product['product_name']); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Description</label>
                        <textarea name="description" 
                                  rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                  required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            <option value="">Select a category</option>
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($product['category'] == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price and Stock -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Price (₱)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" 
                                       name="price" 
                                       value="<?php echo htmlspecialchars($product['price']); ?>"
                                       step="0.01"
                                       min="0.01"
                                       class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                       required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Stock Quantity</label>
                            <input type="number" 
                                   name="stock" 
                                   value="<?php echo htmlspecialchars($product['stock']); ?>"
                                   min="0"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                   required>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="pt-6 border-t border-gray-200">
                        <div class="flex justify-between">
                            <a href="products.php" 
                               class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300">
                                Cancel
                            </a>
                            <div class="flex gap-3">
                                <button type="submit" 
                                        class="px-8 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40]">
                                    Update Product
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Product Info -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-blue-800 mb-3">Product Information</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-blue-600">Product ID</p>
                        <p class="font-medium">#<?php echo $product['id']; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600">Created On</p>
                        <p class="font-medium"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600">Last Updated</p>
                        <p class="font-medium"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600">Status</p>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php echo $product['stock'] == 0 ? 'bg-red-100 text-red-800' : 
                                   ($product['stock'] < 10 ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-green-100 text-green-800'); ?>">
                            <?php echo $product['stock'] == 0 ? 'Out of Stock' : 
                                   ($product['stock'] < 10 ? 'Low Stock' : 'In Stock'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>