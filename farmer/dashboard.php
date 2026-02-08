<?php
session_start();
// Check if user is logged in as farmer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../shared/header.php';
require_once '../shared/navigation.php';
?>

<main class="dashboard">
    <h1>Farmer Dashboard</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Add New Product</h3>
            <a href="products/add.php" class="btn">Add Product</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Manage Products</h3>
            <a href="products/manage.php" class="btn">View Products</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Orders</h3>
            <a href="orders.php" class="btn">View Orders</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Profile</h3>
            <a href="profile.php" class="btn">Edit Profile</a>
        </div>
    </div>
</main>

<?php require_once '../shared/footer.php'; ?>