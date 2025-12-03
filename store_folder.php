<?php
require_once 'includes/auth.php';
$user_id=requireAuth(); 
require_once 'dbconnect.php';





// Check if form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize folder name input
    $FolderName = trim($_POST['folder_name'] ?? '');
    
    // ========== FOLDER NAME VALIDATION ==========
    
    // Check if folder name is empty
    if (empty($FolderName)) {
        $errors[] = "Folder name is required.";
    } 
    // Check folder name length
    elseif (strlen($FolderName) > 100) {
        $errors[] = "Folder name must be less than 100 characters.";
    } 
    // Check for invalid characters using regular expression
    elseif (!preg_match('/^[a-zA-Z0-9\s\-_\']+$/', $FolderName)) {
        $errors[] = "Folder name can only contain letters, numbers, spaces, hyphens, and underscores.";
    }
    
    // ========== DUPLICATE FOLDER CHECK ==========
    
    // Only check for duplicates if no validation errors so far
    if (empty($errors)) {
        try {
            // Check if folder name already exists for this user
            $checkStmt = $pdo->prepare("SELECT FolderID FROM folders WHERE user_id = ? AND FolderName = ?");
            $checkStmt->execute([$user_id, $FolderName]);
            
            // If a record is found, folder name already exists
            if ($checkStmt->fetch()) {
                $errors[] = "A folder with the name '{$FolderName}' already exists. Please choose a different name.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // ========== ERROR HANDLING ==========
    
    // If there are validation errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['folder_errors'] = $errors;
        $_SESSION['folder_form_data'] = ['folder_name' => $FolderName];
        header('Location: your_categories.php');
        exit();
    }
    
    // ========== DATABASE OPERATION ==========
    
    // If validation passes, create the folder in database

    try {
        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO folders (user_id, FolderName, CreatedAt) VALUES (?, ?, NOW())");
        
        // Execute the statement with user input
        $stmt->execute([$user_id, $FolderName]);
        
        // Set success message and redirect
        $_SESSION['success'] = "Folder '{$FolderName}' created successfully! 📁";
        header('Location: your_categories.php');
        exit();
        
    } catch (PDOException $e) {
        // Handle database errors
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: your_categories.php');
        exit();
    }
} else {
    // If someone tries to access this page directly, redirect to folders page
    header('Location: your_categories.php');
    exit();
}
?>