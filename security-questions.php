<?php
require_once 'dbconnect.php';
session_start();

// Redirect if not coming from registration
if (!isset($_SESSION['registering_user'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer1 = trim($_POST['security_answer_1']);
    $answer2 = trim($_POST['security_answer_2']);
    $answer3 = trim($_POST['security_answer_3']);
    
    // Basic validation
    if (empty($answer1) || empty($answer2) || empty($answer3)) {
        $error = "All security questions must be answered";
    } else {
        // Get user data from session
        $user_data = $_SESSION['registering_user'];
        
        try {
            // Hash the answers
            $hashedAnswer1 = password_hash(strtolower($answer1), PASSWORD_DEFAULT);
            $hashedAnswer2 = password_hash(strtolower($answer2), PASSWORD_DEFAULT);
            $hashedAnswer3 = password_hash(strtolower($answer3), PASSWORD_DEFAULT);
            
            // Check if username already exists to prevent duplicate entry
            $checkUserStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $checkUserStmt->execute([$user_data['username']]);
            
            if ($checkUserStmt->fetch()) {
                $error = "Username already exists. Please choose a different username.";
            } else {
                // Insert user with security questions
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, security_question_1_id, security_answer_1_hash, security_question_2_id, security_answer_2_hash, security_question_3_id, security_answer_3_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Get question IDs from the questions array
                $question1_id = $user_data['questions'][0]['question_id'];
                $question2_id = $user_data['questions'][1]['question_id'];
                $question3_id = $user_data['questions'][2]['question_id'];
                
                $stmt->execute([
                    $user_data['username'],
                    $user_data['email'], 
                    $user_data['password_hash'],
                    $question1_id,
                    $hashedAnswer1,
                    $question2_id,
                    $hashedAnswer2, 
                    $question3_id,
                    $hashedAnswer3
                ]);
                
                // Clear session and redirect to login
                unset($_SESSION['registering_user']);
                header("Location: login.php?message=Registration successful! Please login.&status=success");
                exit;
            }
            
        } catch (PDOException $e) {
            // Check if it's a duplicate entry error
            if ($e->errorInfo[1] == 1062) {
                $error = "Username or email already exists. Please choose different credentials.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Check if we already have questions in session, otherwise fetch new ones
if (!isset($_SESSION['registering_user']['questions']) || count($_SESSION['registering_user']['questions']) < 3) {
    try {
        $stmt = $pdo->query("SELECT question_id, question_text, expected_data_type FROM security_questions WHERE is_active = TRUE ORDER BY RAND() LIMIT 3");
        $questions = $stmt->fetchAll();
        
        if (count($questions) < 3) {
            die("Not enough security questions in database");
        }
        
        // Store questions in session
        $_SESSION['registering_user']['questions'] = $questions;
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    $questions = $_SESSION['registering_user']['questions'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions - Power Rangers Movie Watchlist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h2>Security Questions</h2>
            <p class="form-subtitle">Please answer these security questions for account recovery</p>
            
            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Question 1 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($questions[0]['question_text']); ?></label>
                    <?php if ($questions[0]['expected_data_type'] === 'boolean'): ?>
                        <select name="security_answer_1" required>
                            <option value="">Select Yes or No</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    <?php elseif ($questions[0]['expected_data_type'] === 'integer'): ?>
                        <input type="number" name="security_answer_1" placeholder="Enter a number" required>
                    <?php else: ?>
                        <input type="text" name="security_answer_1" placeholder="Enter your answer" required>
                    <?php endif; ?>
                </div>
                
                <!-- Question 2 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($questions[1]['question_text']); ?></label>
                    <?php if ($questions[1]['expected_data_type'] === 'boolean'): ?>
                        <select name="security_answer_2" required>
                            <option value="">Select Yes or No</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    <?php elseif ($questions[1]['expected_data_type'] === 'integer'): ?>
                        <input type="number" name="security_answer_2" placeholder="Enter a number" required>
                    <?php else: ?>
                        <input type="text" name="security_answer_2" placeholder="Enter your answer" required>
                    <?php endif; ?>
                </div>
                
                <!-- Question 3 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($questions[2]['question_text']); ?></label>
                    <?php if ($questions[2]['expected_data_type'] === 'boolean'): ?>
                        <select name="security_answer_3" required>
                            <option value="">Select Yes or No</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    <?php elseif ($questions[2]['expected_data_type'] === 'integer'): ?>
                        <input type="number" name="security_answer_3" placeholder="Enter a number" required>
                    <?php else: ?>
                        <input type="text" name="security_answer_3" placeholder="Enter your answer" required>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn">Complete Registration</button>
            </form>
            
            <div class="login-link">
                <a href="index.php">Back to Registration</a>
            </div>
        </div>
    </div>
</body>
</html>