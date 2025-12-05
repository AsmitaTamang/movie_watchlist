<?php
require_once 'validator.php';

function runPasswordTests() {
    echo "=== PASSWORD VALIDATION TESTS ===\n\n";
    
    $tests = [
        ['', false, 'Extreme Min: Empty'],
        ['pass1', false, 'Min -1: 5 chars'],
        ['pass12', true, 'Min Boundary: 6 chars'],
        ['password123', true, 'Valid password'],
        [str_repeat('a', 255), true, 'Max Boundary: 255 chars'],
        [str_repeat('a', 256), false, 'Max +1: 256 chars'],
    ];
    
    $passed = 0;
    $total = count($tests);
    
    foreach ($tests as $test) {
        $input = $test[0];
        $expected = $test[1];
        $description = $test[2];
        
        $result = AuthenticationValidator::validatePassword($input);
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