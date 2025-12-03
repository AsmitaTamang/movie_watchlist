<?php
// Require authentication — ensures the user is logged in before accessing this page.
// requireAuth() returns the current user's ID.
require_once 'includes/auth.php';
$user_id = requireAuth();

// Connect to database
require_once 'dbconnect.php';

// Variables to store status messages for UI
$error = '';
$success = '';
$redirect = false; // Used when password is successfully changed (auto-redirect)

// -------------------------------------------------------
// Fetch current user data to pre-fill the form
// -------------------------------------------------------
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If somehow user does not exist (rare case)
if (!$user) {
    die("User not found.");
}

// -------------------------------------------------------
// Handle form submission (POST request)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read submitted form inputs safely (trim = remove spaces)
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // ---------------------------------------------
        // Validate required fields
        // ---------------------------------------------
        if (empty($username) || empty($email)) {
            $error = "Username and email are required.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Validate email format
            $error = "Please enter a valid email address.";

        } else {

            // ---------------------------------------------------
            // Check if the new username already exists (except current user)
            // ---------------------------------------------------
            $username_check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $username_check->execute([$username, $user_id]);

            if ($username_check->fetch()) {
                $error = "Username already exists. Please choose a different one.";
            }

            // ---------------------------------------------------
            // Check if the new email already exists (except current user)
            // ---------------------------------------------------
            $email_check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $email_check->execute([$email, $user_id]);

            if ($email_check->fetch()) {
                $error = "Email already exists. Please use a different email.";
            }

            // Proceed only if no errors so far
            if (empty($error)) {

                // ---------------------------------------------------
                // OPTIONAL: User wants to change password
                // ---------------------------------------------------
                if (!empty($new_password)) {

                    // Must provide current password
                    if (empty($current_password)) {
                        $error = "Current password is required to set a new password.";

                    // New password & confirm must match
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";

                    // Basic password length validation
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long.";

                    } else {
                        // ---------------------------------------------------
                        // Verify the CURRENT password before updating
                        // ---------------------------------------------------
                        $password_check = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                        $password_check->execute([$user_id]);
                        $current_hash = $password_check->fetchColumn();

                        // If old password is wrong → error
                        if (!password_verify($current_password, $current_hash)) {
                            $error = "Current password is incorrect.";
                        } else {
                            // ---------------------------------------------------
                            // Update account info + new password
                            // ---------------------------------------------------
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                            $update_stmt = $pdo->prepare(
                                "UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?"
                            );
                            $update_stmt->execute([$username, $email, $new_password_hash, $user_id]);

                            $success = "Account information and password updated successfully!";
                            $redirect = true; // Trigger auto-redirect to dashboard
                        }
                    }

                } else {
                    // ---------------------------------------------------
                    // Update username/email WITHOUT password change
                    // ---------------------------------------------------
                    $update_stmt = $pdo->prepare(
                        "UPDATE users SET username = ?, email = ? WHERE user_id = ?"
                    );
                    $update_stmt->execute([$username, $email, $user_id]);

                    $success = "Account information updated successfully!";

                    // Update session username so navbar shows the updated one
                    if ($_SESSION['username'] !== $username) {
                        $_SESSION['username'] = $username;
                    }
                }
            }
        }

    } catch (PDOException $e) {
        // Catch unexpected database issues
        $error = "Database error: " . $e->getMessage();
    }

    // -------------------------------------------------------
    // Reload fresh user info from database after saving updates
    // -------------------------------------------------------
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If password changed → redirect after 2 seconds
    if ($redirect) {
        header("Refresh: 2; url=dashboard.php");
    }
}
?>
