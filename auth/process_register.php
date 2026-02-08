<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $name = $first_name . ' ' . $last_name;
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
    
    // Farmer specific fields
    $farm_name = ($role === 'farmer' && !empty($_POST['farm_name'])) ? trim($_POST['farm_name']) : null;
    $farm_size = ($role === 'farmer' && !empty($_POST['farm_size'])) ? (float)$_POST['farm_size'] : null;
    $farm_type = ($role === 'farmer' && !empty($_POST['farm_type'])) ? trim($_POST['farm_type']) : null;
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if (!in_array($role, ['farmer', 'customer'])) {
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
    
    // If no errors, insert user
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $role, $phone, $location]);
            
            $user_id = $pdo->lastInsertId();
            
            // If farmer, insert farm details
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("INSERT INTO farmers (user_id, farm_name, farm_size, farm_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $farm_name, $farm_size, $farm_type]);
            }
            
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            
            // Redirect based on role
            if ($role === 'farmer') {
                header('Location: ../farmer/dashboard.php');
            } else {
                header('Location: ../customer/dashboard.php');
            }
            exit();
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    // If there were errors, store them in session and redirect back
    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_data'] = $_POST;
    header('Location: register.php');
    exit();
}

// If not POST request, redirect to register page
header('Location: register.php');
exit();