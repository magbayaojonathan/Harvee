<?php
session_start();
require_once '../config/database.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

// Initialize form data
$formData = [
    'firstName' => '',
    'lastName' => '',
    'username' => '',
    'email' => '',
    'password' => '',
    'confirmPassword' => '',
    'phone' => '',
    'address' => '',
    'latitude' => '',
    'longitude' => '',
    'farmName' => '',
    'terms' => false,
    'role' => 'customer'
];

$requestedRole = strtolower(trim($_GET['role'] ?? ''));
if (in_array($requestedRole, ['customer', 'farmer', 'driver'], true)) {
    $formData['role'] = $requestedRole;
}

// Load saved form data from session
if (isset($_SESSION['form_data'])) {
    $formData = array_merge($formData, $_SESSION['form_data']);
}

// Load errors from session
if (isset($_SESSION['register_errors'])) {
    $errors = $_SESSION['register_errors'];
    unset($_SESSION['register_errors']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update form data from POST
    foreach ($formData as $key => $value) {
        if (isset($_POST[$key])) {
            if ($key === 'terms') {
                $formData[$key] = isset($_POST[$key]);
            } else {
                $formData[$key] = trim(htmlspecialchars($_POST[$key]));
            }
        }
    }
    
    if (isset($_POST['role'])) {
        $formData['role'] = $_POST['role'];
    }
    
    // Step 1 Validation
    if ($step === 1 && isset($_POST['next'])) {
        if (!in_array($formData['role'], ['customer', 'farmer', 'driver'], true)) {
            $errors[] = "Please choose a valid account type";
        }
        if (empty($formData['firstName'])) $errors[] = "First name is required";
        if (empty($formData['lastName'])) $errors[] = "Last name is required";
        if (empty($formData['username'])) $errors[] = "Username is required";
        if (strlen($formData['username']) < 3) $errors[] = "Username must be at least 3 characters";
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['username'])) $errors[] = "Username can only contain letters, numbers, and underscores";
        
        if (empty($formData['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check for duplicate email in database
        if (empty($errors)) {
            try {
                $emailCheckStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $emailCheckStmt->execute([$formData['email']]);
                if ($emailCheckStmt->rowCount() > 0) {
                    $errors[] = "Account already exists for this email. Please log in and update your address in your profile settings.";
                }
            } catch (PDOException $e) {
                error_log("Email check error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again.";
            }
        }
        
        // Check for duplicate username in database
        if (empty($errors) && !empty($formData['username'])) {
            try {
                $usernameCheckStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $usernameCheckStmt->execute([$formData['username']]);
                if ($usernameCheckStmt->rowCount() > 0) {
                    $errors[] = "Username already taken. If this is your account, log in and update your address in profile settings.";
                }
            } catch (PDOException $e) {
                error_log("Username check error: " . $e->getMessage());
                $errors[] = "An error occurred. Please try again.";
            }
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
        
        if (isset($_POST['submit'])) {
            if (empty($formData['password'])) {
                $errors[] = "Password is required";
            } elseif (strlen($formData['password']) < 8) {
                $errors[] = "Password must be at least 8 characters";
            } elseif (!preg_match('/[A-Z]/', $formData['password'])) {
                $errors[] = "Password must contain at least one uppercase letter";
            } elseif (!preg_match('/[a-z]/', $formData['password'])) {
                $errors[] = "Password must contain at least one lowercase letter";
            } elseif (!preg_match('/[0-9]/', $formData['password'])) {
                $errors[] = "Password must contain at least one number";
            }
            
            if (empty($formData['confirmPassword'])) {
                $errors[] = "Please confirm password";
            } elseif ($formData['password'] !== $formData['confirmPassword']) {
                $errors[] = "Passwords do not match";
            }

            if (!$formData['terms']) {
                $errors[] = "You must agree to the terms and conditions";
            }
            
            if (empty($formData['phone'])) {
                $errors[] = "Phone number is required";
            }

            if ($formData['role'] === 'farmer' && empty($formData['farmName'])) {
                $errors[] = "Farm name is required for farmers";
            }

            if (!in_array($formData['role'], ['customer', 'farmer', 'driver'], true)) {
                $errors[] = "Invalid role selected";
            }
            
            if (empty($errors)) {
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
        
        /* Input wrapper – consistent icon alignment */
        .input-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 0.75rem;
            --field-top-offset: 0px;
        }
        .input-wrapper.has-label {
            padding-top: 1.6rem;
            --field-top-offset: 1.6rem;
        }
        
        .input-icon {
            position: absolute;
            left: 14px;
            top: calc(var(--field-top-offset) + 22px);
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9CA3AF;
            z-index: 10;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .input-icon svg {
            width: 18px;
            height: 18px;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border: 1px solid #E5E7EB;
            border-radius: 9999px;
            font-size: 0.9rem;
            transition: all 0.2s;
            background-color: white;
            line-height: 1.4;
            height: 44px;
        }
        
        .form-input:focus {
            border-color: #10854d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 133, 77, 0.1);
        }
        
        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
        }
        
        /* Password strength */
        .password-strength {
            height: 3px;
            transition: all 0.3s ease;
            margin-top: 6px;
            border-radius: 9999px;
            overflow: hidden;
        }
        
        /* Buttons */
        .btn-primary {
            width: 100%;
            padding: 10px 18px;
            background-color: #10854d;
            color: white;
            font-weight: 700;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            height: 44px;
            font-size: 0.9rem;
        }
        
        .btn-primary:hover {
            background-color: #0d6e40;
        }
        
        .btn-secondary {
            padding: 10px 18px;
            background-color: #e5e7eb;
            color: #374151;
            font-weight: 700;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            height: 44px;
            font-size: 0.9rem;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        /* Terms */
        .terms-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 14px 0;
        }
        
        .terms-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #10854d;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .terms-label {
            font-size: 0.8rem;
            color: #374151;
            line-height: 1.4;
        }
        
        .terms-label a {
            color: #10854d;
            font-weight: 600;
            text-decoration: none;
        }
        
        .terms-label a:hover {
            text-decoration: underline;
        }
        
        .field-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
            margin: 0 0 4px 6px;
        }
        
        .input-wrapper.has-label .field-label {
            position: absolute;
            top: 0;
            left: 0;
            margin: 0 0 4px 6px;
        }
        
        .input-wrapper.has-label .input-icon {
            transform: translateY(-50%);
        }
        
        /* Social auth buttons – black text only */
        .social-auth {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin: 12px 0 16px;
        }
        
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 9999px;
            border: 1px solid rgba(209, 213, 219, 0.95);
            background: rgba(255, 255, 255, 0.96);
            color: #000000; /* black text */
            font-size: 0.8rem;
            font-weight: 700;
            padding: 8px 12px;
            transition: all 0.2s ease;
        }
        .social-btn svg,
        .social-btn i,
        .social-btn img {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            object-fit: contain;
        }
        .social-btn span {
            line-height: 1;
        }
        
        .social-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 16px -14px rgba(15, 23, 42, 0.45);
        }
        
        /* No red/blue overrides */
        
        .brand-tagline {
            margin-top: 0.5rem;
            color: #14532d;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-align: center;
            text-transform: uppercase;
            text-shadow: 0 2px 10px rgba(255, 255, 255, 0.45);
            font-size: 0.7rem;
        }
        
        /* Error container */
        .error-container {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
        }
        
        .error-title {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #dc2626;
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        
        .error-list {
            list-style-type: disc;
            list-style-position: inside;
            color: #dc2626;
            font-size: 0.8rem;
        }
        
        .error-list li {
            margin-bottom: 2px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        
        .button-group .btn-secondary {
            flex: 1;
        }
        
        .button-group .btn-primary {
            flex: 2;
        }
        
        .login-link {
            text-align: center;
            color: #374151;
            font-size: 0.8rem;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-link a {
            color: #10854d;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Form container – no scroll */
        .form-container {
            max-height: none;
            overflow: visible;
            padding-right: 0;
        }
        
        .grid-cols-1.md\:grid-cols-2 {
            gap: 0.75rem;
        }
        
        .logo-link {
            display: inline-block;
            transition: transform 0.2s ease;
        }
        .logo-link:hover {
            transform: scale(1.02);
        }
        
        .bottom-home-link {
            text-align: center;
            margin-top: 12px;
        }
        .bottom-home-link a {
            font-size: 0.7rem;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s;
        }
        .bottom-home-link a:hover {
            color: #10854d;
        }
        
        /* Password field uses background-image for lock icon, no extra icon span */
        .password-field {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='11' width='18' height='11' rx='2' ry='2'%3E%3C/rect%3E%3Cpath d='M7 11V7a5 5 0 0 1 10 0v4'%3E%3C/path%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: 14px center !important;
            background-size: 18px !important;
            padding-left: 42px !important;
        }
    </style>
</head>
<body>
    <div class="min-h-screen w-full flex items-center justify-center p-4 bg-cover bg-center bg-no-repeat bg-fixed" 
         style="background-image: url('../assets/images/farm.jpg');">
        <div class="absolute inset-0 bg-gray-900/30"></div>

        <div class="relative z-10 w-full max-w-5xl flex flex-col md:flex-row items-center gap-6 md:gap-8">
            
            <!-- Left Side: Clickable Logo -->
            <div class="w-full md:w-5/12 flex justify-center items-center">
                <a href="../index.php" class="logo-link">
                    <div class="flex flex-col items-center justify-center">
                        <div class="relative mb-2">
                            <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-150 h-auto object-contain">
                        </div>
                        <p class="brand-tagline">Fresh from Farm to Table</p>
                    </div>
                </a>
            </div>

            <!-- Right Side: Compact Registration Form -->
            <div class="w-full md:w-7/12">
                <div class="glass rounded-3xl p-5 md:p-6 shadow-2xl">
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                    <div class="error-container">
                        <div class="error-title">
                            <i data-lucide="alert-circle" width="16" height="16"></i>
                            <span>Please fix the following errors:</span>
                        </div>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <!-- Step 1: Account Information -->
                        <?php if ($step === 1): ?>
                            <form method="POST" action="">
                                <h2 class="text-xl font-bold text-gray-800 mb-4">Account Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="user" width="16" height="16"></i>
                                        </span>
                                        <input type="text" name="firstName" placeholder="First Name" 
                                               value="<?php echo htmlspecialchars($formData['firstName']); ?>" 
                                               class="form-input" required>
                                    </div>
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="user" width="16" height="16"></i>
                                        </span>
                                        <input type="text" name="lastName" placeholder="Last Name" 
                                               value="<?php echo htmlspecialchars($formData['lastName']); ?>" 
                                               class="form-input" required>
                                    </div>
                                </div>
                                
                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="layout-grid" width="16" height="16"></i>
                                    </span>
                                    <input type="text" name="username" placeholder="Username" 
                                           value="<?php echo htmlspecialchars($formData['username']); ?>" 
                                           class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1 ml-4">Letters, numbers, underscores only</p>
                                </div>  
                                
                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="mail" width="16" height="16"></i>
                                    </span>
                                    <input type="email" name="email" placeholder="Email Address" 
                                           value="<?php echo htmlspecialchars($formData['email']); ?>" 
                                           class="form-input" required>
                                </div>

                                <div class="input-wrapper has-label">
                                    <label for="roleInput" class="field-label">Sign up as</label>
                                    <span class="input-icon">
                                        <i data-lucide="users" width="16" height="16"></i>
                                    </span>
                                    <select name="role" id="roleInput" class="form-input" onchange="setRole(this.value)" required>
                                        <option value="customer" <?php echo ($formData['role'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="farmer" <?php echo ($formData['role'] ?? 'customer') === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                        <option value="driver" <?php echo ($formData['role'] ?? 'customer') === 'driver' ? 'selected' : ''; ?>>Driver</option>
                                    </select>
                                </div>
                                
                                <div class="social-auth">
                                    <button type="button" class="social-btn google">
                                        <img src="../assets/images/google.png" alt="Google"><span>Google</span>
                                    </button>
                                    <button type="button" class="social-btn facebook">
                                        <img src="../assets/images/facebook.png" alt="Facebook"><span>Facebook</span>
                                    </button>
                                </div>

                                <button type="submit" name="next" class="btn-primary">
                                    NEXT STEP <i data-lucide="chevron-right" width="16" height="16"></i>
                                </button>
                            </form>

                        <!-- Step 2: Security & Contact -->
                        <?php else: ?>
                            <form method="POST" action="" id="step2Form">
                                <h2 class="text-xl font-bold text-gray-800 mb-1">Security & Contact</h2>
                                <p class="text-xs text-gray-500 mb-4">Address setup will be managed separately after sign-up.</p>

                                <div class="input-wrapper">
                                    <input type="password" name="password" id="password" placeholder="Password" 
                                           class="form-input password-field" required>
                                    <div class="password-strength w-full bg-gray-200">
                                        <div id="strengthBar" class="h-1 bg-gray-300" style="width: 0%"></div>
                                    </div>
                                    <p id="strengthText" class="text-xs mt-1 ml-4 text-gray-500"></p>
                                </div>

                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="lock" width="16" height="16"></i>
                                    </span>
                                    <input type="password" name="confirmPassword" placeholder="Confirm Password" 
                                           class="form-input" required>
                                </div>

                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="phone" width="16" height="16"></i>
                                    </span>
                                    <input type="tel" name="phone" placeholder="Phone Number" 
                                           value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                                           class="form-input" required>
                                </div>

                                <div id="farmerFields" style="<?php echo ($formData['role'] ?? 'customer') === 'farmer' ? '' : 'display: none;'; ?>">
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="briefcase" width="16" height="16"></i>
                                        </span>
                                        <input type="text" name="farmName" placeholder="Farm or store name" 
                                               value="<?php echo htmlspecialchars($formData['farmName']); ?>" 
                                               class="form-input">
                                    </div>
                                </div>

                                <div class="terms-container">
                                    <input type="checkbox" id="terms" name="terms" <?php echo $formData['terms'] ? 'checked' : ''; ?> 
                                           class="terms-checkbox" required>
                                    <label for="terms" class="terms-label">
                                        I agree to the <a href="#">Terms of Service</a> and 
                                        <a href="#">Privacy Policy</a>.
                                    </label>
                                </div>

                                <div class="button-group">
                                    <button type="submit" name="back" class="btn-secondary" formnovalidate>
                                        <i data-lucide="chevron-left" width="16" height="16"></i> BACK
                                    </button>
                                    <button type="submit" name="submit" class="btn-primary">
                                        FINISH & SIGN UP
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <!-- Login Link -->
                        <p class="login-link">
                            Already a member? 
                            <a href="login.php">Log in</a>
                        </p>
                        
                        <!-- Subtle home link at bottom -->
                        <div class="bottom-home-link">
                            <a href="../index.php">
                                <i data-lucide="arrow-left" width="12" height="12"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        function setRole(role) {
            const roleInput = document.getElementById('roleInput');
            if (roleInput) roleInput.value = role;
            const farmerFields = document.getElementById('farmerFields');
            if (farmerFields) farmerFields.style.display = role === 'farmer' ? 'block' : 'none';
        }

        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function(e) {
                const password = e.target.value;
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                let strength = 0;
                if (password.length >= 8) strength += 25;
                if (password.match(/[a-z]/)) strength += 25;
                if (password.match(/[A-Z]/)) strength += 25;
                if (password.match(/[0-9]/)) strength += 25;
                strengthBar.style.width = strength + '%';
                if (strength <= 25) {
                    strengthBar.className = 'h-1 bg-red-500';
                    strengthText.textContent = 'Weak password';
                    strengthText.className = 'text-xs mt-1 ml-4 text-red-500';
                } else if (strength <= 50) {
                    strengthBar.className = 'h-1 bg-yellow-500';
                    strengthText.textContent = 'Fair password';
                    strengthText.className = 'text-xs mt-1 ml-4 text-yellow-600';
                } else if (strength <= 75) {
                    strengthBar.className = 'h-1 bg-blue-500';
                    strengthText.textContent = 'Good password';
                    strengthText.className = 'text-xs mt-1 ml-4 text-blue-600';
                } else {
                    strengthBar.className = 'h-1 bg-green-500';
                    strengthText.textContent = 'Strong password';
                    strengthText.className = 'text-xs mt-1 ml-4 text-green-600';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roleInput = document.getElementById('roleInput');
            if (roleInput) setRole(roleInput.value || 'customer');
        });
    </script>
</body>
</html>
