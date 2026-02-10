<?php
session_start();
// Fix the path - go up two levels from farmer/products/
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';

// Check if user is logged in as farmer/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'farmer' && $_SESSION['role'] !== 'admin')) {
    // Fix redirect path - go up two levels then to auth
    header('Location: ' . dirname(dirname(dirname($_SERVER['PHP_SELF']))) . '/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Get form data with proper trimming
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    
    // Debug: Check what values are being received
    error_log("Form Data Received:");
    error_log("Product Name: " . $product_name);
    error_log("Description: " . $description);
    error_log("Category: " . $category);
    error_log("Price: " . $price);
    error_log("Stock: " . $stock);
    
    // Validate input
    $errors = [];
    
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
        $errors[] = "Valid price is required (must be greater than 0)";
    }
    
    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors[] = "Valid stock quantity is required (must be 0 or greater)";
    }
    
    // If no errors, insert product
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (farmer_id, product_name, description, category, price, stock) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $user_id,
                $product_name,
                $description,
                $category,
                (float)$price,
                (int)$stock
            ]);
            
            if ($success) {
                $message = "✓ Product added successfully!";
                // Clear form fields
                $_POST = [];
            } else {
                $errors[] = "Failed to add product. Please try again.";
            }
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            error_log("Database Error: " . $e->getMessage());
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
    // If error, use default categories
    $existing_categories = ['Vegetables', 'Fruits', 'Grains', 'Poultry', 'Dairy', 'Meat', 'Seafood'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Harvee Farm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .form-card {
            transition: all 0.3s ease;
        }
        .form-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .category-tag {
            transition: all 0.2s ease;
        }
        .category-tag:hover {
            transform: scale(1.05);
            background-color: #10854d !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header/Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="flex items-center space-x-2"> <!-- Changed path -->
                        <svg class="w-8 h-8 text-[#10854d]" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xl font-bold text-gray-800">Harvee Farm</span>
                    </a>
                    <div class="hidden md:block">
                        <span class="text-gray-500">/</span>
                        <span class="text-gray-700 font-medium ml-2">Add Product</span>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <a href="../products.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"> <!-- Changed path -->
                        <i class="fas fa-box mr-2"></i>View Products
                    </a>
                    
                    <div class="relative group">
                        <button class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100">
                            <div class="w-8 h-8 bg-[#10854d] rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-sm">
                                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium">
                                <?php echo htmlspecialchars($_SESSION['name']); ?>
                            </span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 hidden group-hover:block">
                            <a href="../dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100"> <!-- Changed path -->
                                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                            </a>
                            <a href="add.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-plus-circle mr-2"></i> Add Product
                            </a>
                            <a href="../products.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100"> <!-- Changed path -->
                                <i class="fas fa-boxes mr-2"></i> Manage Products
                            </a>
                            <a href="../orders.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100"> <!-- Changed path -->
                                <i class="fas fa-shopping-bag mr-2"></i> View Orders
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50"> <!-- Changed path -->
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Rest of your HTML remains the same, just update form action -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-[#10854d] mb-3">Add New Product</h1>
                <p class="text-gray-600">Add new products to your Harvee Farm store</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg border border-green-200 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo $message; ?>
                    <a href="products.php" class="ml-auto text-green-800 font-medium hover:underline"> <!-- Changed path -->
                        View Products →
                    </a>
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

            <!-- Product Form -->
                           <!-- Product Form -->
                <div class="bg-white rounded-xl shadow-sm p-6 form-card">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-6" id="productForm">
                        <!-- Product Name -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="product_name">
                                Product Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="product_name" 
                                   name="product_name" 
                                   value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                   placeholder="e.g., Organic Tomatoes, Fresh Cabbage"
                                   required>
                            <p class="text-sm text-gray-500 mt-1">Enter a descriptive name for your product</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="description">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                      placeholder="Describe your product (quality, freshness, features, etc.)"
                                      required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <p class="text-sm text-gray-500 mt-1">Detailed description helps customers understand your product better</p>
                        </div>

                                               <!-- Category -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2" for="category">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <div class="mb-3">
                                <select id="category" 
                                       name="category" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                       required>
                                    <option value="">Select a category</option>
                                    <option value="Vegetables" <?php echo (($_POST['category'] ?? '') === 'Vegetables') ? 'selected' : ''; ?>>Vegetables</option>
                                    <option value="Fruits" <?php echo (($_POST['category'] ?? '') === 'Fruits') ? 'selected' : ''; ?>>Fruits</option>
                                    <option value="Grains" <?php echo (($_POST['category'] ?? '') === 'Grains') ? 'selected' : ''; ?>>Grains</option>
                                    <option value="Root Crops" <?php echo (($_POST['category'] ?? '') === 'Root Crops') ? 'selected' : ''; ?>>Root Crops</option>
                                    <option value="Herbs" <?php echo (($_POST['category'] ?? '') === 'Herbs') ? 'selected' : ''; ?>>Herbs</option>
                                    <option value="Legumes" <?php echo (($_POST['category'] ?? '') === 'Legumes') ? 'selected' : ''; ?>>Legumes</option>
                                </select>
                            </div>
                            
                        
                        
                            
                            <!-- Common Categories -->
                            <div>
                                <p class="text-sm text-gray-600 mb-2">Common categories:</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" 
                                            onclick="document.getElementById('category').value = 'Vegetables'"
                                            class="category-tag px-3 py-1 bg-green-50 text-green-700 text-sm rounded-full border border-green-200">
                                        Vegetables
                                    </button>
                                    <button type="button" 
                                            onclick="document.getElementById('category').value = 'Fruits'"
                                            class="category-tag px-3 py-1 bg-yellow-50 text-yellow-700 text-sm rounded-full border border-yellow-200">
                                        Fruits
                                    </button>
                                    <button type="button" 
                                            onclick="document.getElementById('category').value = 'Grains'"
                                            class="category-tag px-3 py-1 bg-amber-50 text-amber-700 text-sm rounded-full border border-amber-200">
                                        Grains
                                    </button>
    
                                    <button type="button" 
                                            onclick="document.getElementById('category').value = 'Dairy'"
                                            class="category-tag px-3 py-1 bg-blue-50 text-blue-700 text-sm rounded-full border border-blue-200">
                                        Dairy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Price and Stock -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Price -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="price">
                                    Price (₱) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                    <input type="number" 
                                           id="price" 
                                           name="price" 
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                           step="0.01"
                                           min="0.01"
                                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                           placeholder="0.00"
                                           required>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Price per unit in Philippine Peso</p>
                            </div>

                            <!-- Stock -->
                            <div>
                                <label class="block text-gray-700 font-medium mb-2" for="stock">
                                    Stock Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       id="stock" 
                                       name="stock" 
                                       value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>"
                                       min="0"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20"
                                       placeholder="0"
                                       required>
                                <p class="text-sm text-gray-500 mt-1">Available quantity in stock</p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-6 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    All fields marked with <span class="text-red-500">*</span> are required
                                </div>
                                <div class="flex gap-3">
                                    <a href="../products.php" 
                                       class="px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                                        Cancel
                                    </a>
                                    <button type="submit" 
                                            name="submit"
                                            class="px-8 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i>
                                        Add Product
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

    
    <script>
           // Form validation before submission
    document.getElementById('productForm')?.addEventListener('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        // Check product name
        const productName = document.getElementById('product_name').value.trim();
        if (!productName) {
            errors.push("Product name is required");
            isValid = false;
        }
        
        // Check description
        const description = document.getElementById('description').value.trim();
        if (!description) {
            errors.push("Description is required");
            isValid = false;
        }
        
        // Check category
        const category = document.getElementById('category').value.trim();
        if (!category) {
            errors.push("Category is required");
            isValid = false;
        }
        
        // Check price
        const price = document.getElementById('price').value.trim();
        if (!price || isNaN(price) || parseFloat(price) <= 0) {
            errors.push("Valid price is required");
            isValid = false;
        }
        
        // Check stock
        const stock = document.getElementById('stock').value.trim();
        if (!stock || isNaN(stock) || parseInt(stock) < 0) {
            errors.push("Valid stock quantity is required");
            isValid = false;
        }
        
        // Show errors if any
        if (!isValid) {
            e.preventDefault(); // Prevent form submission
            
            // Create error message
            let errorHtml = '<div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg border border-red-200">';
            errorHtml += '<div class="flex items-center mb-2">';
            errorHtml += '<i class="fas fa-exclamation-circle mr-2"></i>';
            errorHtml += '<span class="font-bold">Please fix the following errors:</span>';
            errorHtml += '</div>';
            errorHtml += '<ul class="list-disc list-inside text-sm">';
            errors.forEach(error => {
                errorHtml += '<li>' + error + '</li>';
            });
            errorHtml += '</ul>';
            errorHtml += '</div>';
            
            // Remove any existing error messages
            const existingErrors = document.querySelector('.bg-red-100');
            if (existingErrors) {
                existingErrors.remove();
            }
            
            // Insert error message at top of form
            const formCard = document.querySelector('.form-card');
            if (formCard) {
                formCard.insertAdjacentHTML('afterbegin', errorHtml);
            }
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    
    // Auto-format price input
    document.getElementById('price')?.addEventListener('blur', function(e) {
        if (this.value && !isNaN(this.value)) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
    </script>
</body>
</html>