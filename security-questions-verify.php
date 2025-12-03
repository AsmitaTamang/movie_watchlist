<?php
require_once 'dbconnect.php';
session_start();

// -------------------------------------------------------------
// Ensure the user entered an email on the previous page.
// If not, redirect them back to the first step.
// -------------------------------------------------------------
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password-init.php");
    exit;
}

// -------------------------------------------------------------
// Fetch the user’s security questions and expected data types
// These questions are linked by ID to the "security_questions" table
// -------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email, 
               q1.question_text as question1, q1.expected_data_type as type1,
               q2.question_text as question2, q2.expected_data_type as type2, 
               q3.question_text as question3, q3.expected_data_type as type3
        FROM users u
        JOIN security_questions q1 ON u.security_question_1_id = q1.question_id
        JOIN security_questions q2 ON u.security_question_2_id = q2.question_id  
        JOIN security_questions q3 ON u.security_question_3_id = q3.question_id
        WHERE u.email = ?
    ");
    $stmt->execute([$_SESSION['reset_email']]);
    $user = $stmt->fetch();
    
    // If no user found → something is wrong
    if (!$user) {
        header("Location: forgot-password-init.php?message=User not found&status=error");
        exit;
    }
} catch (PDOException $e) {
    // If DB error, redirect with message
    header("Location: forgot-password-init.php?message=Database error&status=error");
    exit;
}

// -------------------------------------------------------------
// When form is submitted, verify all three answers
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer1 = trim($_POST['answer1']);
    $answer2 = trim($_POST['answer2']); 
    $answer3 = trim($_POST['answer3']);
    
    // Basic validation
    if (empty($answer1) || empty($answer2) || empty($answer3)) {
        $error = "All security questions must be answered";
    } else {
        try {
            // Retrieve hashed stored answers for verification
            $stmt = $pdo->prepare("
                SELECT security_answer_1_hash, security_answer_2_hash, security_answer_3_hash 
                FROM users WHERE email = ?
            ");
            $stmt->execute([$_SESSION['reset_email']]);
            $hashedAnswers = $stmt->fetch();
            
            // ---------------------------------------------------------
            // Compare answers with hashed values (case-insensitive)
            // password_verify() handles the real hash comparison
            // ---------------------------------------------------------
            $answer1Correct = password_verify(strtolower($answer1), $hashedAnswers['security_answer_1_hash']);
            $answer2Correct = password_verify(strtolower($answer2), $hashedAnswers['security_answer_2_hash']);
            $answer3Correct = password_verify(strtolower($answer3), $hashedAnswers['security_answer_3_hash']);
            
            // If all three are correct → log user in immediately
            if ($answer1Correct && $answer2Correct && $answer3Correct) {
                
                // Set login session values
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Clear reset verification session
                unset($_SESSION['reset_email']);
                
                // Redirect with success message
                header("Location: login.php?message=Login successful via security questions!&status=success");
                exit;
            
            } else {
                // One or more answers incorrect
                $error = "One or more answers are incorrect. Please try again.";
            }
        } catch (PDOException $e) {
            // DB failure
            $error = "Database error: " . $e->getMessage();
        }
    }
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
            <p class="form-subtitle">Answer your security questions to access your account</p>
            
            <!-- Display validation errors -->
            <?php if (isset($error)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Question 1 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($user['question1']); ?></label>
                    <?php 
                    // Render appropriate input type based on expected_data_type
                    switch($user['type1']) {
                        case 'boolean':
                            echo '<select name="answer1" required>
                                    <option value="">Select Yes or No</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>';
                            break;
                        case 'number':
                            echo '<input type="number" name="answer1" placeholder="Enter a number" required>';
                            break;
                        default:
                            echo '<input type="text" name="answer1" placeholder="Enter your answer" required>';
                    }
                    ?>
                </div>
                
                <!-- Question 2 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($user['question2']); ?></label>
                    <?php 
                    switch($user['type2']) {
                        case 'boolean':
                            echo '<select name="answer2" required>
                                    <option value="">Select Yes or No</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>';
                            break;
                        case 'number':
                            echo '<input type="number" name="answer2" placeholder="Enter a number" required>';
                            break;
                        default:
                            echo '<input type="text" name="answer2" placeholder="Enter your answer" required>';
                    }
                    ?>
                </div>
                
                <!-- Question 3 -->
                <div class="form-group">
                    <label><?php echo htmlspecialchars($user['question3']); ?></label>
                    <?php 
                    switch($user['type3']) {
                        case 'boolean':
                            echo '<select name="answer3" required>
                                    <option value="">Select Yes or No</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>';
                            break;
                        case 'number':
                            echo '<input type="number" name="answer3" placeholder="Enter a number" required>';
                            break;
                        default:
                            echo '<input type="text" name="answer3" placeholder="Enter your answer" required>';
                    }
                    ?>
                </div>
                
                <button type="submit" class="btn">Access My Account</button>
            </form>
            
            <div class="login-link">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
