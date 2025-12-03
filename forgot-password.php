<!-- In the security questions form section -->
<div class="form-group">
    <!-- Display Security Question 1 -->
    <label><?php echo htmlspecialchars($user['question1']); ?></label>

    <?php 
    // Render input type based on question type (stored in DB)
    switch($user['type1']) {

        case 'boolean':   // Yes/No type question
            echo '<select name="answer1" required>
                    <option value="">Select Yes or No</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                  </select>';
            break;

        case 'number':    // Numeric answer required
            echo '<input type="number" name="answer1" placeholder="Enter a number" required>';
            break;

        default:          // Default to text input for all other types
            echo '<input type="text" name="answer1" placeholder="Enter your answer" required>';
    }
    ?>
</div>

<!-- Repeat for question 2 and 3 -->
<div class="form-group">
    <!-- Display Security Question 2 -->
    <label><?php echo htmlspecialchars($user['question2']); ?></label>

    <?php 
    // Determine proper input for question 2
    switch($user['type2']) {

        case 'boolean':   // Yes/No dropdown
            echo '<select name="answer2" required>
                    <option value="">Select Yes or No</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                  </select>';
            break;

        case 'number':    // Numeric required
            echo '<input type="number" name="answer2" placeholder="Enter a number" required>';
            break;

        default:          // Text input fallback
            echo '<input type="text" name="answer2" placeholder="Enter your answer" required>';
    }
    ?>
</div>

<div class="form-group">
    <!-- Display Security Question 3 -->
    <label><?php echo htmlspecialchars($user['question3']); ?></label>

    <?php 
    // Input type selection for question 3
    switch($user['type3']) {

        case 'boolean':   // Boolean type → yes/no dropdown
            echo '<select name="answer3" required>
                    <option value="">Select Yes or No</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                  </select>';
            break;

        case 'number':    // Number input
            echo '<input type="number" name="answer3" placeholder="Enter a number" required>';
            break;

        default:          // Default text input
            echo '<input type="text" name="answer3" placeholder="Enter your answer" required>';
    }
    ?>
</div>
