<?php
require_once 'includes/auth.php';
$user_id = requireAuth();
require_once 'dbconnect.php';

$error = '';
$success = '';
$redirect = false;

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Validate required fields
        if (empty($username) || empty($email)) {
            $error = "Username and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if username already exists (excluding current user)
            $username_check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $username_check->execute([$username, $user_id]);
            if ($username_check->fetch()) {
                $error = "Username already exists. Please choose a different one.";
            }

            // Check if email already exists (excluding current user)
            $email_check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $email_check->execute([$email, $user_id]);
            if ($email_check->fetch()) {
                $error = "Email already exists. Please use a different email.";
            }

            if (empty($error)) {
                // If password change is requested
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = "Current password is required to set a new password.";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords do not match.";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters long.";
                    } else {
                        // Verify current password
                        $password_check = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                        $password_check->execute([$user_id]);
                        $current_hash = $password_check->fetchColumn();
                        
                        if (!password_verify($current_password, $current_hash)) {
                            $error = "Current password is incorrect.";
                        } else {
                            // Update with new password
                            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?");
                            $update_stmt->execute([$username, $email, $new_password_hash, $user_id]);
                            $success = "Account information and password updated successfully!";
                            $redirect = true; // Set redirect flag
                        }
                    }
                } else {
                    // Update without changing password
                    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                    $update_stmt->execute([$username, $email, $user_id]);
                    $success = "Account information updated successfully!";
                    
                    // Update session username if changed
                    if ($_SESSION['username'] !== $username) {
                        $_SESSION['username'] = $username;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
    
    // Refresh user data after update
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Redirect if password was changed
    if ($redirect) {
        header("Refresh: 2; url=dashboard.php"); // Redirect after 2 seconds
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Account - Movie Watchlist</title>
    <link rel="stylesheet" href="style1.css?v=<?php echo time(); ?>">
    <style>
        .edit-account-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .edit-account-title {
            text-align: center;
            color: #ffffff;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d1d5db;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #141a24;
            border: 1px solid #4b5563;
            border-radius: 10px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.15s ease;
        }

        .form-group input:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
            outline: none;
        }

        .password-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #374151;
        }

        .password-section h3 {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .btn-save {
            width: 100%;
            background: #ef4444;
            color: #ffffff;
            border: none;
            border-radius: 999px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-top: 10px;
        }

        .btn-save:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-back {
            display: inline-block;
            text-align: center;
            width: 100%;
            background: transparent;
            color: #9ca3af;
            border: 1px solid #4b5563;
            border-radius: 999px;
            padding: 12px;
            font-size: 14px;
            text-decoration: none;
            margin-top: 15px;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            border-color: #ffffff;
            color: #ffffff;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid #ef4444;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            color: #86efac;
            border: 1px solid #22c55e;
        }

        .form-note {
            color: #9ca3af;
            font-size: 13px;
            margin-top: 6px;
            font-style: italic;
        }

        .redirect-notice {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
            border: 1px solid #3b82f6;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="dashboard.php" class="nav-link">Home</a>
            <a href="your_categories.php" class="nav-link">Your Folders</a>
            <a href="watchlists.php" class="nav-link">Watchlists</a>
        </div>
        <div class="nav-right">
            <div class="profile-wrapper">
                <div class="profile-icon" onclick="toggleProfileDropdown()">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=6a1bff&color=fff" style="width:48px;height:48px;border-radius:50%;">
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <strong><?php echo $_SESSION['username']; ?></strong>
                    <a href="edit_account.php">✏️ Edit Account</a>
                    <a href="logout.php" style="color:#e50914;">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="edit-account-container">
        <h1 class="edit-account-title">Edit Account</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php if ($redirect): ?>
                <div class="redirect-notice">
                    ✅ Password updated successfully! Redirecting to dashboard in 2 seconds...
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="edit_account.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="password-section">
                <h3>Change Password (Optional)</h3>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password">
                    <div class="form-note">Required only if changing password</div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password">
                    <div class="form-note">Leave blank to keep current password</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
            <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
        </form>
    </div>

    <script>
        function toggleProfileDropdown() {
            document.getElementById("profileDropdown").classList.toggle("show");
        }

        document.addEventListener("click", (e) => {
            const dropdown = document.getElementById("profileDropdown");
            const wrapper = document.querySelector(".profile-wrapper");
            if (!wrapper.contains(e.target)) {
                dropdown.classList.remove("show");
            }
        });

        // Real-time password matching validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        function validatePasswords() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#ef4444';
                } else {
                    confirmPassword.style.borderColor = '#22c55e';
                }
            } else {
                confirmPassword.style.borderColor = '#4b5563';
            }
        }

        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }

        // Auto-redirect if password was changed
        <?php if ($redirect): ?>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>