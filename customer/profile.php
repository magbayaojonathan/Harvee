<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors[] = "Name is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check if email already exists (excluding current user)
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists";
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, address = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $address, $user_id]);
                
                // Update session name
                $_SESSION['name'] = $name;
                
                $message = "Profile updated successfully!";
                $message_type = "success";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
            } catch (PDOException $e) {
                $message = "Error updating profile: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = "error";
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $message = "Password changed successfully!";
                $message_type = "success";
                
            } catch (PDOException $e) {
                $message = "Error changing password: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $message_type = "error";
        }
    }
}

// Get user statistics
$stats = [];

// Total orders
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
$stmt->execute([$user_id]);
$stats['total_orders'] = $stmt->fetch()['total'];

// Total spent
$stmt = $pdo->prepare("SELECT SUM(total) as total FROM orders WHERE customer_id = ? AND order_status != 'cancelled'");
$stmt->execute([$user_id]);
$stats['total_spent'] = $stmt->fetch()['total'] ?? 0;

// Pending orders
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND order_status = 'pending'");
$stmt->execute([$user_id]);
$stats['pending_orders'] = $stmt->fetch()['total'];

// Completed orders
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ? AND order_status = 'delivered'");
$stmt->execute([$user_id]);
$stats['completed_orders'] = $stmt->fetch()['total'];

// Member since
$member_since = date('F Y', strtotime($user['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Harvee</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .profile-sidebar {
            background: linear-gradient(135deg, #10854d 0%, #0d6e40 100%);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="text-gray-700">Back to Dashboard</span>
                    </a>
                    <h1 class="text-2xl font-bold text-[#10854d]">My Profile</h1>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <div class="flex items-center">
                    <?php if ($message_type === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                    <div><?php echo $message; ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Profile Sidebar -->
            <div class="lg:w-1/3">
                <div class="profile-sidebar rounded-xl shadow-lg overflow-hidden sticky top-20">
                    <!-- Profile Header -->
                    <div class="p-6 text-center text-white">
                        <div class="w-24 h-24 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                            <span class="text-3xl font-bold text-[#10854d]">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </span>
                        </div>
                        <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-green-100 text-sm mb-4">Member since <?php echo $member_since; ?></p>
                        
                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-3 pt-4 border-t border-green-600">
                            <div>
                                <div class="text-2xl font-bold"><?php echo $stats['total_orders']; ?></div>
                                <div class="text-xs text-green-100">Orders</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold">₱<?php echo number_format($stats['total_spent'], 0); ?></div>
                                <div class="text-xs text-green-100">Spent</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold"><?php echo $stats['pending_orders']; ?></div>
                                <div class="text-xs text-green-100">Pending</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="bg-white p-4">
                        <h3 class="font-semibold text-gray-700 mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="orders.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span class="text-gray-700">My Orders</span>
                            </a>
                            <a href="cart.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="text-gray-700">Shopping Cart</span>
                            </a>
                            <a href="browse.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-[#10854d] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span class="text-gray-700">Browse Products</span>
                            </a>
                            <a href="../auth/logout.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-red-600">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="lg:w-2/3 space-y-6">
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Edit Profile Information</h2>
                    </div>
                    
                    <form method="POST" action="" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Member Since</label>
                                <input type="text" 
                                       value="<?php echo $member_since; ?>"
                                       class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-600"
                                       disabled>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                                <textarea name="address" 
                                          rows="3"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                    name="update_profile"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Change Password</h2>
                    </div>
                    
                    <form method="POST" action="" class="p-6" onsubmit="return validatePasswordForm()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                <input type="password" 
                                       name="current_password" 
                                       id="current_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                <input type="password" 
                                       name="new_password" 
                                       id="new_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#10854d] focus:border-transparent"
                                       required>
                            </div>
                            
                            <!-- Password Strength Meter -->
                            <div class="hidden" id="password-strength">
                                <div class="flex items-center mt-2">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-red-500" id="strength-bar" style="width: 0%"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-600" id="strength-text"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" 
                                    name="change_password"
                                    class="px-6 py-3 bg-[#10854d] text-white font-medium rounded-lg hover:bg-[#0d6e40] transition-colors">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Statistics -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-lg font-bold text-gray-800">Account Statistics</h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="stat-card bg-blue-50 p-4 rounded-lg">
                                <div class="text-blue-600 text-2xl font-bold"><?php echo $stats['total_orders']; ?></div>
                                <div class="text-sm text-gray-600">Total Orders</div>
                            </div>
                            
                            <div class="stat-card bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 text-2xl font-bold"><?php echo $stats['completed_orders']; ?></div>
                                <div class="text-sm text-gray-600">Completed</div>
                            </div>
                            
                            <div class="stat-card bg-yellow-50 p-4 rounded-lg">
                                <div class="text-yellow-600 text-2xl font-bold"><?php echo $stats['pending_orders']; ?></div>
                                <div class="text-sm text-gray-600">Pending</div>
                            </div>
                            
                            <div class="stat-card bg-purple-50 p-4 rounded-lg">
                                <div class="text-purple-600 text-2xl font-bold">₱<?php echo number_format($stats['total_spent'], 0); ?></div>
                                <div class="text-sm text-gray-600">Total Spent</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Password validation
        function validatePasswordForm() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass.length < 6) {
                alert('New password must be at least 6 characters long');
                return false;
            }
            
            if (newPass !== confirmPass) {
                alert('New passwords do not match');
                return false;
            }
            
            return true;
        }
        
        // Password strength meter
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length > 0) {
                strengthDiv.classList.remove('hidden');
                
                let strength = 0;
                if (password.length >= 6) strength += 25;
                if (password.match(/[a-z]+/)) strength += 25;
                if (password.match(/[A-Z]+/)) strength += 25;
                if (password.match(/[0-9]+/)) strength += 25;
                
                strengthBar.style.width = strength + '%';
                
                if (strength < 50) {
                    strengthBar.className = 'h-full bg-red-500';
                    strengthText.textContent = 'Weak';
                } else if (strength < 75) {
                    strengthBar.className = 'h-full bg-yellow-500';
                    strengthText.textContent = 'Medium';
                } else {
                    strengthBar.className = 'h-full bg-green-500';
                    strengthText.textContent = 'Strong';
                }
            } else {
                strengthDiv.classList.add('hidden');
            }
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>