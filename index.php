<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Rangers Movie Watchlist - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="welcome-section">
            <div class="logo-container">
                <img src="logo.jpg.jpeg" alt="Power Rangers Logo" class="company-logo">
            </div>
            <!-- Our company name -->
            <h1>Power Rangers Movie Watchlist</h1>
            <p class="slogan">Add and Manage your Favorite Movies</p>
            <ul class="features">
                <li>Save and organize your favorite movies</li>
                <li>Keep track of watched movies and movies you want to watch</li>
                <li>Create as many watchlist as you want with different category</li>
                <li>Share your watchlist with friends</li>
            </ul>
        </div>
        
        <div class="form-section">
            <h2>Create Account</h2>
            <p class="form-subtitle">Join our community of movie enthusiasts</p>
            
            <!-- Show success/error messages -->
            <!-- checks if the message is passed in the URL -->
            <?php if (isset($_GET['message'])): ?>
                <!-- creating a div with a message:success or error -->
                <div class="message <?php echo $_GET['status'] ?? ''; ?>">
                    <!-- htmlspecialchars is used to prevent a XSS attack -->
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
                <!-- this ends the statement -->
            <?php endif; ?>
            
            <!-- creating a form that will send data to register.php -->
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required>
                </div>
               
                <!-- required is browser validation, and it is creating a form that accepts email in register.php -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </div>
    </div>
</body>
</html>