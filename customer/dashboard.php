<?php
session_start();
// Check if user is logged in as customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../shared/header.php';
require_once '../shared/navigation.php';
?>

<main class="dashboard">
    <h1>Customer Dashboard</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Browse Products</h3>
            <a href="browse.php" class="btn">Shop Now</a>
        </div>
        
        <div class="dashboard-card">
            <h3>My Cart</h3>
            <a href="cart.php" class="btn">View Cart</a>
        </div>
        
        <div class="dashboard-card">
            <h3>My Orders</h3>
            <a href="orders.php" class="btn">View Orders</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Profile</h3>
            <a href="profile.php" class="btn">Edit Profile</a>
        </div>
    </div>
</main>

<?php require_once '../shared/footer.php'; ?>