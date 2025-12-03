<?php
// Include database connection file for database operations
require_once '../dbconnect.php';
// Start session to access user session data
session_start();

// AUTHORIZATION CHECK - Only admins should access this page
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php?error=not_logged_in");
    exit; 
}

// Check if user has admin role
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page if not admin
    header("Location: ../login.php?error=admin_access_required");
    exit; 
}

// INITIALIZE SEARCH AND FILTER PARAMETERS from URL
// Get search term from URL or set empty string
$search = $_GET['search'] ?? '';
// Get status filter from URL or set empty string
$status_filter = $_GET['status'] ?? '';
// Get sort option from URL or set default to newest first
$sort_by = $_GET['sort'] ?? 'created_at_desc';

// BUILD QUERY WITH FILTERS
// Array to store WHERE conditions for SQL query
$where_conditions = [];
// Array to store parameters for prepared statement
$params = [];

// SEARCH FUNCTIONALITY - Search in username or email
if (!empty($search)) {
    // Add condition to search in username or email fields
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    // Prepare search term with wildcards for partial matching
    $search_term = "%$search%";
    // Add search term to parameters array twice (for username and email)
    $params[] = $search_term;
    $params[] = $search_term;
}

// STATUS FILTER - Active/Inactive users
if ($status_filter === 'active') {
    // Filter for active users only
    $where_conditions[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    // Filter for inactive users only
    $where_conditions[] = "is_active = 0";
}

// BUILD WHERE CLAUSE for SQL query
$where_sql = '';
// If there are any conditions, combine them with AND
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// SORTING OPTIONS - Different ways to sort the user list
$order_by = 'created_at DESC'; // Default sort order
// Determine order by based on sort parameter
switch ($sort_by) {
    case 'username_asc':
        $order_by = 'username ASC';
        break;
    case 'username_desc':
        $order_by = 'username DESC';
        break;
    case 'email_asc':
        $order_by = 'email ASC';
        break;
    case 'email_desc':
        $order_by = 'email DESC';
        break;
    case 'created_at_asc':
        $order_by = 'created_at ASC';
        break;
    case 'created_at_desc':
        $order_by = 'created_at DESC';
        break;
    case 'last_login_asc':
        $order_by = 'last_login ASC';
        break;
    case 'last_login_desc':
        $order_by = 'last_login DESC';
        break;
}

try {
    // GET TOTAL COUNT for pagination info
    // Count total users matching filters
    $count_sql = "SELECT COUNT(*) FROM users $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_users = $count_stmt->fetchColumn();

    // GET FILTERED USERS with applied search/sort
    // Select user data with applied filters and sorting
    $sql = "SELECT user_id, username, email, is_active, created_at, last_login 
            FROM users 
            $where_sql 
            ORDER BY $order_by";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <!-- MAIN ADMIN CONTAINER -->
    <div class="admin-container">
        
        <!-- PAGE HEADER with logo and navigation -->
        <div class="admin-header">
            <div class="admin-header-content">
                <!-- Company logo for branding -->
                <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="admin-logo">
                <div class="admin-title">
                    <h1>User Management</h1>
                    <p>Admin Control Panel</p>
                </div>
            </div>
            <div class="admin-welcome">
                <!-- Display welcome message with username -->
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <div class="admin-actions">
                    <!-- Navigation buttons -->
                    <a href="admin_dashboard.php" class="admin-btn">← Dashboard</a>
                    <a href="../logout.php" class="admin-btn logout-btn">Logout</a>
                </div>
            </div>
        </div>

        <!-- SUCCESS MESSAGES - Show when users are updated/deleted -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-msg">
                <?php 
                // Display appropriate success message based on success parameter
                switch($_GET['success']) {
                    case 'updated': echo "✅ User updated successfully!"; break;
                    case 'toggled': echo "✅ User status updated successfully!"; break;
                    case 'deleted': echo "✅ User permanently deleted successfully!"; break;
                    default: echo "✅ Operation completed successfully!";
                }
                ?>
            </div>
        <?php endif; ?>
        
        <!-- ERROR MESSAGES - Show database or operation errors -->
        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- SEARCH AND FILTER SECTION -->
        <div class="search-filter-container">
            <!-- Form for search and filter options -->
            <form method="GET" class="search-filter-form">
                <div class="form-row">
                    <!-- SEARCH INPUT -->
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search username or email..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input">
                    </div>
                    
                    <!-- STATUS FILTER DROPDOWN -->
                    <div class="form-group">
                        <select name="status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <!-- SORTING DROPDOWN -->
                    <div class="form-group">
                        <select name="sort" class="sort-select">
                            <option value="created_at_desc" <?php echo $sort_by === 'created_at_desc' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="created_at_asc" <?php echo $sort_by === 'created_at_asc' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="username_asc" <?php echo $sort_by === 'username_asc' ? 'selected' : ''; ?>>Username A-Z</option>
                            <option value="username_desc" <?php echo $sort_by === 'username_desc' ? 'selected' : ''; ?>>Username Z-A</option>
                            <option value="email_asc" <?php echo $sort_by === 'email_asc' ? 'selected' : ''; ?>>Email A-Z</option>
                            <option value="email_desc" <?php echo $sort_by === 'email_desc' ? 'selected' : ''; ?>>Email Z-A</option>
                        </select>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="form-group">
                        <button type="submit" class="admin-btn">Apply Filters</button>
                        <!-- Reset button to clear all filters -->
                        <a href="admin_users.php" class="admin-btn" style="background: #555;">Reset</a>
                    </div>
                </div>
            </form>
            
            <!-- RESULTS SUMMARY - Shows how many users match filters -->
            <div class="results-summary">
                <p>
                    Showing <strong><?php echo count($users); ?></strong> of <strong><?php echo $total_users; ?></strong> users
                    <!-- Show search term if search was performed -->
                    <?php if (!empty($search)): ?>
                        matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- USERS TABLE - Main content area -->
        <div class="table-container">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- NO RESULTS MESSAGE -->
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="no-results">
                                No users found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <!-- LOOP THROUGH EACH USER AND DISPLAY IN TABLE -->
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <!-- USER ID -->
                            <td><?php echo $user['user_id']; ?></td>
                            
                            <!-- USERNAME -->
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </td>
                            
                            <!-- EMAIL -->
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            
                            <!-- STATUS BADGE - Active/Inactive -->
                            <td>
                                <span class="status <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            
                            <!-- CREATION DATE -->
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            
                            <!-- LAST LOGIN DATE -->
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <!-- Display last login date with full timestamp in title -->
                                    <span title="<?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>">
                                        <?php echo date('M j, Y', strtotime($user['last_login'])); ?>
                                    </span>
                                <?php else: ?>
                                    <!-- Show "Never" if user never logged in -->
                                    <span class="never-logged">Never</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- ACTION BUTTONS -->
                            <td class="actions">
                                <!-- EDIT BUTTON -->
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                   class="admin-btn small" title="Edit User">
                                   ✏️ Edit
                                </a>
                                
                                <!-- PREVENT SELF-MODIFICATION - Can't edit/deactivate yourself -->
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <!-- ACTIVATE/DEACTIVATE TOGGLE -->
                                    <a href="toggle_user.php?id=<?php echo $user['user_id']; ?>" 
                                       class="admin-btn small <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>"
                                       onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')"
                                       title="<?php echo $user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>">
                                        <?php echo $user['is_active'] ? '❌ Deactivate' : '✅ Activate'; ?>
                                    </a>
                                    
                                    <!-- DELETE BUTTON - Permanent deletion -->
                                    <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                      class="admin-btn small delete"
                                      onclick="return confirm('⚠️ WARNING: This will permanently delete the user and all their data. This action cannot be undone! Are you absolutely sure?')"
                                       title="Permanently Delete User">
                                      🗑️ Delete
                                    </a>
                                <?php else: ?>
                                    <!-- CURRENT USER INDICATOR -->
                                    <span class="current-user">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- BACK TO DASHBOARD BUTTON -->
        <div class="action-buttons" style="text-align: center; margin-top: 20px;">
            <a href="admin_dashboard.php" class="admin-btn">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>