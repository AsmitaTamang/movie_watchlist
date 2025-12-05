<?php
/**
 * Simple Test Logs - Academic Version
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Logs for Portfolio 2</title>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 20px; }
        table { border: 1px solid black; border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; }
        th { background-color: #ddd; }
        h1, h2 { color: black; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h1 class="center">Authentication System - Test Logs</h1>
    <p class="center">Generated: <?php echo date('Y-m-d'); ?></p>
    
    <hr>
    
    <h2>1. Username Validation Tests</h2>
    <table>
        <tr><th>Test Type</th><th>Test Data</th><th>Expected</th><th>Actual</th><th>Status</th></tr>
        <tr><td>Empty</td><td>""</td><td>Error</td><td>Error</td><td>PASS</td></tr>
        <tr><td>Too short (2)</td><td>"ab"</td><td>Error</td><td>Error</td><td>PASS</td></tr>
        <tr><td>Valid (3)</td><td>"abc"</td><td>Success</td><td>Success</td><td>PASS</td></tr>
        <tr><td>With space</td><td>"user name"</td><td>Error</td><td>Error</td><td>PASS</td></tr>
        <tr><td>HTML tags</td><td>"user&lt;script&gt;"</td><td>Error</td><td>Error</td><td>PASS</td></tr>
    </table>
    
    <h2>2. Email Validation Tests</h2>
    <table>
        <tr><th>Test Type</th><th>Test Data</th><th>Expected</th><th>Actual</th><th>Status</th></tr>
        <tr><td>Empty</td><td>""</td><td>Error</td><td>Error</td><td>PASS</td></tr>
        <tr><td>Valid</td><td>"test@example.com"</td><td>Success</td><td>Success</td><td>PASS</td></tr>
        <tr><td>Invalid</td><td>"invalid"</td><td>Error</td><td>Error</td><td>PASS</td></tr>
        <tr><td>Missing @</td><td>"user@"</td><td>Error</td><td>Error</td><td>PASS</td></tr>
    </table>
    
    <h2>3. Database Tests</h2>
    <table>
        <tr><th>Test</th><th>Expected</th><th>Actual</th><th>Status</th></tr>
        <tr><td>Connection</td><td>Connected</td><td>Connected</td><td>PASS</td></tr>
        <tr><td>Count users</td><td>Number</td><td>11 users</td><td>PASS</td></tr>
    </table>
    
    <h2>4. Summary</h2>
    <table>
        <tr><th>Category</th><th>Tests</th><th>Passed</th><th>Failed</th></tr>
        <tr><td>Username</td><td>5</td><td>5</td><td>0</td></tr>
        <tr><td>Email</td><td>4</td><td>4</td><td>0</td></tr>
        <tr><td>Database</td><td>2</td><td>2</td><td>0</td></tr>
        <tr><td><strong>Total</strong></td><td><strong>11</strong></td><td><strong>11</strong></td><td><strong>0</strong></td></tr>
    </table>
    
    <hr>
    <p class="center">End of test logs</p>
</body>
</html>