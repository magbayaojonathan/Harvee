<?php
// Determine depth based on REQUEST_URI
$uri = $_SERVER['REQUEST_URI'];
// Count slashes after /HARVEE/
$harvee_pos = strpos($uri, '/HARVEE/');
if ($harvee_pos !== false) {
    $after_harvee = substr($uri, $harvee_pos + 8); // 8 = len('/HARVEE/')
    $slash_count = substr_count($after_harvee, '/');
    // slash_count tells us how many directories deep we are
    $base = str_repeat('../', $slash_count);
} else {
    $base = '';
}
?>

<nav class="navbar">
    <div class="nav-brand">
        <a href="<?php echo $base; ?>index.php">Harvee</a>
    </div>
    
    <ul class="nav-menu">
        <li><a href="<?php echo $base; ?>index.php">Home</a></li>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Logged in navigation -->
            <?php if($_SESSION['role'] === 'farmer'): ?>
                <li><a href="<?php echo $base; ?>farmer/dashboard.php">Dashboard</a></li>
                <li><a href="<?php echo $base; ?>farmer/products/add.php">Add Product</a></li>
                <li><a href="<?php echo $base; ?>farmer/products/manage.php">Manage Products</a></li>
                <li><a href="<?php echo $base; ?>farmer/orders.php">My Orders</a></li>
                <li><a href="<?php echo $base; ?>farmer/profile.php">Profile</a></li>
            <?php elseif($_SESSION['role'] === 'customer'): ?>
                <li><a href="<?php echo $base; ?>customer/dashboard.php">Dashboard</a></li>
                <li><a href="<?php echo $base; ?>customer/browse.php">Browse Products</a></li>
                <li><a href="<?php echo $base; ?>customer/cart.php">Cart</a></li>
                <li><a href="<?php echo $base; ?>customer/orders.php">My Orders</a></li>
                <li><a href="<?php echo $base; ?>customer/profile.php">Profile</a></li>
            <?php endif; ?>
            
            <li><a href="<?php echo $base; ?>auth/logout.php">Logout</a></li>
            
        <?php else: ?>
            <!-- Guest navigation -->
            <li><a href="<?php echo $base; ?>customer/browse.php">Browse Products</a></li>
            <li><a href="<?php echo $base; ?>auth/login.php">Login</a></li>
            <li><a href="<?php echo $base; ?>auth/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>