<?php
session_start();
require_once '../config/database.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

$formData = [
    'name' => '',
    'email' => '',
    'password' => '',
    'confirmPassword' => '',
    'terms' => false,
    'role' => 'customer'
];

if (isset($_SESSION['form_data'])) {
    $formData = array_merge($formData, $_SESSION['form_data']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $value) {
        if (isset($_POST[$key])) {
            if ($key === 'terms') {
                $formData[$key] = isset($_POST[$key]);
            } else {
                $formData[$key] = htmlspecialchars($_POST[$key]);
            }
        }
    }
    
    if (isset($_POST['role'])) {
        $formData['role'] = $_POST['role'];
    }
    
    // Step 1 Validation
    if ($step === 1 && isset($_POST['next'])) {
        if (empty($formData['name'])) $errors[] = "Full name is required";
        if (empty($formData['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($errors)) {
            $_SESSION['form_data'] = $formData;
            header('Location: ?step=2');
            exit;
        }
    }
    
    // Step 2 Validation
    if ($step === 2) {
        if (isset($_POST['back'])) {
            header('Location: ?step=1');
            exit;
        }
        
        if (isset($_POST['next'])) {
            if (empty($formData['password'])) $errors[] = "Password is required";
            if (strlen($formData['password']) < 8) $errors[] = "Password must be at least 8 characters";
            if (empty($formData['confirmPassword'])) $errors[] = "Please confirm password";
            if ($formData['password'] !== $formData['confirmPassword']) $errors[] = "Passwords do not match";
            
            if (empty($errors)) {
                $_SESSION['form_data'] = $formData;
                header('Location: ?step=3');
                exit;
            }
        }
    }
    
    // Step 3 Validation and Registration
    if ($step === 3) {
        if (isset($_POST['back'])) {
            header('Location: ?step=2');
            exit;
        }
        
        if (isset($_POST['submit'])) {
            if (!$formData['terms']) $errors[] = "You must agree to the terms";
            
            if (empty($errors)) {
                // Proceed to process_register.php
                $_SESSION['form_data'] = $formData;
                header('Location: process_register.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvee - Register</title>
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
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full flex items-center justify-center p-4 bg-cover bg-center bg-no-repeat bg-fixed" 
     style="background-image: url('../assets/images/Farm.jpg'); background-size: 100%;">
     <div class="absolute inset-0 bg-gray-900/30"></div>

    <?php if ($success): ?>
    <!-- Success Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-[40px] p-12 max-w-md mx-4 text-center animate-in">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="check" class="w-10 h-10 text-green-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Registration Successful!</h3>
            <p class="text-gray-600 mb-6">Your account has been created successfully.</p>
            <a href="login.php" class="inline-block px-8 py-3 bg-[#10854d] text-white font-bold rounded-full hover:bg-[#0d6e40] transition-all">
                Go to Login
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="relative z-10 w-full max-w-5xl flex flex-col md:flex-row items-center gap-8 md:gap-12">
        
        <!-- Left Side: Branding Box -->
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

        <!-- Right Side: Registration Form -->
        <div class="w-full md:w-7/12">
            <!-- Changed glass to have more opacity for better text visibility -->
            <div class="bg-white/20 backdrop-blur-md rounded-[40px] p-8 md:p-10 shadow-2xl overflow-hidden relative min-h-[550px] flex flex-col ">
                
                <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center gap-2 text-red-600 mb-2">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-bold">Please fix the following errors:</span>
                    </div>
                    <ul class="text-sm text-red-600 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Progress Header -->
                <div class="flex items-center justify-between mb-8 px-2">
                    <?php for ($s = 1; $s <= 3; $s++): ?>
                        <div class="flex items-center flex-1 last:flex-none">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 <?php echo $step >= $s ? 'bg-[#10854d] text-white' : 'bg-gray-200 text-gray-400'; ?>">
                                <?php if ($step > $s): ?>
                                    <i data-lucide="check" class="w-5 h-5"></i>
                                <?php else: ?>
                                    <?php echo $s; ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($s < 3): ?>
                                <div class="h-1 flex-1 mx-2 rounded transition-all duration-300 <?php echo $step > $s ? 'bg-[#10854d]' : 'bg-gray-200'; ?>"></div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="flex-1">
                    <!-- Step 1: Account Information -->
                    <?php if ($step === 1): ?>
                        <form method="POST" action="" class="space-y-4 animate-in">
                            <!-- Changed text color to dark green for better visibility -->
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="relative">
                                    <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                    <input type="text" name="firstName" placeholder="First Name" value="<?php echo $formData['firstName']; ?>" 
                                           class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                                </div>
                                <div class="relative">
                                    <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                    <input type="text" name="lastName" placeholder="Last Name" value="<?php echo $formData['lastName']; ?>" 
                                           class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                                </div>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="layout-grid" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="text" name="username" placeholder="Username" value="<?php echo $formData['username']; ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="email" name="email" placeholder="Email Address" value="<?php echo $formData['email']; ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>
                            
                            <button type="submit" name="next" 
                                    class="w-full mt-6 py-4 bg-[#10854d] text-white font-bold rounded-full flex items-center justify-center gap-2 hover:bg-[#0d6e40] transition-all shadow-lg">
                                NEXT STEP <i data-lucide="chevron-right" class="w-5 h-5"></i>
                            </button>
                        </form>

                    <!-- Step 2: Security & Contact -->
                    <?php elseif ($step === 2): ?>
                        <form method="POST" action="" class="space-y-4 animate-in">
                            <!-- Changed text color to dark green -->
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Security & Contact</h2>
                            
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="password" name="password" placeholder="Password" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="password" name="confirmPassword" placeholder="Confirm Password" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>
                            
                            <div class="relative">
                                <i data-lucide="phone" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo $formData['phone']; ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>
                            
                            <div class="flex gap-4 mt-6">
                                <button type="submit" name="back" class="flex-1 py-4 bg-gray-200 text-gray-700 font-bold rounded-full flex items-center justify-center gap-2 hover:bg-gray-300 transition-all">
                                    <i data-lucide="chevron-left" class="w-5 h-5"></i> BACK
                                </button>
                                <button type="submit" name="next" class="flex-[2] py-4 bg-[#10854d] text-white font-bold rounded-full flex items-center justify-center gap-2 hover:bg-[#0d6e40] transition-all shadow-lg">
                                    CONTINUE <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </form>

                    <!-- Step 3: Final Details -->
                    <?php else: ?>
                        <form method="POST" action="" class="space-y-4 animate-in">
                            <!-- Changed text color to dark green -->
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Final Details</h2>
                            
                            <!-- Role Toggle -->
                            <div class="flex justify-center mb-6">
                                <div class="bg-gray-100 p-1.5 rounded-full flex items-center shadow-inner w-72">
                                    <button type="button" onclick="setRole('customer')" 
                                            class="flex-1 py-2.5 px-6 rounded-full text-sm font-bold transition-all duration-300 <?php echo ($formData['role'] ?? 'customer') === 'customer' ? 'bg-white text-[#10854d] shadow-md' : 'text-gray-700 hover:text-black'; ?>">
                                        CUSTOMER
                                    </button>
                                    <button type="button" onclick="setRole('farmer')" 
                                            class="flex-1 py-2.5 px-6 rounded-full text-sm font-bold transition-all duration-300 <?php echo ($formData['role'] ?? 'customer') === 'farmer' ? 'bg-white text-[#10854d] shadow-md' : 'text-gray-700 hover:text-black'; ?>">
                                        FARMER
                                    </button>
                                </div>
                                <input type="hidden" name="role" id="roleInput" value="<?php echo $formData['role'] ?? 'customer'; ?>">
                            </div>

                            <div class="relative">
                                <i data-lucide="map-pin" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                <input type="text" name="address" placeholder="Complete Address" value="<?php echo $formData['address']; ?>" 
                                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all" required>
                            </div>

                            <div id="farmerFields" style="<?php echo ($formData['role'] ?? 'customer') === 'farmer' ? '' : 'display: none;'; ?>">
                                <div class="space-y-4 pt-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="relative">
                                            <i data-lucide="briefcase" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                            <input type="text" name="farmName" placeholder="Farm Name" value="<?php echo $formData['farmName']; ?>" 
                                                   class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all">
                                        </div>
                                        <div class="relative">
                                            <i data-lucide="ruler" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                            <input type="number" name="farmSize" placeholder="Acres" value="<?php echo $formData['farmSize']; ?>" 
                                                   class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all">
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <i data-lucide="tag" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                                        <input type="text" name="farmProducts" placeholder="Main Products" value="<?php echo $formData['farmProducts']; ?>" 
                                               class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-300 focus:border-[#10854d] focus:outline-none focus:ring-2 focus:ring-[#10854d]/50 transition-all">
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 mt-4">
                                <input type="checkbox" id="terms" name="terms" <?php echo $formData['terms'] ? 'checked' : ''; ?> 
                                       class="mt-1 w-4 h-4 accent-[#10854d]" required>
                                <label for="terms" class="text-xs text-gray-700 leading-tight">
                                    I agree to the <span class="text-[#10854d] font-bold">Terms</span> and <span class="text-[#10854d] font-bold">Privacy Policy</span>.
                                </label>
                            </div>

                            <div class="flex gap-4 mt-6">
                                <button type="submit" name="back" class="flex-1 py-4 bg-gray-200 text-gray-700 font-bold rounded-full flex items-center justify-center gap-2 hover:bg-gray-300 transition-all">
                                    <i data-lucide="chevron-left" class="w-5 h-5"></i> BACK
                                </button>
                                <button type="submit" name="submit" class="flex-[3] py-4 bg-[#10854d] text-white font-bold rounded-full text-lg uppercase tracking-widest shadow-lg hover:bg-[#0d6e40] active:scale-95 transition-all">
                                    FINISH & SIGN UP
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Changed text to dark gray -->
                    <p class="text-center text-gray-700 text-sm mt-8">
                        Already a member? 
                        <a href="login.php" class="text-[#10854d] font-bold hover:underline ml-1">
                            Log in
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
    <script>
        // Role toggle functionality
        function setRole(role) {
            document.getElementById('roleInput').value = role;
            
            // Update button styles
            document.querySelectorAll('[onclick^="setRole"]').forEach(btn => {
                if (btn.getAttribute('onclick').includes(role)) {
                    btn.classList.add('bg-white', 'text-[#10854d]', 'shadow-md');
                    btn.classList.remove('text-gray-700', 'hover:text-black');
                } else {
                    btn.classList.remove('bg-white', 'text-[#10854d]', 'shadow-md');
                    btn.classList.add('text-gray-700', 'hover:text-black');
                }
            });
            
            // Show/hide farmer fields
            const farmerFields = document.getElementById('farmerFields');
            if (farmerFields) {
                farmerFields.style.display = role === 'farmer' ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>