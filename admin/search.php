<?php
// Advanced search functionality for user management

/**
 * Performs advanced user search with multiple filter options
 * @param PDO $pdo Database connection object
 * @param array $filters Search criteria (username, email, date ranges, etc.)
 * @return array Filtered user results
 */
function advancedUserSearch($pdo, $filters) {
    $where_conditions = [];
    $params = [];

    // Username search - partial match
    if (!empty($filters['username'])) {
        $where_conditions[] = "username LIKE ?";
        $params[] = "%{$filters['username']}%";
    }

    // Email search - partial match
    if (!empty($filters['email'])) {
        $where_conditions[] = "email LIKE ?";
        $params[] = "%{$filters['email']}%";
    }

    // Date range search - account creation from date
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "created_at >= ?";
        $params[] = $filters['date_from'];
    }
    
    // Date range search - account creation to date
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59'; // Include entire end date
    }

    // Last login range - users who logged in after specified date
    if (!empty($filters['last_login_from'])) {
        $where_conditions[] = "last_login >= ?";
        $params[] = $filters['last_login_from'];
    }

    // Build WHERE clause from conditions
    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = "WHERE " . implode(" AND ", $where_conditions);
    }

    // Execute search query
    $sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>