<?php
// login.php - User login form and processing - Simplified version

// For debugging - comment out in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include session configuration first, before starting the session
require_once 'session_config.php';

// Initialize the session after config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Now include other configurations that might use session data
require_once 'config.php';

// Check if the user is already logged in, if yes then redirect to home page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // If there's a redirect, send there, otherwise to home page
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        error_log("Already logged in - redirecting to: " . $_GET['redirect']);
        header("Location: " . $_GET['redirect']);
    } else {
        error_log("Already logged in - redirecting to home.php");
        header("Location: home.php");
    }
    exit;
}

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        try {
            // For SQLite3, we need to use a different approach than mysqli
            $sql = "SELECT id, username, email, password, is_verified FROM users WHERE email = :email";
    
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // Bind parameters
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                
                // Execute the statement
                $result = $stmt->execute();
                
                // Check if we found a user
                if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    // Check password
                    if (password_verify($password, $row['password'])) {
                        // Auto-verify all accounts for development
                        if ($row['is_verified'] == 0) {
                            // Update the user to be verified
                            $verify_sql = "UPDATE users SET is_verified = 1 WHERE id = :id";
                            $verify_stmt = $conn->prepare($verify_sql);
                            $verify_stmt->bindValue(':id', $row['id'], SQLITE3_INTEGER);
                            $verify_stmt->execute();
                        }
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $row['id'];
                        $_SESSION["username"] = $row['username'];
                        $_SESSION["email"] = $row['email'];
                        
                        // Debug session data being saved
                        error_log("Login successful for user: " . $row['username']);
                        error_log("Session ID: " . session_id());
                        error_log("Setting session variables: " . print_r($_SESSION, true));
                        
                        // Make sure session data is saved
                        session_regenerate_id(true); // Regenerate the session ID for security
                        session_write_close();
                        
                        // Check if there's a redirect parameter
                        if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                            $redirect = $_POST['redirect'];
                            error_log("Redirecting to: " . $redirect);
                            
                            // If the redirect URL is relative (doesn't start with http), prepend the site URL
                            if (strpos($redirect, 'http') !== 0) {
                                // Just use the redirect value directly for any local path
                                $redirect = ltrim($redirect, '/');
                                error_log("Using relative path: $redirect");
                            }
                            
                            error_log("Final redirect URL: " . $redirect);
                            header("Location: " . $redirect);
                        } else {
                            // Redirect user to home page
                            error_log("Redirecting to home page");
                            header("Location: home.php");
                        }
                        exit();
                    } else {
                        // Password is not valid
                        $login_err = "Invalid email or password.";
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Something went wrong. Please try again later.";
            }
        } catch (Exception $e) {
            $login_err = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RateMyTeacher</title>
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
        .login-error {
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
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php 
        if (!empty($login_err)) {
            echo '<div class="login-error">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars(urlencode($_GET['redirect'])) : ''; ?>" method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="help-block"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
            <?php endif; ?>
            <div class="form-group">
                <input type="submit" class="btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            <p><a href="reset-password.php">Forgot your password?</a></p>
        </form>
    </div>
</body>
</html>