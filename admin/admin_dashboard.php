<?php

require_once '../dbconnect.php';
session_start();

// Check if user is logged in by verifying session user_id exists
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with error message if not logged in
    header("Location: ../login.php?error=not_logged_in");
    exit; // Stop script execution
}

// Check if user has admin role for authorization
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page if user is not an admin
    header("Location: ../login.php?error=admin_access_required");
    exit; // Stop script execution
}

// Get statistics for dashboard display
try {
    // Total users count - count all records in users table
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Active users count - count users where is_active = 1
    $active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    
    // New users this month - count users created in last 30 days
    $new_users_month = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    
    // Recent users (last 5) - get 5 most recently created users
    $recent_users_stmt = $pdo->prepare("SELECT user_id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users_stmt->execute();
    $recent_users = $recent_users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User activity stats - get login statistics
    $login_stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_logins,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_logins,
            COUNT(CASE WHEN last_login IS NULL THEN 1 END) as never_logged
        FROM users
    ");
    $login_stats_stmt->execute();
    $login_stats = $login_stats_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors and store error message
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - User Management</title>
    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <!-- Main admin container for the dashboard -->
    <div class="admin-container">
        <!-- Admin Header section with logo and navigation -->
        <div class="admin-header">
            <div class="admin-header-content">
                <!-- Company logo for branding -->
                <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="admin-logo">
                <div class="admin-title">
                    <h1>Admin Dashboard</h1>
                    <p>Power Rangers Movie Watchlist</p>
                </div>
            </div>
            <div class="admin-welcome">
                <!-- Display welcome message with username -->
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (Admin)</p>
                <div class="admin-actions">
                    <!-- Navigation buttons for user dashboard and logout -->
                    <a href="../dashboard.php" class="admin-btn">User Dashboard</a>
                    <a href="../logout.php" class="admin-btn logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <!-- Display error message if database error occurred -->
        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics Cards section displaying key metrics -->
        <div class="stats-grid">
            <!-- Total Users card -->
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <!-- Active Users card -->
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            
            <!-- New Users This Month card -->
            <div class="stat-card">
                <div class="stat-icon">🆕</div>
                <div class="stat-info">
                    <h3><?php echo $new_users_month; ?></h3>
                    <p>New This Month</p>
                </div>
            </div>
            
            <!-- Recent Logins card -->
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?php echo $login_stats['recent_logins']; ?></h3>
                    <p>Recent Logins (7 days)</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions section with admin functionality links -->
        <div class="admin-actions">
            <h2>Admin Actions</h2>
            <div class="action-buttons-grid">
                <!-- Manage all users button -->
                <a href="admin_users.php" class="action-btn">
                    <span class="action-icon">👥</span>
                    <span class="action-text">Manage Users</span>
                </a>
                
                <!-- View active users button -->
                <a href="admin_users.php?status=active" class="action-btn">
                    <span class="action-icon">✅</span>
                    <span class="action-text">Active Users</span>
                </a>
                
                <!-- View inactive users button -->
                <a href="admin_users.php?status=inactive" class="action-btn">
                    <span class="action-icon">❌</span>
                    <span class="action-text">Inactive Users</span>
                </a>
                
                <!-- View new users button -->
                <a href="admin_users.php?sort=created_at_desc" class="action-btn">
                    <span class="action-icon">🆕</span>
                    <span class="action-text">New Users</span>
                </a>
            </div>
        </div>

        <!-- Recent Users section showing latest registrations -->
        <div class="recent-section">
            <h2>Recently Registered Users</h2>
            <!-- Check if there are any recent users -->
            <?php if (empty($recent_users)): ?>
                <!-- Display message if no users found -->
                <p class="no-data">No users found.</p>
            <?php else: ?>
                <!-- Display grid of recent user cards -->
                <div class="recent-users">
                    <!-- Loop through each recent user and display their information -->
                    <?php foreach ($recent_users as $user): ?>
                    <div class="user-card">
                        <!-- User avatar using first letter of username -->
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <!-- Username with XSS protection -->
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <!-- Email with XSS protection -->
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                            <!-- Formatted join date -->
                            <small>Joined: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                        </div>
                        <div class="user-actions">
                            <!-- Edit button for user management -->
                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="admin-btn small">Edit</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>