<?php
session_start();
require_once '../shared/header.php';
require_once '../shared/navigation.php';
?>

<main class="container">
    <h1>Browse Products</h1>
    
    <div class="search-filter">
        <input type="text" placeholder="Search products..." id="search">
        <select id="category-filter">
            <option value="">All Categories</option>
            <option value="vegetables">Vegetables</option>
            <option value="fruits">Fruits</option>
            <option value="grains">Grains</option>
            <option value="dairy">Dairy</option>
        </select>
    </div>
    
    <div class="products-grid" id="products-container">
        <!-- Products will be loaded here -->
    </div>
</main>

<script>
// JavaScript to load products dynamically
// This is a skeleton - you'll implement the actual product loading
</script>

<?php require_once '../shared/footer.php'; ?>