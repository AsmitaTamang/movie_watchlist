<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Basic validation
    $errors = [];
    
    if (empty($fullName)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                header("Location: index.php?message=Email already registered!&status=error");
                exit;
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$fullName, $email, $hashedPassword]);
                
                header("Location: login.php?message=Registration successful! Please login.&status=success");
                exit;
            }
        } catch (PDOException $e) {
            header("Location: index.php?message=Database error: " . $e->getMessage() . "&status=error");
            exit;
        }
    } else {
        // Redirect back with errors
        $errorMessage = urlencode(implode(", ", $errors));
        header("Location: index.php?message=$errorMessage&status=error");
        exit;
    }
} else {
    // If someone tries to access directly, redirect to form
    header("Location: index.php");
    exit;
}
?>