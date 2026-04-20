<?php
session_start();
require_once '../config/database.php';

// Check if form data exists
if (!isset($_SESSION['form_data'])) {
    $_SESSION['register_errors'] = ['Registration session expired. Please try again.'];
    header('Location: register.php');
    exit;
}

$formData = $_SESSION['form_data'];
$firstName = trim($formData['firstName'] ?? '');
$lastName = trim($formData['lastName'] ?? '');
$username = trim($formData['username'] ?? '');
$email = trim($formData['email'] ?? '');
$password = $formData['password'] ?? '';
$phone = trim($formData['phone'] ?? '');
$address = trim($formData['address'] ?? '');
$latitude = trim((string)($formData['latitude'] ?? ''));
$longitude = trim((string)($formData['longitude'] ?? ''));
$farmName = trim($formData['farmName'] ?? '');
$role = $formData['role'] ?? 'customer';

// Create full name from first and last name
$fullName = $firstName . ' ' . $lastName;

// Validate input again (security measure)
$errors = [];

if (empty($firstName)) {
    $errors[] = "First name is required";
}

if (empty($lastName)) {
    $errors[] = "Last name is required";
}

if (empty($username)) {
    $errors[] = "Username is required";
} elseif (strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters";
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = "Password must contain at least one uppercase letter";
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors[] = "Password must contain at least one lowercase letter";
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = "Password must contain at least one number";
}

if (empty($phone)) {
    $errors[] = "Phone number is required";
}

if ($latitude !== '' && (!is_numeric($latitude) || (float)$latitude < -90 || (float)$latitude > 90)) {
    $errors[] = "Invalid latitude value";
}

if ($longitude !== '' && (!is_numeric($longitude) || (float)$longitude < -180 || (float)$longitude > 180)) {
    $errors[] = "Invalid longitude value";
}

if ($role === 'farmer' && empty($farmName)) {
    $errors[] = "Farm name is required for farmers";
}

if (!in_array($role, ['customer', 'farmer', 'driver'])) {
    $errors[] = "Invalid role selected";
}

// If there are validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['form_data'] = $formData;
    header('Location: register.php?step=2');
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $pdo->rollBack();
        $_SESSION['register_errors'] = ['Account already exists for this email. Please log in and update your address in profile settings.'];
        $_SESSION['form_data'] = $formData;
        header('Location: register.php?step=1');
        exit;
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $pdo->rollBack();
        $_SESSION['register_errors'] = ['Username already taken. If this is your account, please log in and update your address in profile settings.'];
        $_SESSION['form_data'] = $formData;
        header('Location: register.php?step=1');
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate verification token (optional)
    $verification_token = bin2hex(random_bytes(32));
    
    $has_latitude = false;
    $has_longitude = false;
    try {
        $col_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'latitude'");
        $has_latitude = (bool)$col_stmt->fetch();
        $col_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'longitude'");
        $has_longitude = (bool)$col_stmt->fetch();
    } catch (PDOException $e) {
        $has_latitude = false;
        $has_longitude = false;
    }

    if ($has_latitude && $has_longitude) {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name, 
                last_name, 
                name, 
                username, 
                email, 
                password, 
                phone, 
                address,
                latitude,
                longitude,
                role, 
                email_verified,
                verification_token,
                is_active,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $firstName,
            $lastName,
            $fullName,
            $username,
            $email,
            $hashed_password,
            $phone,
            $address,
            $latitude !== '' ? (float)$latitude : null,
            $longitude !== '' ? (float)$longitude : null,
            $role,
            1,
            $verification_token,
            1
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name, 
                last_name, 
                name, 
                username, 
                email, 
                password, 
                phone, 
                address, 
                role, 
                email_verified,
                verification_token,
                is_active,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $firstName,
            $lastName,
            $fullName,
            $username,
            $email,
            $hashed_password,
            $phone,
            $address,
            $role,
            1,
            $verification_token,
            1
        ]);
    }
    
    $user_id = $pdo->lastInsertId();
    
    // If user is a farmer, create farmer profile
    if ($role === 'farmer') {
        $stmt = $pdo->prepare("
            INSERT INTO farmer_profiles (
                user_id, 
                business_name, 
                business_address,
                business_phone,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $farmName,
            $address,
            $phone
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Set session variables (auto-login after registration)
    $_SESSION['user_id'] = $user_id;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['name'] = $fullName;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    // Clear form data
    unset($_SESSION['form_data']);
    
    // Send verification email (optional)
    // sendVerificationEmail($email, $verification_token);
    
    // Redirect based on role
    if ($role === 'farmer') {
        header('Location: ../farmer/dashboard.php?welcome=1');
    } elseif ($role === 'driver') {
        header('Location: ../driver/dashboard.php?welcome=1');
    } else {
        header('Location: ../customer/dashboard.php?welcome=1');
    }
    exit();
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Registration error: " . $e->getMessage());
    
    // User-friendly error message
    $_SESSION['register_errors'] = ['Registration failed. Please try again later.'];
    $_SESSION['form_data'] = $formData;
    header('Location: register.php?step=3');
    exit();
}

// Function to send verification email (optional)
function sendVerificationEmail($email, $token) {
    $subject = "Verify Your Harvee Account";
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/verify-email.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <h2>Welcome to Harvee!</h2>
        <p>Thank you for registering. Please click the link below to verify your email address:</p>
        <p><a href='$verification_link'>Verify Email Address</a></p>
        <p>If you didn't create an account, you can ignore this email.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@harvee.com" . "\r\n";
    
    // Uncomment to actually send email
    // mail($email, $subject, $message, $headers);
}
?>
