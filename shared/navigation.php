<?php
// Determine the correct base path for relative links
// Count directory depth from root
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$depth = substr_count(trim($script_path, '/'), '/') - substr_count('/HARVEE', '/');
$base = str_repeat('../', max(0, $depth));
?>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="<?php echo $base; ?>index.php">
                <i class="fas fa-leaf"></i> Harvee
            </a>
        </div>
        
        <button class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <ul class="nav-menu" id="navMenu">
            <li><a href="<?php echo $base; ?>index.php"><i class="fas fa-home"></i> Home</a></li>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Logged in navigation -->
                <?php if($_SESSION['role'] === 'farmer'): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-tractor"></i> Farmer <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base; ?>dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo $base; ?>products/add.php">Add Product</a></li>
                            <li><a href="<?php echo $base; ?>products/products.php">Manage Products</a></li>
                            <li><a href="<?php echo $base; ?>orders.php">My Orders</a></li>
                            <li><a href="<?php echo $base; ?>profile.php">Profile</a></li>
                        </ul>
                    </li>
                <?php elseif($_SESSION['role'] === 'customer'): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-shopping-bag"></i> Shopping <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base; ?>browse.php">Browse Products</a></li>
                            <li><a href="<?php echo $base; ?>cart.php">Cart</a></li>
                            <li><a href="<?php echo $base; ?>orders.php">My Orders</a></li>
                        </ul>
                    </li>
                    <li><a href="<?php echo $base; ?>dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <?php elseif($_SESSION['role'] === 'driver'): ?>
                    <li><a href="<?php echo $base; ?>dashboard.php"><i class="fas fa-truck"></i> Driver Dashboard</a></li>
                <?php endif; ?>
                
                <li class="user-info">
                    <i class="fas fa-user-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </li>
                <li><a href="<?php echo $base; ?>auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                
            <?php else: ?>
                <!-- Guest navigationtytgfgfgffg -->
                <li><a href="<?php echo $base; ?>customer/browse.php"><i class="fas fa-search"></i> Browse Products</a></li>
                <li><a href="<?php echo $base; ?>../harvee/auth/login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="<?php echo $base; ?>harvee/auth/register.php" class="register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
