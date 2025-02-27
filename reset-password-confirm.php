<?php
// reset-password-confirm.php - Process the password reset
require_once 'config.php';

// Initialize variables
$email = $token = $password = $confirm_password = "";
$email_err = $token_err = $password_err = $confirm_password_err = "";
$reset_success = false;
$valid_request = false;

// Check if email and token are provided in the URL
if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];
    
    // Validate the token for SQLite3
    $sql = "SELECT id, token_expiry FROM users WHERE email = :email AND verification_token = :token";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':token', $token, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        if ($result) {
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Check if token has expired
                if (strtotime($row['token_expiry']) > time()) {
                    $valid_request = true;
                    $id = $row['id'];
                } else {
                    $token_err = "This password reset link has expired. Please request a new one.";
                }
            } else {
                $token_err = "Invalid password reset link.";
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_request) {
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 8) {
        $password_err = "Password must have at least 8 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before updating the database
    if (empty($password_err) && empty($confirm_password_err)) {
        // Prepare an update statement for SQLite3
        $sql = "UPDATE users SET password = :password, verification_token = NULL, token_expiry = NULL WHERE email = :email";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Set parameters
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Bind variables
            $stmt->bindValue(':password', $param_password, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            
            // Attempt to execute the prepared statement
            $result = $stmt->execute();
            if ($result) {
                $reset_success = true;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// SQLite doesn't have a close method like mysqli
// Uncomment the below line if using mysqli
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SU2H1 Rating</title>
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
            text-decoration: none;
            display: inline-block;
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
                <p>Your password has been reset successfully!</p>
                <p>You can now <a href="login.php">login</a> with your new password.</p>
            </div>
        <?php elseif ($valid_request): ?>
            <p>Please enter your new password.</p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?email=" . urlencode($email) . "&token=" . $token; ?>" method="post">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control">
                    <span class="help-block"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                    <span class="help-block"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Reset Password">
                </div>
            </form>
        <?php else: ?>
            <div class="error-message">
                <?php echo $token_err; ?>
            </div>
            <a href="reset-password.php" class="btn-primary">Request New Reset Link</a>
        <?php endif; ?>
    </div>
</body>
</html>