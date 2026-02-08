<nav class="navbar">
    <div class="nav-brand">
        <a href="index.php">Harvee</a>
    </div>
    
    <ul class="nav-menu">
        <li><a href="index.php">Home</a></li>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Logged in navigation -->
            <?php if($_SESSION['role'] === 'farmer'): ?>
                <li><a href="farmer/dashboard.php">Dashboard</a></li>
                <li><a href="farmer/products/add.php">Add Product</a></li>
                <li><a href="farmer/orders.php">My Orders</a></li>
            <?php elseif($_SESSION['role'] === 'customer'): ?>
                <li><a href="customer/dashboard.php">Dashboard</a></li>
                <li><a href="customer/browse.php">Browse Products</a></li>
                <li><a href="customer/cart.php">Cart</a></li>
                <li><a href="customer/orders.php">My Orders</a></li>
            <?php endif; ?>
            
            <li><a href="profile.php">Profile</a></li>
            <li><a href="auth/logout.php">Logout</a></li>
            
        <?php else: ?>
            <!-- Guest navigation -->
            <li><a href="customer/browse.php">Browse Products</a></li>
            <li><a href="auth/login.php">Login</a></li>
            <li><a href="auth/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>