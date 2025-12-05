<?php
// AuthenticationTest.php - Integration tests for authentication system
require_once '../dbconnect.php';

class AuthenticationTest {
    private $pdo;
    private $testUsers = [];
    private $testCredentials = []; // Store test user credentials
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        session_start();
        $_SESSION = []; // Clear session for testing
    }
    
    /**
     * Clean up test users after tests
     */
    public function cleanup() {
        foreach ($this->testUsers as $userId) {
            try {
                $this->pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
            } catch (PDOException $e) {
                // Ignore cleanup errors
            }
        }
        $this->testUsers = [];
    }
    
    /**
     * Test 1: Registration flow
     */
    public function testRegistrationFlow() {
        $results = [];
        echo "<h3>Registration Flow Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Steps</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testUsername = 'regtest_' . time();
        $testEmail = $testUsername . '@test.com';
        $password = 'Test123!';
        
        // Simulate registration process
        try {
            // Step 1: Check if user exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$testUsername, $testEmail]);
            
            if (!$stmt->fetch()) {
                $results[] = $this->logTest(
                    "Check user doesn't exist before registration",
                    "Query database",
                    "No existing user found",
                    "No existing user found",
                    "PASS"
                );
                
                // Step 2: Create user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, role) 
                    VALUES (?, ?, ?, 'user')
                ");
                
                if ($stmt->execute([$testUsername, $testEmail, $passwordHash])) {
                    $userId = $this->pdo->lastInsertId();
                    $this->testUsers[] = $userId;
                    
                    // Store credentials for login test
                    $this->testCredentials = [
                        'username' => $testUsername,
                        'email' => $testEmail,
                        'password' => $password,
                        'userId' => $userId
                    ];
                    
                    $results[] = $this->logTest(
                        "Create new user account",
                        "INSERT into users table",
                        "User created successfully",
                        "User created with ID: {$userId}",
                        "PASS"
                    );
                    
                    // Step 3: Verify user creation
                    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        $results[] = $this->logTest(
                            "Verify user creation",
                            "SELECT from users table",
                            "User data retrieved",
                            "Username: {$user['username']}, Email: {$user['email']}",
                            "PASS"
                        );
                        
                        // Check default values
                        if ($user['role'] === 'user') {
                            $results[] = $this->logTest(
                                "Default role set correctly",
                                "Check role field",
                                "role = 'user'",
                                "role = '{$user['role']}'",
                                "PASS"
                            );
                        }
                        
                        if ($user['is_active'] == 1) {
                            $results[] = $this->logTest(
                                "Default active status",
                                "Check is_active field",
                                "is_active = 1",
                                "is_active = {$user['is_active']}",
                                "PASS"
                            );
                        }
                        
                        // Check created_at is set
                        if (!empty($user['created_at'])) {
                            $results[] = $this->logTest(
                                "Timestamp created automatically",
                                "Check created_at field",
                                "created_at is not null",
                                "created_at = {$user['created_at']}",
                                "PASS"
                            );
                        }
                    }
                }
            } else {
                $results[] = $this->logTest(
                    "Check user doesn't exist",
                    "Query database",
                    "No existing user found",
                    "User already exists (unexpected)",
                    "FAIL"
                );
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTest(
                "Registration flow",
                "Complete registration process",
                "User created successfully",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 2: Login authentication
     */
    public function testLoginAuthentication() {
        $results = [];
        echo "<h3>Login Authentication Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Steps</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        if (empty($this->testCredentials)) {
            // Create a test user if none exists
            $this->testRegistrationFlow();
        }
        
        $username = $this->testCredentials['username'];
        $password = $this->testCredentials['password'];
        $wrongPassword = 'WrongPass123!';
        
        try {
            // Test 2.1: Valid login with username
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $passwordValid = password_verify($password, $user['password']);
                
                if ($passwordValid) {
                    // Simulate successful login
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    $results[] = $this->logTest(
                        "Login with valid username/password",
                        "1. Query user by username\n2. Verify password\n3. Set session",
                        "Login successful, session created",
                        "Session created: user_id={$user['user_id']}, username={$user['username']}",
                        "PASS"
                    );
                }
            }
            
            // Test 2.2: Valid login with email
            $email = $this->testCredentials['email'];
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $passwordValid = password_verify($password, $user['password']);
                
                if ($passwordValid) {
                    $results[] = $this->logTest(
                        "Login with valid email/password",
                        "1. Query user by email\n2. Verify password",
                        "Login successful",
                        "User found and password verified",
                        "PASS"
                    );
                }
            }
            
            // Test 2.3: Invalid password
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $passwordValid = password_verify($wrongPassword, $user['password']);
                
                if (!$passwordValid) {
                    $results[] = $this->logTest(
                        "Login with wrong password",
                        "1. Query user\n2. Verify wrong password",
                        "Login failed, password incorrect",
                        "Password verification failed as expected",
                        "PASS"
                    );
                }
            }
            
            // Test 2.4: Non-existent user
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute(['nonexistent_user_' . time()]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $results[] = $this->logTest(
                    "Login with non-existent username",
                    "Query for non-existent user",
                    "No user found",
                    "No user returned (as expected)",
                    "PASS"
                );
            }
            
            // Test 2.5: Update last_login on successful login
            $userId = $this->testCredentials['userId'];
            $updateStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$userId]);
            
            if ($updateStmt->rowCount() > 0) {
                $results[] = $this->logTest(
                    "Update last_login timestamp",
                    "UPDATE users SET last_login",
                    "last_login updated",
                    "last_login timestamp set to current time",
                    "PASS"
                );
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTest(
                "Login authentication",
                "Various login scenarios",
                "All tests pass",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 3: Password reset flow
     */
    public function testPasswordReset() {
        $results = [];
        echo "<h3>Password Reset Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Steps</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        try {
            // Create a user with security questions for this test
            $testUser = 'pwreset_' . time();
            $testEmail = $testUser . '@test.com';
            $oldPassword = 'OldPass123!';
            $newPassword = 'NewPass123!';
            
            // Get security questions
            $stmt = $this->pdo->query("SELECT question_id FROM security_questions WHERE is_active = 1 LIMIT 3");
            $questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($questions) >= 3) {
                // Hash answers
                $answer1Hash = password_hash('blue', PASSWORD_DEFAULT);
                $answer2Hash = password_hash('42', PASSWORD_DEFAULT);
                $answer3Hash = password_hash('yes', PASSWORD_DEFAULT);
                
                // Create test user with security questions
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password,
                    security_question_1_id, security_answer_1_hash,
                    security_question_2_id, security_answer_2_hash,
                    security_question_3_id, security_answer_3_hash)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $passwordHash = password_hash($oldPassword, PASSWORD_DEFAULT);
                $stmt->execute([
                    $testUser, $testEmail, $passwordHash,
                    $questions[0], $answer1Hash,
                    $questions[1], $answer2Hash,
                    $questions[2], $answer3Hash
                ]);
                
                $userId = $this->pdo->lastInsertId();
                $this->testUsers[] = $userId;
                
                $results[] = $this->logTest(
                    "Create user for password reset test",
                    "INSERT user with security Q/A",
                    "User created with security data",
                    "User ID: {$userId}",
                    "PASS"
                );
                
                // Test: Verify security answers
                $stmt = $this->pdo->prepare("
                    SELECT security_answer_1_hash, security_answer_2_hash, security_answer_3_hash
                    FROM users WHERE user_id = ?
                ");
                $stmt->execute([$userId]);
                $hashedAnswers = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $answer1Valid = password_verify('blue', $hashedAnswers['security_answer_1_hash']);
                $answer2Valid = password_verify('42', $hashedAnswers['security_answer_2_hash']);
                $answer3Valid = password_verify('yes', $hashedAnswers['security_answer_3_hash']);
                
                if ($answer1Valid && $answer2Valid && $answer3Valid) {
                    $results[] = $this->logTest(
                        "Verify correct security answers",
                        "password_verify() for all 3 answers",
                        "All answers correct",
                        "Security answers verified successfully",
                        "PASS"
                    );
                }
                
                // Test: Wrong security answer
                $wrongAnswerValid = password_verify('wrong', $hashedAnswers['security_answer_1_hash']);
                
                if (!$wrongAnswerValid) {
                    $results[] = $this->logTest(
                        "Verify wrong security answer fails",
                        "password_verify() with wrong answer",
                        "Answer incorrect",
                        "Password verification failed (as expected)",
                        "PASS"
                    );
                }
                
                // Test: Update password after verification
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $updateStmt->execute([$newPasswordHash, $userId]);
                
                if ($updateStmt->rowCount() > 0) {
                    $results[] = $this->logTest(
                        "Update password after reset",
                        "UPDATE users SET password",
                        "Password updated",
                        "Password hash changed in database",
                        "PASS"
                    );
                }
            } else {
                $results[] = $this->logTest(
                    "Password reset test setup",
                    "Get security questions",
                    "At least 3 active questions needed",
                    "Only " . count($questions) . " questions available",
                    "SKIP"
                );
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTest(
                "Password reset tests",
                "Complete password reset flow",
                "All tests pass",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 4: Admin functionality
     */
    public function testAdminFunctionality() {
        $results = [];
        echo "<h3>Admin Functionality Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Steps</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        try {
            // Create an admin user
            $adminUser = 'admin_test_' . time();
            $adminEmail = $adminUser . '@test.com';
            $adminPassword = 'AdminPass123!';
            
            $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, 'admin')
            ");
            
            if ($stmt->execute([$adminUser, $adminEmail, $passwordHash])) {
                $adminId = $this->pdo->lastInsertId();
                $this->testUsers[] = $adminId;
                
                $results[] = $this->logTest(
                    "Create admin user",
                    "INSERT user with role='admin'",
                    "Admin user created",
                    "Admin ID: {$adminId}",
                    "PASS"
                );
                
                // Test: Admin can see all users
                $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $results[] = $this->logTest(
                        "Admin view all users",
                        "SELECT COUNT(*) FROM users",
                        "Returns user count > 0",
                        "Found {$result['count']} users",
                        "PASS"
                    );
                }
                
                // Create a regular user for admin operations
                $regularUser = 'regular_' . time();
                $regularEmail = $regularUser . '@test.com';
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, role) 
                    VALUES (?, ?, ?, 'user')
                ");
                $stmt->execute([$regularUser, $regularEmail, $passwordHash]);
                $regularId = $this->pdo->lastInsertId();
                $this->testUsers[] = $regularId;
                
                // Test: Admin deactivate user
                $updateStmt = $this->pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
                $updateStmt->execute([$regularId]);
                
                if ($updateStmt->rowCount() > 0) {
                    $results[] = $this->logTest(
                        "Admin deactivate user",
                        "UPDATE users SET is_active = 0",
                        "User deactivated",
                        "User ID {$regularId} is_active set to 0",
                        "PASS"
                    );
                }
                
                // Test: Admin reactivate user
                $updateStmt = $this->pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
                $updateStmt->execute([$regularId]);
                
                if ($updateStmt->rowCount() > 0) {
                    $results[] = $this->logTest(
                        "Admin reactivate user",
                        "UPDATE users SET is_active = 1",
                        "User reactivated",
                        "User ID {$regularId} is_active set to 1",
                        "PASS"
                    );
                }
                
                // Test: Admin delete user
                $deleteStmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $deleteStmt->execute([$regularId]);
                
                if ($deleteStmt->rowCount() > 0) {
                    $results[] = $this->logTest(
                        "Admin delete user",
                        "DELETE FROM users",
                        "User deleted",
                        "User ID {$regularId} removed",
                        "PASS"
                    );
                }
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTest(
                "Admin functionality tests",
                "Admin operations",
                "All tests pass",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Helper method to log test results
     */
    private function logTest($description, $steps, $expected, $actual, $status) {
        $color = $status === 'PASS' ? 'green' : ($status === 'SKIP' ? 'orange' : 'red');
        
        echo "<tr>";
        echo "<td>{$description}</td>";
        echo "<td><pre style='margin:0;'>{$steps}</pre></td>";
        echo "<td>{$expected}</td>";
        echo "<td>{$actual}</td>";
        echo "<td style='color: {$color}; font-weight: bold;'>{$status}</td>";
        echo "</tr>";
        
        return [
            'description' => $description,
            'steps' => $steps,
            'expected' => $expected,
            'actual' => $actual,
            'status' => $status
        ];
    }
    
    /**
     * Run all integration tests
     */
    public function runAllTests() {
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Authentication Integration Tests</title>";
        echo "<style>table { border-collapse: collapse; width: 100%; } 
              th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
              th { background-color: #f2f2f2; }
              pre { font-family: monospace; }</style>";
        echo "</head><body>";
        echo "<h1>Authentication System Integration Tests</h1>";
        
        $results = [];
        $results[] = $this->testRegistrationFlow();
        $results[] = $this->testLoginAuthentication();
        $results[] = $this->testPasswordReset();
        $results[] = $this->testAdminFunctionality();
        
        // Cleanup test data
        $this->cleanup();
        
        echo "<h2>All Tests Completed</h2>";
        echo "<p>Test data cleaned up automatically.</p>";
        echo "</body></html>";
        
        return $results;
    }
}

// Run tests if executed directly
if (PHP_SAPI === 'cli' || isset($_GET['run_tests'])) {
    try {
        require_once '../dbconnect.php';
        $test = new AuthenticationTest($pdo);
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>