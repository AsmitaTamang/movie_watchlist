<?php
/**
 * Main Validator Class
 */
class AuthenticationValidator {
    
    // Username: varchar(100), NOT NULL, UNIQUE
    public static function validateUsername($username) {
        if (empty(trim($username))) return false;
        $length = strlen($username);
        if ($length < 3 || $length > 100) return false;
        // Allow letters, numbers, underscore, dot, hyphen
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) return false;
        return true;
    }
    
    // Email: varchar(120), NOT NULL, UNIQUE
    public static function validateEmail($email) {
        if (empty(trim($email))) return false;
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Password: varchar(255), NOT NULL
    public static function validatePassword($password) {
        if (empty($password)) return false;
        if (strlen($password) < 6 || strlen($password) > 255) return false;
        return true;
    }
    
    // Security Answer: varchar(255), NULL
    public static function validateSecurityAnswer($answer) {
        if ($answer === null || $answer === '') return true; // Optional field
        if (strlen($answer) < 2 || strlen($answer) > 255) return false;
        return true;
    }
    
    // Role: ENUM('admin','user'), defaults to 'user'
    public static function validateRole($role) {
        if ($role === null || $role === '') return true; // Defaults to 'user'
        $validRoles = ['admin', 'user'];
        return in_array(strtolower($role), $validRoles);
    }
    
    // is_active: tinyint(1), defaults to 1
    public static function validateIsActive($value) {
        if ($value === null) return true; // Defaults to 1
        if ($value === 0 || $value === 1) return true;
        if ($value === '0' || $value === '1') return true;
        return false;
    }
}
?>