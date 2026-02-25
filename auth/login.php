<?php
session_start();
require_once '../config/database.php';

$errors = [];
$success = false;
$registered = isset($_GET['registered']) ? true : false;

// Check for remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $email = $_COOKIE['remember_user'];
    // Auto-fill email in form
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $remember = isset($_POST['remember']);
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        try {
            // Query user with the selected role
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, name, username, email, password, role, is_active, email_verified FROM users WHERE email = ? AND role = ?");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                
                // Check if account is active
                if (!$user['is_active']) {
                    $errors[] = "Your account has been deactivated. Please contact support.";
                } 
                else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['name'] = $user['name'] ?: ($user['first_name'] . ' ' . $user['last_name']);
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                    // Update last login
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Set remember me cookie
                    if ($remember) {
                        setcookie('remember_user', $email, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    $success = true;
                    
                    // Determine dashboard based on role
                    $dashboard = ($user['role'] === 'farmer') ? '../farmer/dashboard.php' : '../customer/dashboard.php';
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=$dashboard");
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvee - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/lucide-static@0.263.0/font/lucide.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .glass-dark {
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(16, 133, 77, 0.5);
            border-radius: 10px;
        }
        .animate-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        /* Dropdown animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-animate {
    animation: slideDown 0.2s ease-out;
}

/* Hover effects */
.role-option {
    position: relative;
    overflow: hidden;
}

.role-option::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background-color: #10854d;
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.role-option:hover::after {
    transform: scaleY(1);
}

.role-option.bg-green-50::after {
    transform: scaleY(1);
}

/* Icon animations */
.role-option i {
    transition: transform 0.2s ease;
}

.role-option:hover i:first-child {
    transform: scale(1.1);
}

/* Dropdown button hover */
#dropdownButton:hover {
    border-color: #10854d;
    box-shadow: 0 2px 8px rgba(16, 133, 77, 0.1);
}

#dropdownButton:hover .text-gray-400 {
    color: #10854d;
}
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
     <!-- Main container with background -->
    <div class="relative min-h-screen w-full flex items-center justify-center p-4" 
         style="background: url('../assets/images/Farm.jpg') center/cover no-repeat fixed;">
        
        <!-- FIXED gradient that covers ENTIRE viewport, even when scrolling -->
        <div class="fixed inset-0 bg-gradient-to-b from-black/30 via-black/15 to-black/30 backdrop-blur-[1px] pointer-events-none"></div>

        <!-- Success Modal -->
        <?php if ($success): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-[40px] p-12 max-w-md mx-4 text-center animate-in">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check" class="w-10 h-10 text-green-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Login Successful!</h3>
                <p class="text-gray-600 mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>!</p>
                <p class="text-gray-500 text-sm mb-6">Redirecting to dashboard...</p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-[#10854d] h-2 rounded-full animate-pulse" style="width: 100%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Registration Success Message -->
        <?php if ($registered): ?>
        <div class="fixed top-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl shadow-lg animate-in">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
                <span class="font-medium">Registration successful! Please login.</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="relative z-10 w-full max-w-5xl flex flex-col md:flex-row items-center gap-8 md:gap-12">
            
            <!-- Left Side: Branding Box -->
            <div class="w-full md:w-5/12 flex justify-center items-center">
                <div class="p-90 flex flex-col items-center justify-center transition-all hover:scale-105">
                    <div class="relative mb-6">
                        <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-100 h-100 object-contain">
                    </div>
                    <p class="mt-4 text-gray-600 font-medium text-center">Fresh from Farm to Table</p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="w-full md:w-7/12">
                <div class="glass rounded-[40px] p-10 md:p-12 shadow-2xl overflow-hidden relative min-h-[550px] flex flex-col backdrop-blur-md">
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl backdrop-blur-sm">
                        <div class="flex items-center gap-2 text-red-600 mb-2">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            <span class="font-bold">Login Failed</span>
                        </div>
                        <ul class="text-sm text-red-600 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <h2 class="text-3xl font-bold text-[#10854d] mb-8 text-center">Welcome Back</h2>
                    <p class="text-gray-600 mb-8 text-center">Sign in to continue to your Harvee account</p>
                    
                    <form method="POST" action="" class="space-y-6 animate-in">
                        <div class="space-y-4">
                            <!-- Email Field -->
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="email" name="email" placeholder="Email Address" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ($_COOKIE['remember_user'] ?? '')); ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="password" name="password" placeholder="Password" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            </div>
                            
                           <!-- Role Selection - Enhanced Dropdown (Pure CSS/JS) -->
<div class="relative" id="roleDropdown">
    <!-- Hidden original select -->
    <select name="role" id="roleSelect" class="hidden">
        <option value="customer" <?php echo (($_POST['role'] ?? 'customer') === 'customer') ? 'selected' : ''; ?>>Customer</option>
        <option value="farmer" <?php echo (($_POST['role'] ?? 'customer') === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
    </select>
    
   <!-- Custom dropdown button - FIXED ALIGNMENT -->
<button type="button" 
        id="dropdownButton"
        class="w-full py-3 px-4 rounded-full border border-gray-300 bg-white focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20 flex items-center justify-between transition-all duration-200 hover:border-gray-400 relative">
    <div class="flex items-center gap-2 ml-8"> <!-- Added ml-8 to push text right -->
        <i data-lucide="user" class="absolute left-4 text-gray-400 w-5 h-5"></i> <!-- Made icon absolute -->
        <span id="selectedRoleText" class="text-gray-700"><?php echo ucfirst($_POST['role'] ?? 'Customer'); ?></span>
    </div>
    <i data-lucide="chevron-down" id="dropdownIcon" class="text-gray-400 w-5 h-5 transition-transform duration-200"></i>
</button>
    
    <!-- Dropdown menu -->
    <div id="dropdownMenu" 
         class="absolute z-50 w-full mt-2 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden hidden opacity-0 translate-y-1 transition-all duration-200">
        
        <!-- Customer option -->
        <button type="button"
                class="w-full px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors duration-150 role-option <?php echo (($_POST['role'] ?? 'customer') === 'customer') ? 'bg-green-50' : ''; ?>"
                data-value="customer">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                <i data-lucide="shopping-bag" class="w-4 h-4 text-[#10854d]"></i>
            </div>
            <div class="flex-1 text-left">
                <div class="font-medium text-gray-800">Customer</div>
                <div class="text-xs text-gray-500">Browse and buy fresh products</div>
            </div>
            <i data-lucide="check" class="w-5 h-5 text-[#10854d] <?php echo (($_POST['role'] ?? 'customer') === 'customer') ? '' : 'hidden'; ?> check-icon"></i>
        </button>
        
        <!-- Divider -->
        <div class="border-t border-gray-100"></div>
        
        <!-- Farmer option -->
        <button type="button"
                class="w-full px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors duration-150 role-option <?php echo (($_POST['role'] ?? 'customer') === 'farmer') ? 'bg-green-50' : ''; ?>"
                data-value="farmer">
            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                <i data-lucide="tractor" class="w-4 h-4 text-amber-600"></i>
            </div>
            <div class="flex-1 text-left">
                <div class="font-medium text-gray-800">Farmer</div>
                <div class="text-xs text-gray-500">Sell your farm products</div>
            </div>
            <i data-lucide="check" class="w-5 h-5 text-[#10854d] <?php echo (($_POST['role'] ?? 'customer') === 'farmer') ? '' : 'hidden'; ?> check-icon"></i>
        </button>
    </div>
</div>
                        
                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 accent-[#10854d]" <?php echo (isset($_POST['remember']) || isset($_COOKIE['remember_user'])) ? 'checked' : ''; ?>>
                                <label for="remember" class="text-sm text-gray-700">Remember me</label>
                            </div>
                            <a href="forgot-password.php" class="text-sm text-[#10854d] font-medium hover:underline">
                                Forgot Password?
                            </a>
                        </div>
                        
                        <!-- Login Button -->
                        <button type="submit" 
                                class="w-full py-4 bg-[#10854d] text-white font-bold rounded-full text-lg uppercase tracking-widest shadow-lg hover:bg-[#0d6e40] active:scale-95 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-5 h-5"></i> LOGIN
                        </button>
                    </form>
                    
                    <!-- REMOVED: Facebook and Google login options -->
                    
                    <!-- Register Link -->
                    <p class="text-center text-gray-600 mt-8">
                        Don't have an account? 
                        <a href="register.php" class="text-[#10854d] font-bold hover:underline ml-1">
                            Create Account
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lucide Icons Script -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    
    // Auto-hide registration success message after 5 seconds
    <?php if ($registered): ?>
    setTimeout(function() {
        document.querySelector('.fixed.top-4.right-4').style.display = 'none';
    }, 5000);
    <?php endif; ?>
    
    // Dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownButton = document.getElementById('dropdownButton');
        const dropdownMenu = document.getElementById('dropdownMenu');
        const dropdownIcon = document.getElementById('dropdownIcon');
        const selectedRoleText = document.getElementById('selectedRoleText');
        const roleSelect = document.getElementById('roleSelect');
        const roleOptions = document.querySelectorAll('.role-option');
        const checkIcons = document.querySelectorAll('.check-icon');
        
        // Toggle dropdown
        dropdownButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = !dropdownMenu.classList.contains('hidden');
            
            if (isOpen) {
                dropdownMenu.classList.add('hidden');
                dropdownIcon.style.transform = 'rotate(0deg)';
            } else {
                dropdownMenu.classList.remove('hidden');
                setTimeout(() => {
                    dropdownMenu.classList.remove('opacity-0', 'translate-y-1');
                    dropdownMenu.classList.add('opacity-100', 'translate-y-0');
                }, 10);
                dropdownIcon.style.transform = 'rotate(180deg)';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.add('hidden', 'opacity-0', 'translate-y-1');
                dropdownMenu.classList.remove('opacity-100', 'translate-y-0');
                dropdownIcon.style.transform = 'rotate(0deg)';
            }
        });
        
        // Select option
        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const text = value === 'customer' ? 'Customer' : 'Farmer';
                
                // Update hidden select
                roleSelect.value = value;
                
                // Update button text
                selectedRoleText.textContent = text;
                
                // Update check icons
                checkIcons.forEach(icon => icon.classList.add('hidden'));
                this.querySelector('.check-icon').classList.remove('hidden');
                
                // Update background colors
                roleOptions.forEach(opt => opt.classList.remove('bg-green-50'));
                this.classList.add('bg-green-50');
                
                // Close dropdown
                dropdownMenu.classList.add('hidden', 'opacity-0', 'translate-y-1');
                dropdownMenu.classList.remove('opacity-100', 'translate-y-0');
                dropdownIcon.style.transform = 'rotate(0deg)';  // ✅ Fixed: removed extra )
            });
        });
    });
</script>
</body>
</html>