
<?php

// This file validates registration data and initiates security questions setup

// Include database connection for user registration
require_once 'dbconnect.php';

session_start();

// Check if form was submitted using POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and trim whitespace from text fields
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Initialize errors array to collect validation errors
    $errors = [];
    
    // Validate full name - check if empty
    if (empty($fullName)) { 
        $errors[] = "Full name is required";
    }
    
    // Validate email - check if empty and valid format
    if (empty($email)) {
        $errors[] = "Email is required";
        // Validate email format using PHP's built-in filter
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate password - check if empty and meets length requirement
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Validate password confirmation - check if passwords match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        try {
            // Check if email already exists in database to prevent duplicates
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            // If user with this email already exists
            if ($checkStmt->fetch()) {
                // Redirect back to registration form with error message
                header("Location: index.php?message=Email already registered!&status=error");
                exit; 
            } else {
                // Email is available - proceed with registration
                
                // Hash password for secure storage using bcrypt algorithm
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Get 4 active security questions for user to answer
                $questionStmt = $pdo->query("SELECT question_id, question_text, expected_data_type FROM security_questions WHERE is_active = TRUE ORDER BY RAND() LIMIT 4");
                $questions = $questionStmt->fetchAll();
                
                // Verify we got exactly 4 questions
                if (count($questions) < 4) {
                    header("Location: index.php?message=System error: Not enough security questions&status=error");
                    exit; // Stop if insufficient questions
                }
                
                // Store user registration data in session for security questions step
                // This allows us to pass data between registration steps
                $_SESSION['registering_user'] = [
                    'username' => $fullName,      // User's chosen username (full name)
                    'email' => $email,           // User's email address
                    'password_hash' => $hashedPassword, // Securely hashed password
                    'questions' => $questions    // Array of security questions with IDs and types
                ];
                
                // Redirect to security questions page to complete registration
                header("Location: security-questions.php");
                exit; 
            }
        } catch (PDOException $e) {
            // Handle database errors 
            header("Location: index.php?message=Database error: " . $e->getMessage() . "&status=error");
            exit; 
        }
    } else {
        // There were validation errors - redirect back with error messages
        $errorMessage = urlencode(implode(", ", $errors)); // Encode for URL safety
        header("Location: index.php?message=$errorMessage&status=error");
        exit; 
    }
} else {
    // If someone tries to access this page directly (not via form submission)
    // Redirect them to the registration form
    header("Location: index.php");
    exit; 
}
?>