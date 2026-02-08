<?php
session_start();
// Check if user is logged in as farmer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../shared/header.php';
require_once '../../shared/navigation.php';
?>

<main class="container">
    <h1>Add New Product</h1>
    
    <form action="process_add.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="price">Price per unit</label>
            <input type="number" id="price" name="price" step="0.01" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity Available</label>
            <input type="number" id="quantity" name="quantity" required>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="vegetables">Vegetables</option>
                <option value="fruits">Fruits</option>
                <option value="grains">Grains</option>
                <option value="dairy">Dairy</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>
        
        <button type="submit" class="btn">Add Product</button>
    </form>
</main>

<?php require_once '../../shared/footer.php'; ?>