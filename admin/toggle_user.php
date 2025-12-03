<?php
// Include database connection and start session
require_once '../dbconnect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=not_logged_in");
    exit;
}

// Check if user has admin privileges
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=admin_access_required");
    exit;
}


// Get user ID from URL parameter
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: admin_users.php");
    exit;
}

// Prevent self-deactivation for security
if ($user_id == $_SESSION['user_id']) {
    header("Location: admin_users.php?error=cannot_modify_self");
    exit;
}

try {
    // Toggle user status (active/inactive) using NOT operator(logical operator that reverses or inverts a boolean value)
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    header("Location: admin_users.php?success=toggled");
    exit;
} catch (PDOException $e) {
    header("Location: admin_users.php?error=database");
    exit;
}