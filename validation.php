<?php
/**
 * ✅ validation.php
 * Centralized security, sanitization, and validation helper file
 * Include this in any script that handles user input.
 */

// Sanitize text inputs (remove unwanted characters, prevent XSS)
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Validate year format (between 1900–2099)
function validate_year($year) {
    return preg_match('/^(19|20)\d{2}$/', $year);
}

// Validate uploaded image (type + size)
function validate_poster($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 3 * 1024 * 1024; // 3MB max
    return isset($file['type'], $file['size']) &&
           in_array($file['type'], $allowedTypes) &&
           $file['size'] <= $maxSize;
}

// Check duplicate movie (same title, same user)
function movie_exists($pdo, $title, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE title = ? AND user_id = ?");
    $stmt->execute([$title, $user_id]);
    return $stmt->fetchColumn() > 0;
}

// Securely move uploaded poster
function save_poster($file) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $posterName = "poster_" . uniqid() . "." . $extension;
    $targetPath = $targetDir . $posterName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    return false;
}

// Flash message helper (one-time messages)
function set_flash($message) {
    $_SESSION['flash_message'] = $message;
    header("Location: dashboard.php");
    exit;
}
?>
