<?php
// DatabaseTest.php - Database CRUD operations tests
require_once '../dbconnect.php';

class DatabaseTest {
    private $pdo;
    private $testUserId = null;
    private $testUsername = 'testuser_' . time();
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Test 1: Create User (C in CRUD)
     */
    public function testCreateUser() {
        $results = [];
        echo "<h3>Database CREATE Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        try {
            // Test valid user creation
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $passwordHash = password_hash('Test123!', PASSWORD_DEFAULT);
            $success = $stmt->execute([
                $this->testUsername,
                $this->testUsername . '@test.com',
                $passwordHash,
                'user',
                1
            ]);
            
            if ($success) {
                $this->testUserId = $this->pdo->lastInsertId();
                $results[] = $this->logTestResult(
                    "Create valid user",
                    "User created successfully",
                    "User created with ID: {$this->testUserId}",
                    "PASS"
                );
            }
            
            // Test duplicate username
            try {
                $stmt->execute([
                    $this->testUsername,
                    'different@test.com',
                    $passwordHash,
                    'user',
                    1
                ]);
                $results[] = $this->logTestResult(
                    "Duplicate username prevention",
                    "Should fail with duplicate error",
                    "Unexpectedly succeeded",
                    "FAIL"
                );
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $results[] = $this->logTestResult(
                        "Duplicate username prevention",
                        "Should fail with duplicate error",
                        "Failed as expected: " . $e->getMessage(),
                        "PASS"
                    );
                }
            }
            
            // Test duplicate email
            try {
                $stmt->execute([
                    'differentuser',
                    $this->testUsername . '@test.com',
                    $passwordHash,
                    'user',
                    1
                ]);
                $results[] = $this->logTestResult(
                    "Duplicate email prevention",
                    "Should fail with duplicate error",
                    "Unexpectedly succeeded",
                    "FAIL"
                );
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $results[] = $this->logTestResult(
                        "Duplicate email prevention",
                        "Should fail with duplicate error",
                        "Failed as expected: " . $e->getMessage(),
                        "PASS"
                    );
                }
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTestResult(
                "Create user basic",
                "User created successfully",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 2: Read User (R in CRUD)
     */
    public function testReadUser() {
        $results = [];
        echo "<h3>Database READ Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        // Test read by ID
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $results[] = $this->logTestResult(
                "Read user by ID",
                "User data retrieved",
                "Found: " . $user['username'],
                "PASS"
            );
        } else {
            $results[] = $this->logTestResult(
                "Read user by ID",
                "User data retrieved",
                "User not found",
                "FAIL"
            );
        }
        
        // Test read by username
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$this->testUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $results[] = $this->logTestResult(
                "Read user by username",
                "User data retrieved",
                "Found: " . $user['email'],
                "PASS"
            );
        }
        
        // Test non-existent user
        $stmt->execute(['nonexistentuser']);
        $user = $stmt->fetch();
        
        if (!$user) {
            $results[] = $this->logTestResult(
                "Read non-existent user",
                "No user returned",
                "Correctly returned no results",
                "PASS"
            );
        }
        
        // Test get all users
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $results[] = $this->logTestResult(
                "Get all users count",
                "Count > 0",
                "Found " . $result['count'] . " users",
                "PASS"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 3: Update User (U in CRUD)
     */
    public function testUpdateUser() {
        $results = [];
        echo "<h3>Database UPDATE Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        // Test update username
        $newUsername = $this->testUsername . '_updated';
        $stmt = $this->pdo->prepare("UPDATE users SET username = ? WHERE user_id = ?");
        $stmt->execute([$newUsername, $this->testUserId]);
        
        if ($stmt->rowCount() > 0) {
            $results[] = $this->logTestResult(
                "Update username",
                "Username updated",
                "Updated to: {$newUsername}",
                "PASS"
            );
            $this->testUsername = $newUsername;
        }
        
        // Test update email
        $newEmail = 'updated_' . $this->testUsername . '@test.com';
        $stmt = $this->pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
        $stmt->execute([$newEmail, $this->testUserId]);
        
        if ($stmt->rowCount() > 0) {
            $results[] = $this->logTestResult(
                "Update email",
                "Email updated",
                "Updated to: {$newEmail}",
                "PASS"
            );
        }
        
        // Test update role to admin
        $stmt = $this->pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        
        if ($stmt->rowCount() > 0) {
            $results[] = $this->logTestResult(
                "Update role to admin",
                "Role updated",
                "Role set to admin",
                "PASS"
            );
        }
        
        // Test update is_active
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        
        if ($stmt->rowCount() > 0) {
            $results[] = $this->logTestResult(
                "Deactivate user",
                "is_active set to 0",
                "User deactivated",
                "PASS"
            );
        }
        
        // Reactivate for other tests
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 4: Delete User (D in CRUD)
     */
    public function testDeleteUser() {
        $results = [];
        echo "<h3>Database DELETE Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        // Test delete user
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        
        if ($stmt->rowCount() > 0) {
            $results[] = $this->logTestResult(
                "Delete user",
                "User deleted",
                "User deleted successfully",
                "PASS"
            );
        } else {
            $results[] = $this->logTestResult(
                "Delete user",
                "User deleted",
                "No user deleted",
                "FAIL"
            );
        }
        
        // Verify deletion
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$this->testUserId]);
        
        if (!$stmt->fetch()) {
            $results[] = $this->logTestResult(
                "Verify user deletion",
                "User not found after deletion",
                "User correctly removed",
                "PASS"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 5: Security Questions functionality
     */
    public function testSecurityQuestions() {
        $results = [];
        echo "<h3>Security Questions Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        try {
            // Create a user with security questions
            $testUser = 'secuser_' . time();
            $passwordHash = password_hash('Test123!', PASSWORD_DEFAULT);
            $answer1Hash = password_hash('blue', PASSWORD_DEFAULT);
            $answer2Hash = password_hash('42', PASSWORD_DEFAULT);
            $answer3Hash = password_hash('yes', PASSWORD_DEFAULT);
            
            // First, get some security question IDs
            $stmt = $this->pdo->query("SELECT question_id FROM security_questions WHERE is_active = 1 LIMIT 3");
            $questions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($questions) >= 3) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, 
                    security_question_1_id, security_answer_1_hash,
                    security_question_2_id, security_answer_2_hash,
                    security_question_3_id, security_answer_3_hash)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $success = $stmt->execute([
                    $testUser,
                    $testUser . '@test.com',
                    $passwordHash,
                    $questions[0], $answer1Hash,
                    $questions[1], $answer2Hash,
                    $questions[2], $answer3Hash
                ]);
                
                if ($success) {
                    $secUserId = $this->pdo->lastInsertId();
                    $results[] = $this->logTestResult(
                        "Create user with security questions",
                        "User created with security Q/A",
                        "Created with ID: {$secUserId}",
                        "PASS"
                    );
                    
                    // Test retrieving security questions
                    $stmt = $this->pdo->prepare("
                        SELECT u.*, 
                        q1.question_text as q1_text, q2.question_text as q2_text, q3.question_text as q3_text
                        FROM users u
                        LEFT JOIN security_questions q1 ON u.security_question_1_id = q1.question_id
                        LEFT JOIN security_questions q2 ON u.security_question_2_id = q2.question_id
                        LEFT JOIN security_questions q3 ON u.security_question_3_id = q3.question_id
                        WHERE u.user_id = ?
                    ");
                    $stmt->execute([$secUserId]);
                    $userWithQuestions = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($userWithQuestions && $userWithQuestions['q1_text']) {
                        $results[] = $this->logTestResult(
                            "Retrieve user with security questions",
                            "Questions retrieved",
                            "Found questions for user",
                            "PASS"
                        );
                    }
                    
                    // Clean up
                    $this->pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$secUserId]);
                }
            }
            
        } catch (PDOException $e) {
            $results[] = $this->logTestResult(
                "Security questions test",
                "Success",
                "Error: " . $e->getMessage(),
                "FAIL"
            );
        }
        
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 6: Database Constraints
     */
    public function testDatabaseConstraints() {
        $results = [];
        echo "<h3>Database Constraints Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        // Test NOT NULL constraint on username
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->execute(['test@test.com', password_hash('test', PASSWORD_DEFAULT)]);
            $results[] = $this->logTestResult(
                "NOT NULL username constraint",
                "Should fail",
                "Unexpectedly succeeded",
                "FAIL"
            );
        } catch (PDOException $e) {
            $results[] = $this->logTestResult(
                "NOT NULL username constraint",
                "Should fail",
                "Failed as expected",
                "PASS"
            );
        }
        
        // Test NOT NULL constraint on email
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute(['testuser', password_hash('test', PASSWORD_DEFAULT)]);
            $results[] = $this->logTestResult(
                "NOT NULL email constraint",
                "Should fail",
                "Unexpectedly succeeded",
                "FAIL"
            );
        } catch (PDOException $e) {
            $results[] = $this->logTestResult(
                "NOT NULL email constraint",
                "Should fail",
                "Failed as expected",
                "PASS"
            );
        }
        
        // Test UNIQUE constraint on username
        try {
            // Get existing username
            $stmt = $this->pdo->query("SELECT username FROM users LIMIT 1");
            $existingUser = $stmt->fetch(PDO::FETCH_COLUMN);
            
            if ($existingUser) {
                $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([
                    $existingUser,
                    'different@test.com',
                    password_hash('test', PASSWORD_DEFAULT)
                ]);
                $results[] = $this->logTestResult(
                    "UNIQUE username constraint",
                    "Should fail",
                    "Unexpectedly succeeded",
                    "FAIL"
                );
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $results[] = $this->logTestResult(
                    "UNIQUE username constraint",
                    "Should fail",
                    "Failed as expected",
                    "PASS"
                );
            }
        }
        
        echo "</table>";
        return $results;
    }
    
    private function logTestResult($description, $expected, $actual, $status) {
        echo "<tr>";
        echo "<td>{$description}</td>";
        echo "<td>{$expected}</td>";
        echo "<td>{$actual}</td>";
        echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
        echo "</tr>";
        
        return [
            'description' => $description,
            'expected' => $expected,
            'actual' => $actual,
            'status' => $status
        ];
    }
}

// Run tests if executed directly
if (PHP_SAPI === 'cli' || isset($_GET['run_tests'])) {
    try {
        require_once '../dbconnect.php';
        $test = new DatabaseTest($pdo);
        
        echo "<!DOCTYPE html><html><head><title>Database Tests</title></head><body>";
        echo "<h1>Database Test Results</h1>";
        
        $test->testCreateUser();
        $test->testReadUser();
        $test->testUpdateUser();
        $test->testDeleteUser();
        $test->testSecurityQuestions();
        $test->testDatabaseConstraints();
        
        echo "</body></html>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>