<?php
session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $remember = isset($_POST['remember']);
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        // Login successful
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['logged_in'] = true;
        $success = true;
        
        if ($remember) {
            setcookie('remember_user', $email, time() + (30 * 24 * 60 * 60), '/');
        }
        
        header("refresh:2;url=dashboard.php");
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full flex items-center justify-center p-4" 
         style="background: url('../assets/images/Farm.jpg') center/cover no-repeat fixed;">
        <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-black/15 to-black/30 backdrop-blur-[1px]"></div>

        <?php if ($success): ?>
        <!-- Success Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-[40px] p-12 max-w-md mx-4 text-center animate-in">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check" class="w-10 h-10 text-green-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Login Successful!</h3>
                <p class="text-gray-600 mb-2">Welcome back, <?php echo htmlspecialchars($email); ?>!</p>
                <p class="text-gray-500 text-sm mb-6">Redirecting to dashboard...</p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-[#10854d] h-2 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="relative z-10 w-full max-w-5xl flex flex-col md:flex-row items-center gap-8 md:gap-12">
            
            <!-- Left Side: Branding Box -->
            <div class="w-full md:w-5/12 flex justify-center items-center">
    <div class=" p-90 flex flex-col items-center justify-center transition-all hover:scale-105">
        <div class="relative mb-6">
            <!-- Local logo image -->
            <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-100 h-100 object-contain">
        </div>
        <p class="mt-4 text-gray-600 font-medium text-center">Tagline dito</p>
    </div>
</div>

            <!-- Right Side: Login Form -->
            <div class="w-full md:w-7/12">
                <div class="glass rounded-[40px] p-10 md:p-12 shadow-2xl overflow-hidden relative min-h-[550px] flex flex-col backdrop-blur-md">
                    
                    <?php if (!empty($errors)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl backdrop-blur-sm">
                        <div class="flex items-center gap-2 text-red-600 mb-2">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            <span class="font-bold">Login Failed</span>
                        </div>
                        <ul class="text-sm text-red-600 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <h2 class="text-3xl font-bold text-[#10854d] mb-8 text-center">Welcome Back</h2>
                    <p class="text-gray-600 mb-8 text-center">Sign in to continue to your Harvee account</p>
                    
                    <form method="POST" action="" class="space-y-6 animate-in">
                        <div class="space-y-4">
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="email" name="email" placeholder="Email Address" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="password" name="password" placeholder="Password" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20" required>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <select name="role" 
                                        class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/20 appearance-none bg-white">
                                    <option value="customer" <?php echo (($_POST['role'] ?? 'customer') === 'customer') ? 'selected' : ''; ?>>Customer</option>
                                    <option value="farmer" <?php echo (($_POST['role'] ?? 'customer') === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none"></i>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 accent-[#10854d]" <?php echo (isset($_POST['remember']) ? 'checked' : ''); ?>>
                                <label for="remember" class="text-sm text-gray-700">Remember me</label>
                            </div>
                            <a href="forgot-password.php" class="text-sm text-[#10854d] font-medium hover:underline">
                                Forgot Password?
                            </a>
                        </div>
                        
                        <button type="submit" 
                                class="w-full py-4 bg-[#10854d] text-white font-bold rounded-full text-lg uppercase tracking-widest shadow-lg hover:bg-[#0d6e40] active:scale-95 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-5 h-5"></i> LOGIN
                        </button>
                    </form>
                    
                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white/20 backdrop-blur-sm text-gray-600">Or continue with</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <button type="button" class="py-3 bg-white border border-gray-300 rounded-full flex items-center justify-center gap-2 hover:bg-gray-50 transition-all">
                            <i data-lucide="facebook" class="w-5 h-5 text-blue-600"></i>
                            <span class="font-medium">Facebook</span>
                        </button>
                        <button type="button" class="py-3 bg-white border border-gray-300 rounded-full flex items-center justify-center gap-2 hover:bg-gray-50 transition-all">
                            <i data-lucide="google" class="w-5 h-5 text-red-600"></i>
                            <span class="font-medium">Google</span>
                        </button>
                    </div>
                    
                    <p class="text-center text-gray-600 mt-auto">
                        Don't have an account? 
                        <a href="register.php" class="text-[#10854d] font-bold hover:underline ml-1">
                            Create Account
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>