<?php
// UserValidationTest.php - Unit tests for user validation
require_once '../dbconnect.php';

class UserValidationTest {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Test 1: Username validation tests
     */
    public function testUsernameValidation() {
        $results = [];
        echo "<h3>Username Validation Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testCases = [
            ["", false, "Empty username"],
            ["ab", false, "Min-1 (2 chars) - Too short"],
            ["abc", true, "Min boundary (3 chars)"],
            ["user1234", true, "Mid length (8 chars)"],
            ["a".str_repeat("b", 97), true, "Max-1 (98 chars)"],
            ["a".str_repeat("b", 98), true, "Max boundary (99 chars)"],
            ["a".str_repeat("b", 99), false, "Max+1 (100 chars) - Too long"],
            ["user_name", true, "Contains underscore"],
            ["user.name", true, "Contains dot"],
            ["user-name", true, "Contains hyphen"],
            ["user name", false, "Contains space"],
            ["12345", true, "Numbers only - valid"],
            ["admin", true, "Common username"],
            ["<script>", false, "HTML tags"],
        ];
        
        foreach ($testCases as $test) {
            $result = $this->validateUsername($test[0]);
            $status = $result === $test[1] ? "PASS" : "FAIL";
            $results[] = [
                'description' => $test[2],
                'input' => htmlspecialchars($test[0]),
                'expected' => $test[1] ? 'Valid' : 'Invalid',
                'actual' => $result ? 'Valid' : 'Invalid',
                'status' => $status
            ];
            
            echo "<tr>";
            echo "<td>{$test[2]}</td>";
            echo "<td>'" . htmlspecialchars($test[0]) . "'</td>";
            echo "<td>" . ($test[1] ? 'Valid' : 'Invalid') . "</td>";
            echo "<td>" . ($result ? 'Valid' : 'Invalid') . "</td>";
            echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
            echo "</tr>";
        }
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 2: Email validation tests
     */
    public function testEmailValidation() {
        $results = [];
        echo "<h3>Email Validation Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testCases = [
            ["", false, "Empty email"],
            ["a@b.c", false, "Invalid TLD length"],
            ["a@b.co", true, "Min boundary"],
            ["user@example.com", true, "Standard email"],
            ["user.name@example.co.uk", true, "Email with dots and country"],
            ["user+tag@example.com", true, "Email with plus"],
            ["user@sub.example.com", true, "Subdomain email"],
            ["user@123.123.123.123", true, "IP address domain"],
            ["@example.com", false, "Missing local part"],
            ["user@.com", false, "Missing domain"],
            ["user@com", false, "Missing TLD"],
            ["user@example.", false, "Missing TLD after dot"],
            ["user name@example.com", false, "Space in local part"],
            ["user@exa mple.com", false, "Space in domain"],
            ["user@example.c", false, "Single char TLD"],
            ["12345", false, "Numbers only"],
        ];
        
        foreach ($testCases as $test) {
            $result = filter_var($test[0], FILTER_VALIDATE_EMAIL) !== false;
            $status = $result === $test[1] ? "PASS" : "FAIL";
            $results[] = [
                'description' => $test[2],
                'input' => htmlspecialchars($test[0]),
                'expected' => $test[1] ? 'Valid' : 'Invalid',
                'actual' => $result ? 'Valid' : 'Invalid',
                'status' => $status
            ];
            
            echo "<tr>";
            echo "<td>{$test[2]}</td>";
            echo "<td>'" . htmlspecialchars($test[0]) . "'</td>";
            echo "<td>" . ($test[1] ? 'Valid' : 'Invalid') . "</td>";
            echo "<td>" . ($result ? 'Valid' : 'Invalid') . "</td>";
            echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
            echo "</tr>";
        }
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 3: Password validation tests
     */
    public function testPasswordValidation() {
        $results = [];
        echo "<h3>Password Validation Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testCases = [
            ["", false, "Empty password"],
            ["Pass1", false, "5 chars - too short"],
            ["Pass12", true, "6 chars - min boundary"],
            ["Pass123", true, "7 chars - min+1"],
            ["Password123!", true, "Strong password"],
            ["password", false, "No uppercase/digit"],
            ["PASSWORD", false, "No lowercase/digit"],
            ["Password", false, "No digit"],
            ["12345678", false, "No letters"],
            ["Pass word", false, "Contains space"],
            [str_repeat("A", 255), true, "Max boundary"],
            [str_repeat("A", 256), false, "Max+1 - too long"],
            ["Test123!", true, "With special char"],
            ["<script>alert()</script>", false, "HTML/script tags"],
        ];
        
        foreach ($testCases as $test) {
            $result = $this->validatePassword($test[0]);
            $status = $result === $test[1] ? "PASS" : "FAIL";
            $results[] = [
                'description' => $test[2],
                'input' => htmlspecialchars($test[0]),
                'expected' => $test[1] ? 'Valid' : 'Invalid',
                'actual' => $result ? 'Valid' : 'Invalid',
                'status' => $status
            ];
            
            echo "<tr>";
            echo "<td>{$test[2]}</td>";
            echo "<td>'" . htmlspecialchars($test[0]) . "'</td>";
            echo "<td>" . ($test[1] ? 'Valid' : 'Invalid') . "</td>";
            echo "<td>" . ($result ? 'Valid' : 'Invalid') . "</td>";
            echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
            echo "</tr>";
        }
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 4: Security Answer validation
     */
    public function testSecurityAnswerValidation() {
        $results = [];
        echo "<h3>Security Answer Validation Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testCases = [
            ["", false, "Empty answer"],
            ["A", false, "Single character"],
            ["Ab", true, "Min boundary (2 chars)"],
            ["MyAnswer", true, "Normal answer"],
            ["yes", true, "Boolean answer"],
            ["123", true, "Numeric answer"],
            ["My Pet's Name", true, "With apostrophe"],
            [str_repeat("A", 254), true, "Max-1"],
            [str_repeat("A", 255), true, "Max boundary"],
            [str_repeat("A", 256), false, "Max+1"],
            ["<script>alert()</script>", false, "HTML injection attempt"],
        ];
        
        foreach ($testCases as $test) {
            $result = $this->validateSecurityAnswer($test[0]);
            $status = $result === $test[1] ? "PASS" : "FAIL";
            $results[] = [
                'description' => $test[2],
                'input' => htmlspecialchars($test[0]),
                'expected' => $test[1] ? 'Valid' : 'Invalid',
                'actual' => $result ? 'Valid' : 'Invalid',
                'status' => $status
            ];
            
            echo "<tr>";
            echo "<td>{$test[2]}</td>";
            echo "<td>'" . htmlspecialchars($test[0]) . "'</td>";
            echo "<td>" . ($test[1] ? 'Valid' : 'Invalid') . "</td>";
            echo "<td>" . ($result ? 'Valid' : 'Invalid') . "</td>";
            echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
            echo "</tr>";
        }
        echo "</table>";
        return $results;
    }
    
    /**
     * Test 5: Role validation
     */
    public function testRoleValidation() {
        $results = [];
        echo "<h3>Role Validation Tests</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Test Description</th><th>Input</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
        
        $testCases = [
            ["user", true, "Valid role: user"],
            ["admin", true, "Valid role: admin"],
            ["User", false, "Case sensitive - uppercase"],
            ["Admin", false, "Case sensitive - uppercase"],
            ["", true, "Empty defaults to 'user'"],
            ["moderator", false, "Invalid role"],
            ["0", false, "Numeric"],
            ["1", false, "Numeric"],
            ["guest", false, "Not in enum"],
        ];
        
        foreach ($testCases as $test) {
            $result = $this->validateRole($test[0]);
            $status = $result === $test[1] ? "PASS" : "FAIL";
            $results[] = [
                'description' => $test[2],
                'input' => htmlspecialchars($test[0]),
                'expected' => $test[1] ? 'Valid' : 'Invalid',
                'actual' => $result ? 'Valid' : 'Invalid',
                'status' => $status
            ];
            
            echo "<tr>";
            echo "<td>{$test[2]}</td>";
            echo "<td>'" . htmlspecialchars($test[0]) . "'</td>";
            echo "<td>" . ($test[1] ? 'Valid' : 'Invalid') . "</td>";
            echo "<td>" . ($result ? 'Valid' : 'Invalid') . "</td>";
            echo "<td style='color: " . ($status === 'PASS' ? 'green' : 'red') . "'><b>{$status}</b></td>";
            echo "</tr>";
        }
        echo "</table>";
        return $results;
    }
    
    // Helper validation methods
    private function validateUsername($username) {
        if (empty(trim($username))) return false;
        $length = strlen($username);
        if ($length < 3 || $length > 100) return false;
        // Allow letters, numbers, underscore, dot, hyphen
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) return false;
        return true;
    }
    
    private function validatePassword($password) {
        if (empty($password)) return false;
        if (strlen($password) < 6 || strlen($password) > 255) return false;
        // Basic validation - at least one letter and one number
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }
    
    private function validateSecurityAnswer($answer) {
        if (empty(trim($answer))) return false;
        if (strlen($answer) < 2 || strlen($answer) > 255) return false;
        // Basic sanitization check
        if (preg_match('/<[^>]*>/', $answer)) return false;
        return true;
    }
    
    private function validateRole($role) {
        $validRoles = ['user', 'admin', ''];
        return in_array(strtolower($role), $validRoles);
    }
}

// Run tests if executed directly
if (PHP_SAPI === 'cli' || isset($_GET['run_tests'])) {
    try {
        require_once '../dbconnect.php';
        $test = new UserValidationTest($pdo);
        
        echo "<!DOCTYPE html><html><head><title>User Validation Tests</title></head><body>";
        echo "<h1>User Validation Test Results</h1>";
        
        $test->testUsernameValidation();
        $test->testEmailValidation();
        $test->testPasswordValidation();
        $test->testSecurityAnswerValidation();
        $test->testRoleValidation();
        
        echo "</body></html>";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>