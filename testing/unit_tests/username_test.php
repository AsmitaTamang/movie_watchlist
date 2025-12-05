<?php
require_once 'validator.php';

function runUsernameTests() {
    echo "=== USERNAME VALIDATION TESTS ===\n\n";
    
    $tests = [
        ['', false, 'Extreme Min: Empty'],
        ['ab', false, 'Min -1: 2 chars'],
        ['abc', true, 'Min Boundary: 3 chars'],
        ['user123', true, 'Mid: Valid username'],
        [str_repeat('a', 100), true, 'Max Boundary: 100 chars'],
        [str_repeat('a', 101), false, 'Max +1: 101 chars'],
        ['john_doe', true, 'Underscore allowed'],
        ['john.doe', true, 'Dot allowed'],
        ['john-doe', true, 'Hyphen allowed'],
        ['john doe', false, 'Space not allowed'],
        ["john<script>", false, 'HTML tags not allowed'],
    ];
    
    $passed = 0;
    $total = count($tests);
    
    foreach ($tests as $test) {
        $input = $test[0];
        $expected = $test[1];
        $description = $test[2];
        
        $result = AuthenticationValidator::validateUsername($input);
        $status = ($result === $expected) ? 'PASS' : 'FAIL';
        
        if ($result === $expected) $passed++;
        
        $displayInput = $input;
        if (strlen($input) > 20) {
            $displayInput = substr($input, 0, 17) . '...';
        }
        
        echo sprintf("  %-10s | Input: %-20s | Expected: %-6s | Got: %-6s | %s\n",
            $status,
            $displayInput,
            $expected ? 'VALID' : 'INVALID',
            $result ? 'VALID' : 'INVALID',
            $description
        );
    }
    
    echo "\nResult: {$passed}/{$total} passed\n";
    return $tests;
}
?>