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
                    $errors[] = "Email already exists. Please use a different email.";
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
                    $errors[] = "Username already taken. Please choose a different username.";
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
        
        if (isset($_POST['next'])) {
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
            if (!$formData['terms']) {
                $errors[] = "You must agree to the terms and conditions";
            }
            
            if (empty($formData['phone'])) {
                $errors[] = "Phone number is required";
            }
            
            if (empty($formData['address'])) {
                $errors[] = "Address is required";
            }
            
            if ($formData['role'] === 'farmer' && empty($formData['farmName'])) {
                $errors[] = "Farm name is required for farmers";
            }

            if (!in_array($formData['role'], ['customer', 'farmer', 'driver'], true)) {
                $errors[] = "Invalid role selected";
            }
            
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
        
        /* Fixed icon alignment for all input fields */
        .input-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #9CA3AF;
            z-index: 10;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .input-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 1px solid #E5E7EB;
            border-radius: 9999px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: white;
            line-height: 1.5;
            height: 52px;
        }
        
        .form-input:focus {
            border-color: #10854d;
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 133, 77, 0.1);
        }
        
        /* Fix for select element */
        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 4px;
            transition: all 0.3s ease;
            margin-top: 8px;
            border-radius: 9999px;
            overflow: hidden;
        }
        
        /* Role toggle styling */
        .role-toggle-container {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }
        
        .role-toggle {
            background: #F3F4F6;
            padding: 4px;
            border-radius: 9999px;
            display: inline-flex;
            width: 100%;
            max-width: 460px;
        }
        
        .role-btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-align: center;
            cursor: pointer;
            border: none;
            background: transparent;
        }
        
        .role-btn.active {
            background: white;
            color: #10854d;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .role-btn:not(.active) {
            color: #4B5563;
        }
        
        .role-btn:not(.active):hover {
            color: #1F2937;
        }
        
        /* Button styles */
        .btn-primary {
            width: 100%;
            padding: 14px 24px;
            background-color: #10854d;
            color: white;
            font-weight: 700;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            height: 52px;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background-color: #0d6e40;
        }
        
        .btn-secondary {
            padding: 14px 24px;
            background-color: #e5e7eb;
            color: #374151;
            font-weight: 700;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            height: 52px;
            font-size: 1rem;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        /* Terms checkbox */
        .terms-container {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 16px 0;
        }
        
        .terms-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #10854d;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .terms-label {
            font-size: 0.9rem;
            color: #374151;
            line-height: 1.5;
        }
        
        .terms-label a {
            color: #10854d;
            font-weight: 600;
            text-decoration: none;
        }
        
        .terms-label a:hover {
            text-decoration: underline;
        }
        
        /* Form container */
        .form-container {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .form-container::-webkit-scrollbar {
            width: 4px;
        }
        
        .form-container::-webkit-scrollbar-thumb {
            background: rgba(16, 133, 77, 0.3);
            border-radius: 10px;
        }
        
        /* Step indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding: 0 8px;
        }
        
        .step-item {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .step-item:last-child {
            flex: 0;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            transition: all 0.3s;
        }
        
        .step-circle.active {
            background-color: #10854d;
            color: white;
        }
        
        .step-circle.inactive {
            background-color: #e5e7eb;
            color: #9ca3af;
        }
        
        .step-line {
            height: 4px;
            flex: 1;
            margin: 0 12px;
            border-radius: 9999px;
            transition: all 0.3s;
        }
        
        .step-line.active {
            background-color: #10854d;
        }
        
        .step-line.inactive {
            background-color: #e5e7eb;
        }
        
        /* Error message */
        .error-container {
            margin-bottom: 24px;
            padding: 16px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 16px;
        }
        
        .error-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #dc2626;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .error-list {
            list-style-type: disc;
            list-style-position: inside;
            color: #dc2626;
            font-size: 0.9rem;
        }
        
        .error-list li {
            margin-bottom: 4px;
        }
        
        /* Button group */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .button-group .btn-secondary {
            flex: 1;
        }
        
        .button-group .btn-primary {
            flex: 2;
        }
        
        /* Login link */
        .login-link {
            text-align: center;
            color: #374151;
            font-size: 0.9rem;
            margin-top: 24px;
            padding-top: 20px;
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
        /* Base styles for all icons - keep as is */
.input-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #9CA3AF;
    z-index: 10;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Target the password field wrapper and its icon */
.password-field-wrapper .input-icon {
    transform: translateY(-80%);
    
    
    /* Also try these if transform doesn't work */
    /* top: 20px; /* Adjust pixel value */
    /* bottom: 10px; /* Adjust pixel value */
}
.password-field {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='11' width='18' height='11' rx='2' ry='2'%3E%3C/rect%3E%3Cpath d='M7 11V7a5 5 0 0 1 10 0v4'%3E%3C/path%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: 16px center !important;
    background-size: 20px !important;
    padding-left: 48px !important;
}
/* Target only the username icon using its class */
.username-icon {
    transform: translateY(-96%) !important;  /* Adjust this value */
    /* Try: -55%, -60%, -65% to move up more */
}
    </style>
</head>
<body>
    <div class="min-h-screen w-full flex items-center justify-center p-4 bg-cover bg-center bg-no-repeat bg-fixed" 
         style="background-image: url('../assets/images/Farm.jpg');">
        <div class="absolute inset-0 bg-gray-900/30"></div>

        <div class="relative z-10 w-full max-w-5xl flex flex-col md:flex-row items-center gap-8 md:gap-12">
            
            <!-- Left Side: Branding Box (Keep original, don't resize) -->
            <div class="w-full md:w-5/12 flex justify-center items-center">
                <div class="p-90 flex flex-col items-center justify-center transition-all hover:scale-105">
                    <div class="relative mb-6">
                        <img src="../assets/images/logo.png" alt="Harvee Logo" class="w-100 h-100 object-contain">
                    </div>
                    <p class="mt-4 text-gray-600 font-medium text-center">Fresh from Farm to Table</p>
                </div>
            </div>

            <!-- Right Side: Registration Form - Fixed components inside -->
            <div class="w-full md:w-7/12">
                <div class="bg-white/20 backdrop-blur-md rounded-[40px] p-8 md:p-10 shadow-2xl">
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                    <div class="error-container">
                        <div class="error-title">
                            <i data-lucide="alert-circle" width="20" height="20"></i>
                            <span>Please fix the following errors:</span>
                        </div>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Progress Header -->
                    <div class="step-indicator">
                        <?php for ($s = 1; $s <= 3; $s++): ?>
                            <div class="step-item">
                                <div class="step-circle <?php echo $step >= $s ? 'active' : 'inactive'; ?>">
                                    <?php if ($step > $s): ?>
                                        <i data-lucide="check" width="20" height="20"></i>
                                    <?php else: ?>
                                        <?php echo $s; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($s < 3): ?>
                                    <div class="step-line <?php echo $step > $s ? 'active' : 'inactive'; ?>"></div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Form Container -->
                    <div class="form-container">
                        <!-- Step 1: Account Information -->
                        <?php if ($step === 1): ?>
                            <form method="POST" action="">
                                <h2 class="text-2xl font-bold text-gray-800 mb-6">Account Information</h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- First Name -->
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="user" width="20" height="20"></i>
                                        </span>
                                        <input type="text" name="firstName" placeholder="First Name" 
                                               value="<?php echo htmlspecialchars($formData['firstName']); ?>" 
                                               class="form-input" required>
                                    </div>
                                    
                                    <!-- Last Name -->
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="user" width="20" height="20"></i>
                                        </span>
                                        <input type="text" name="lastName" placeholder="Last Name" 
                                               value="<?php echo htmlspecialchars($formData['lastName']); ?>" 
                                               class="form-input" required>
                                    </div>
                                </div>
                                
                                <!-- Username - Fixed icon alignment -->
                                <div class="input-wrapper">
    <span class="input-icon username-icon">  <!-- Added "username-icon" class -->
        <i data-lucide="layout-grid" width="20" height="20"></i>
    </span>
    <input type="text" name="username" placeholder="Username" 
           value="<?php echo htmlspecialchars($formData['username']); ?>" 
           class="form-input" required>
    <p class="text-xs text-gray-500 mt-1 ml-4">Only letters, numbers, and underscores</p>
</div>  
                                
                                <!-- Email -->
                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="mail" width="20" height="20"></i>
                                    </span>
                                    <input type="email" name="email" placeholder="Email Address" 
                                           value="<?php echo htmlspecialchars($formData['email']); ?>" 
                                           class="form-input" required>
                                </div>
                                
                                <button type="submit" name="next" class="btn-primary">
                                    NEXT STEP <i data-lucide="chevron-right" width="20" height="20"></i>
                                </button>
                            </form>

                       <!-- Step 2: Security & Contact - Fixed with password icon adjustment -->
<?php elseif ($step === 2): ?>
    <form method="POST" action="" id="step2Form">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Security & Contact</h2>
        
        <!-- Password Field - Icon can be adjusted here -->
        <!-- Password Field - FIXED - Icon won't move -->
<div class="input-wrapper">
    <input type="password" name="password" id="password" placeholder="Password" 
           class="form-input password-field" required>  <!-- Added password-field class -->
    <!-- Password strength indicator -->
    <div class="password-strength w-full bg-gray-200">
        <div id="strengthBar" class="h-1 bg-gray-300" style="width: 0%"></div>
    </div>
    <p id="strengthText" class="text-xs mt-1 ml-4 text-gray-500"></p>
</div>
        
        <!-- Confirm Password - Icon stays at default position -->
        <div class="input-wrapper">
            <span class="input-icon">
                <i data-lucide="lock" width="20" height="20"></i>
            </span>
            <input type="password" name="confirmPassword" placeholder="Confirm Password" 
                   class="form-input" required>
        </div>
        
        <!-- Phone - Icon stays at default position -->
        <div class="input-wrapper">
            <span class="input-icon">
                <i data-lucide="phone" width="20" height="20"></i>
            </span>
            <input type="tel" name="phone" placeholder="Phone Number" 
                   value="<?php echo htmlspecialchars($formData['phone']); ?>" 
                   class="form-input" required>
        </div>
        
        <div class="button-group">
            <button type="submit" name="back" class="btn-secondary">
                <i data-lucide="chevron-left" width="20" height="20"></i> BACK
            </button>
            <button type="submit" name="next" class="btn-primary">
                CONTINUE <i data-lucide="chevron-right" width="20" height="20"></i>
            </button>
        </div>
    </form>

                        <!-- Step 3: Final Details -->
                        <?php else: ?>
                            <form method="POST" action="">
                                <h2 class="text-2xl font-bold text-gray-800 mb-6">Final Details</h2>
                                
                                <!-- Role Toggle -->
                                <div class="role-toggle-container">
                                    <div class="role-toggle">
                                        <button type="button" onclick="setRole('customer')" 
                                                class="role-btn <?php echo ($formData['role'] ?? 'customer') === 'customer' ? 'active' : ''; ?>">
                                            CUSTOMER
                                        </button>
                                        <button type="button" onclick="setRole('farmer')" 
                                                class="role-btn <?php echo ($formData['role'] ?? 'customer') === 'farmer' ? 'active' : ''; ?>">
                                            FARMER
                                        </button>
                                        <button type="button" onclick="setRole('driver')" 
                                                class="role-btn <?php echo ($formData['role'] ?? 'customer') === 'driver' ? 'active' : ''; ?>">
                                            DRIVER
                                        </button>
                                    </div>
                                    <input type="hidden" name="role" id="roleInput" value="<?php echo $formData['role'] ?? 'customer'; ?>">
                                </div>

                                <!-- Address -->
                                <div class="input-wrapper">
                                    <span class="input-icon">
                                        <i data-lucide="map-pin" width="20" height="20"></i>
                                    </span>
                                    <input type="text" name="address" placeholder="Complete Address" 
                                           value="<?php echo htmlspecialchars($formData['address']); ?>" 
                                           class="form-input" required>
                                </div>

                                <!-- Farmer Fields -->
                                <div id="farmerFields" style="<?php echo ($formData['role'] ?? 'customer') === 'farmer' ? '' : 'display: none;'; ?>">
                                    <div class="input-wrapper">
                                        <span class="input-icon">
                                            <i data-lucide="briefcase" width="20" height="20"></i>
                                        </span>
                                        <input type="text" name="farmName" placeholder="Farm Name" 
                                               value="<?php echo htmlspecialchars($formData['farmName']); ?>" 
                                               class="form-input">
                                    </div>
                                </div>

                                <!-- Terms Checkbox -->
                                <div class="terms-container">
                                    <input type="checkbox" id="terms" name="terms" <?php echo $formData['terms'] ? 'checked' : ''; ?> 
                                           class="terms-checkbox" required>
                                    <label for="terms" class="terms-label">
                                        I agree to the <a href="#">Terms of Service</a> and 
                                        <a href="#">Privacy Policy</a>.
                                    </label>
                                </div>

                                <div class="button-group">
                                    <button type="submit" name="back" class="btn-secondary">
                                        <i data-lucide="chevron-left" width="20" height="20"></i> BACK
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Role toggle functionality
        function setRole(role) {
            document.getElementById('roleInput').value = role;
            
            // Update button styles
            document.querySelectorAll('.role-btn').forEach(btn => {
                if (btn.textContent.trim().toLowerCase() === role) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Show/hide farmer fields
            const farmerFields = document.getElementById('farmerFields');
            if (farmerFields) {
                farmerFields.style.display = role === 'farmer' ? 'block' : 'none';
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function(e) {
                const password = e.target.value;
                const strengthBar = document.getElementById('strengthBar');
                const strengthText = document.getElementById('strengthText');
                
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength += 25;
                
                // Lowercase check
                if (password.match(/[a-z]/)) strength += 25;
                
                // Uppercase check
                if (password.match(/[A-Z]/)) strength += 25;
                
                // Number check
                if (password.match(/[0-9]/)) strength += 25;
                
                strengthBar.style.width = strength + '%';
                
                // Update colors and text
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
    </script>
</body>
</html>
