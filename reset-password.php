<?php
// reset-password.php - Password reset request form - Simplified version
require_once 'config.php';

// For debugging - comment out in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$email = $email_err = "";
$reset_success = false;

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email address.";
    } else {
        $email = trim($_POST["email"]);
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // If no errors, proceed with password reset
    if (empty($email_err)) {
        try {
            // This is a direct reset approach for testing
            // Simply set a new password for the email address
            
            // Generate a new random password
            $new_password = substr(md5(uniqid(mt_rand(), true)), 0, 8); // 8-char random password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Check if email exists in database
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $result = $stmt->execute();
                
                if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    // Update user with new password AND verify the account
                    $update_sql = "UPDATE users SET password = :password, is_verified = 1 WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                        $update_stmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
                        
                        $update_result = $update_stmt->execute();
                        if ($update_result) {
                            $reset_success = true;
                            
                            // Show the new password to the user (for development only)
                            $success_message = "Your password has been reset and your account has been verified. Your new password is: <strong>$new_password</strong><br>
                                                Please log in with this password and change it immediately.";
                        }
                    }
                } else {
                    // For security, don't reveal that the email doesn't exist
                    $email_err = "If your email is registered, you will receive reset instructions.";
                }
            }
            
        } catch (Exception $e) {
            $email_err = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - RateMyTeacher</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .help-block {
            color: red;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Reset Password</h2>
        
        <?php if ($reset_success): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
                <p><a href="login.php">Go to login page</a></p>
            </div>
        <?php else: ?>
            
            <?php if (!empty($email_err)): ?>
                <div class="error-message"><?php echo $email_err; ?></div>
            <?php endif; ?>
            
            <p>Enter your email address and we'll reset your password.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Reset Password">
                </div>
                <p><a href="login.php">Back to Login</a></p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>