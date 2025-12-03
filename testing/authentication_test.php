<?php
/**
 * Authentication & User Management Validation Tests
 * Run: php authentication_tests.php
 */

class AuthenticationValidator {
    
    // ==================== VALIDATION METHODS ====================
    
    public static function validateUsername($username) {
        if (empty($username)) return false;
        if (strlen($username) < 3 || strlen($username) > 15) return false;
        if (strpos($username, ' ') !== false) return false;
        
        // Alphanumeric only
        if (!ctype_alnum($username)) return false;
        
        return true;
    }
    
    public static function validatePassword($password) {
        if (empty($password)) return false;
        if (strlen($password) < 8 || strlen($password) > 64) return false;
        
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasDigit = preg_match('/\d/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
        
        return $hasUpper && $hasLower && $hasDigit && $hasSpecial;
    }
    
    public static function validateEmail($email) {
        if (empty($email)) return false;
        if (strlen($email) < 6 || strlen($email) > 255) return false;
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateSecurityAnswer($answer) {
        if (empty($answer)) return false;
        $answer = trim($answer);
        return strlen($answer) >= 2 && strlen($answer) <= 50;
    }
    
    public static function validateRole($roleId) {
        return is_numeric($roleId) && $roleId >= 1 && $roleId <= 3;
    }
    
    public static function validateStatus($statusId) {
        return is_numeric($statusId) && $statusId >= 1 && $statusId <= 3;
    }
}

// ==================== TEST RUNNER ====================

class TestRunner {
    public static function runAllTests() {
        echo "=== AUTHENTICATION VALIDATION TESTS ===\n\n";
        
        self::testUsername();
        self::testPassword();
        self::testEmail();
        self::testSecurityAnswer();
        self::testRole();
        self::testStatus();
        
        echo "\n=== ALL TESTS COMPLETED ===\n";
    }
    
    private static function testUsername() {
        echo "--- USERNAME VALIDATION TESTS ---\n";
        
        $tests = [
            ['', false, 'Extreme Min: Empty'],
            ['a', false, 'Min -1: 1 char'],
            ['ab', false, 'Min Boundary: 2 chars'],
            ['abc', true, 'Min +1: 3 chars'],
            ['user1234', true, 'Mid: 8 chars'],
            ['username12345678', true, 'Max -1: 14 chars'],
            ['username123456789', true, 'Max Boundary: 15 chars'],
            ['username1234567890', false, 'Max +1: 16 chars'],
            ['aaaaaaaaaaaaaaaaaaaa', false, 'Extreme Max: 20 chars'],
            ['admin user', false, 'Contains space'],
            ['user@name', false, 'Contains special char'],
        ];
        
        self::runTestSuite($tests, 'validateUsername');
    }
    
    private static function testPassword() {
        echo "\n--- PASSWORD VALIDATION TESTS ---\n";
        
        $tests = [
            ['', false, 'Extreme Min: Empty'],
            ['Pass1!', false, 'Min -1: 6 chars'],
            ['Pass1!ab', true, 'Min Boundary: 8 chars'],
            ['Pass1!abc', true, 'Min +1: 9 chars'],
            ['Password123!', true, 'Mid: Valid password'],
            ['password123', false, 'No uppercase'],
            ['PASSWORD123!', false, 'No lowercase'],
            ['Password!', false, 'No number'],
            ['Password123', false, 'No special char'],
            ['Pa1!', false, 'Too short'],
        ];
        
        self::runTestSuite($tests, 'validatePassword');
    }
    
    private static function testEmail() {
        echo "\n--- EMAIL VALIDATION TESTS ---\n";
        
        $tests = [
            ['', false, 'Extreme Min: Empty'],
            ['a@b.c', false, 'Min -1: Too short'],
            ['a@b.co', true, 'Min Boundary: Valid short'],
            ['user@example.com', true, 'Mid: Valid'],
            ['user.name@domain.co.uk', true, 'Valid with subdomain'],
            ['user@.com', false, 'Invalid format'],
            ['user@domain', false, 'Missing TLD'],
            ['@domain.com', false, 'Missing username'],
            ['user@com', false, 'Invalid'],
            ['user@' . str_repeat('a', 250) . '.com', false, 'Too long'],
        ];
        
        self::runTestSuite($tests, 'validateEmail');
    }
    
    private static function testSecurityAnswer() {
        echo "\n--- SECURITY ANSWER TESTS ---\n";
        
        $tests = [
            ['', false, 'Extreme Min: Empty'],
            ['A', false, 'Min -1: 1 char'],
            ['Ab', true, 'Min Boundary: 2 chars'],
            ['My Security Answer', true, 'Mid: Valid'],
            ['  answer  ', true, 'With spaces (trimmed)'],
            ['A', false, 'Too short'],
            [str_repeat('a', 51), false, 'Too long'],
            ['ValidAnswer123', true, 'Valid with numbers'],
        ];
        
        self::runTestSuite($tests, 'validateSecurityAnswer');
    }
    
    private static function testRole() {
        echo "\n--- ROLE VALIDATION TESTS ---\n";
        
        $tests = [
            [0, false, 'Extreme Min: 0'],
            [1, true, 'Min Boundary: 1 (User)'],
            [2, true, 'Mid: 2 (Moderator)'],
            [3, true, 'Max Boundary: 3 (Admin)'],
            [4, false, 'Max +1: 4'],
            [999, false, 'Extreme Max: 999'],
            ['admin', false, 'Invalid type: string'],
            ['', false, 'Empty'],
        ];
        
        self::runTestSuite($tests, 'validateRole');
    }
    
    private static function testStatus() {
        echo "\n--- STATUS VALIDATION TESTS ---\n";
        
        $tests = [
            [0, false, 'Extreme Min: 0'],
            [1, true, 'Min Boundary: 1 (Active)'],
            [2, true, 'Mid: 2 (Suspended)'],
            [3, true, 'Max Boundary: 3 (Banned)'],
            [4, false, 'Max +1: 4'],
            ['active', false, 'Invalid type: string'],
            ['', false, 'Empty'],
        ];
        
        self::runTestSuite($tests, 'validateStatus');
    }
    
    private static function runTestSuite($tests, $methodName) {
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $test) {
            $input = $test[0];
            $expected = $test[1];
            $description = $test[2];
            
            $result = AuthenticationValidator::$methodName($input);
            $status = ($result === $expected) ? 'PASS' : 'FAIL';
            
            if ($result === $expected) {
                $passed++;
            }
            
            $inputDisplay = is_string($input) ? "'$input'" : $input;
            echo sprintf("  %-10s | Input: %-30s | Expected: %-6s | Got: %-6s | %s\n",
                $status,
                $inputDisplay,
                $expected ? 'VALID' : 'INVALID',
                $result ? 'VALID' : 'INVALID',
                $description
            );
        }
        
        echo "  Result: {$passed}/{$total} passed\n";
    }
}

// ==================== TEST REPORT GENERATOR ====================

class TestReportGenerator {
    public static function generateTestLogs() {
        echo "\n\n=== TEST LOGS FOR WORD DOCUMENT ===\n";
        echo "Copy and paste these into your test logs:\n\n";
        
        self::generateUsernameLog();
        self::generatePasswordLog();
        self::generateEmailLog();
        self::generateSecurityAnswerLog();
        self::generateRoleLog();
        self::generateStatusLog();
    }
    
    private static function generateUsernameLog() {
        echo "Username Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', '', 'Error', 'Error'],
            ['Min -1', 'a', 'Error', 'Error'],
            ['Min (Boundary)', 'ab', 'Error', 'Error'],
            ['Min +1', 'abc', 'Success', 'Success'],
            ['Mid', 'user1234', 'Success', 'Success'],
            ['Max -1', 'username12345678', 'Success', 'Success'],
            ['Max (Boundary)', 'username123456789', 'Success', 'Success'],
            ['Max +1', 'username1234567890', 'Error', 'Error'],
            ['Extreme Max', str_repeat('a', 100), 'Error', 'Error'],
            ['Invalid data type', 12345, 'Error', 'Error'],
            ['Other tests', 'admin user', 'Error', 'Error'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validateUsername($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
    
    private static function generatePasswordLog() {
        echo "Password Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', '', 'Error', 'Error'],
            ['Min -1', 'Pass1!', 'Error', 'Error'],
            ['Min (Boundary)', 'Pass1!ab', 'Success', 'Success'],
            ['Min +1', 'Pass1!abc', 'Success', 'Success'],
            ['Mid', 'Password123!', 'Success', 'Success'],
            ['Max -1', str_repeat('a', 63) . '1!A', 'Success', 'Success'],
            ['Max (Boundary)', str_repeat('a', 64) . '1!A', 'Success', 'Success'],
            ['Max +1', str_repeat('a', 65) . '1!A', 'Error', 'Error'],
            ['Extreme Max', str_repeat('a', 1000) . '1!A', 'Error', 'Error'],
            ['Invalid data type', 12345678, 'Error', 'Error'],
            ['Other tests', 'password123', 'Error', 'Error'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validatePassword($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
    
    private static function generateEmailLog() {
        echo "Email Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', '', 'Error', 'Error'],
            ['Min -1', 'a@b.c', 'Error', 'Error'],
            ['Min (Boundary)', 'a@b.co', 'Success', 'Success'],
            ['Min +1', 'ab@cd.ef', 'Success', 'Success'],
            ['Mid', 'user@example.com', 'Success', 'Success'],
            ['Max -1', 'user@' . str_repeat('a', 247) . '.com', 'Success', 'Success'],
            ['Max (Boundary)', 'user@' . str_repeat('a', 248) . '.com', 'Success', 'Success'],
            ['Max +1', 'user@' . str_repeat('a', 249) . '.com', 'Error', 'Error'],
            ['Extreme Max', str_repeat('a', 500) . '@domain.com', 'Error', 'Error'],
            ['Invalid data type', 12345, 'Error', 'Error'],
            ['Other tests', 'user@.com', 'Error', 'Error'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validateEmail($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
    
    private static function generateSecurityAnswerLog() {
        echo "Security Answer Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', '', 'Error', 'Error'],
            ['Min -1', 'A', 'Error', 'Error'],
            ['Min (Boundary)', 'Ab', 'Success', 'Success'],
            ['Min +1', 'Abc', 'Success', 'Success'],
            ['Mid', 'MySecurityAnswer', 'Success', 'Success'],
            ['Max -1', str_repeat('a', 49), 'Success', 'Success'],
            ['Max (Boundary)', str_repeat('a', 50), 'Success', 'Success'],
            ['Max +1', str_repeat('a', 51), 'Error', 'Error'],
            ['Extreme Max', str_repeat('a', 100), 'Error', 'Error'],
            ['Invalid data type', 12345, 'Error', 'Error'],
            ['Other tests', '  answer  ', 'Success', 'Success'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validateSecurityAnswer($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
    
    private static function generateRoleLog() {
        echo "Role Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', 0, 'Error', 'Error'],
            ['Min -1', 0, 'Error', 'Error'],
            ['Min (Boundary)', 1, 'Success', 'Success'],
            ['Min +1', 2, 'Success', 'Success'],
            ['Mid', 2, 'Success', 'Success'],
            ['Max -1', 2, 'Success', 'Success'],
            ['Max (Boundary)', 3, 'Success', 'Success'],
            ['Max +1', 4, 'Error', 'Error'],
            ['Extreme Max', 999, 'Error', 'Error'],
            ['Invalid data type', 'admin', 'Error', 'Error'],
            ['Other tests', NULL, 'Error', 'Error'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validateRole($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
    
    private static function generateStatusLog() {
        echo "Status Validation Test Log:\n";
        echo "| Test Type | Test Data | Expected Result | Actual Result |\n";
        echo "|-----------|-----------|-----------------|---------------|\n";
        
        $tests = [
            ['Extreme Min', 0, 'Error', 'Error'],
            ['Min -1', 0, 'Error', 'Error'],
            ['Min (Boundary)', 1, 'Success', 'Success'],
            ['Min +1', 2, 'Success', 'Success'],
            ['Mid', 2, 'Success', 'Success'],
            ['Max -1', 2, 'Success', 'Success'],
            ['Max (Boundary)', 3, 'Success', 'Success'],
            ['Max +1', 4, 'Error', 'Error'],
            ['Extreme Max', 999, 'Error', 'Error'],
            ['Invalid data type', 'active', 'Error', 'Error'],
            ['Other tests', NULL, 'Error', 'Error'],
        ];
        
        foreach ($tests as $test) {
            $actual = AuthenticationValidator::validateStatus($test[1]) ? 'Success' : 'Error';
            echo "| {$test[0]} | {$test[1]} | {$test[2]} | {$actual} |\n";
        }
        echo "\n";
    }
}

// ==================== MAIN EXECUTION ====================

if (php_sapi_name() === 'cli') {
    // Run from command line
    TestRunner::runAllTests();
    TestReportGenerator::generateTestLogs();
} else {
    // Run from web browser
    echo "<pre>";
    TestRunner::runAllTests();
    TestReportGenerator::generateTestLogs();
    echo "</pre>";
}

?>