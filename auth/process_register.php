<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['form_data'])) {
    header('Location: register.php');
    exit;
}

$formData = $_SESSION['form_data'];
$firstName = trim($formData['firstName']);
$lastName = trim($formData['lastName']);
$username = trim($formData['username']);
$email = trim($formData['email']);
$password = $formData['password'];
$phone = trim($formData['phone']);
$address = trim($formData['address']);
$role = $formData['role'] ?? 'customer';

// Validate input
$errors = [];

if (empty($firstName)) {
    $errors[] = "First name is required";
}

if (empty($lastName)) {
    $errors[] = "Last name is required";
}

if (empty($username)) {
    $errors[] = "Username is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters";
}

if (!in_array($role, ['customer', 'farmer'])) {
    $errors[] = "Invalid role selected";
}

// Check if email already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered";
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Check if username already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Username already taken";
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// If no errors, insert user
if (empty($errors)) {
    try {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$firstName, $lastName, $username, $email, $hashed_password, $phone, $address, $role]);
        
        $user_id = $pdo->lastInsertId();
        
        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = true;
        
        // Clear form data
        unset($_SESSION['form_data']);
        
        // Redirect based on role
        if ($role === 'farmer') {
            header('Location: ../auth/login.php');
        } else {
            header('Location: ../auth/login.php');
        }
        exit();
        
    } catch (PDOException $e) {
        $errors[] = "Registration failed: " . $e->getMessage();
    }
}

// If there were errors, store them in session and redirect back
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['form_data'] = $formData;
    header('Location: register.php');
    exit();
}