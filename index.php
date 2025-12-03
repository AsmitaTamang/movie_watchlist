<!DOCTYPE html>

<!-- This is the main registration page for Power Rangers Movie Watchlist -->
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Rangers Movie Watchlist - Register</title>
    <!-- Link to external CSS file for styling -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <div class="container">
        <!-- Left section: Welcome message and features list -->
        <div class="welcome-section">
            <!-- Logo container for branding -->
            <div class="logo-container">
                <!-- Company logo image with alt text for accessibility -->
                <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="company-logo">
            </div>
            
            
            <h1>Power Rangers Movie Watchlist</h1>
            <!-- Slogan or tagline describing the service -->
            <p class="slogan">Add and Manage your Favorite Movies</p>
            
            <!-- Unordered list of key features to attract users -->
            <ul class="features">
                <!-- Feature 1: Core functionality -->
                <li>Save and organize your favorite movies</li>
                <!-- Feature 2: Tracking capabilities -->
                <li>Keep track of watched movies and movies you want to watch</li>
                <!-- Feature 3: Customization options -->
                <li>Create as many watchlist as you want with different category</li>
                <!-- Feature 4: Social sharing -->
                <li>Share your watchlist with friends</li>
            </ul>
        </div>
        
        <!-- Right section: Registration form -->
        <div class="form-section">
            
            <h2>Create Account</h2>
            
            <p class="form-subtitle">Join our community of movie enthusiasts</p>
            
            <!-- PHP section for displaying success/error messages -->
            <!-- This checks if a message parameter exists in the URL -->
            <?php if (isset($_GET['message'])): ?>
                <!-- Dynamic message div that changes class based on status (success/error) -->
                <!-- The status determines the color and styling of the message -->
                <div class="message <?php echo $_GET['status'] ?? ''; ?>">
                    <!-- Display the message with XSS protection using htmlspecialchars -->
                    <!-- htmlspecialchars converts special characters to HTML entities to prevent cross-site scripting attacks -->
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
                <!-- End of PHP if statement -->
            <?php endif; ?>
            
            <!-- Registration form that sends data to register.php using POST method -->
            <!-- POST method is used for security as it doesn't expose data in URL -->
            <form action="register.php" method="POST">
                <!-- Form group for full name input -->
                <div class="form-group">
                    <!-- Label for accessibility and usability -->
                    <label for="fullName">Full Name</label>
                    <!-- Text input field for full name -->
                    <!-- required attribute enables browser validation -->
                    <input type="text" id="fullName" name="fullName" required>
                </div>
               
                <!-- Form group for email input -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <!-- Email input type with built-in browser validation -->
                    <!-- Browser will check if input matches email format -->
                    <input type="email" id="email" name="email" required>
                </div>
                
                <!-- Form group for password input -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <!-- Password input type hides characters as user types -->
                    <input type="password" id="password" name="password" required>
                </div>
                
                <!-- Form group for password confirmation -->
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <!-- Second password field to verify user typed correctly -->
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
               
               
                
                <!-- Submit button to create account -->
                <button type="submit" class="btn">Create Account</button>
            </form>
            
            <!-- Link to login page for existing users -->
            <div class="login-link">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>
</body>
</html>