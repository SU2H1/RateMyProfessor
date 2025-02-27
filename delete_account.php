<?php
// delete_account.php - Account deletion handler
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

$password_err = "";
$deletion_err = "";
$success_message = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password to confirm account deletion.";
    } else {
        $password = trim($_POST["password"]);
        
        // Prepare a select statement
        $sql = "SELECT id, password FROM users WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("i", $_SESSION["id"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if user exists
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, proceed with account deletion
                            
                            // First, anonymize any reviews or ratings
                            $anonymize_reviews_sql = "UPDATE reviews SET user_id = 0, username = 'Deleted User' WHERE user_id = ?";
                            if ($anonymize_stmt = $conn->prepare($anonymize_reviews_sql)) {
                                $anonymize_stmt->bind_param("i", $_SESSION["id"]);
                                $anonymize_stmt->execute();
                                $anonymize_stmt->close();
                            }
                            
                            // Delete the user account
                            $delete_user_sql = "DELETE FROM users WHERE id = ?";
                            if ($delete_stmt = $conn->prepare($delete_user_sql)) {
                                $delete_stmt->bind_param("i", $_SESSION["id"]);
                                
                                if ($delete_stmt->execute()) {
                                    // Account successfully deleted
                                    // Destroy the session and redirect to home page
                                    session_destroy();
                                    $success_message = "Your account has been successfully deleted.";
                                } else {
                                    $deletion_err = "Something went wrong. Please try again later.";
                                }
                                
                                $delete_stmt->close();
                            }
                        } else {
                            // Password is not valid
                            $password_err = "The password you entered is not valid.";
                        }
                    }
                } else {
                    // User doesn't exist (shouldn't happen, but just in case)
                    $deletion_err = "User account not found.";
                }
            } else {
                $deletion_err = "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - SU2H1 Rating</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            width: 100%;
            max-width: 600px;
            padding: 30px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1e3a8a;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .warning-box {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #ffeeba;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .help-block {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        .mb-3 {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Delete Account</h2>
        
        <?php if(!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="btn-secondary">Return to Home</a>
            </div>
        <?php else: ?>
            <div class="warning-box">
                <p><strong>Warning:</strong> This action cannot be undone. Your account will be permanently deleted.</p>
                <p>Your reviews and ratings will remain on the site but will be anonymized.</p>
            </div>
            
            <?php if(!empty($deletion_err)): ?>
                <div class="error-message"><?php echo $deletion_err; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                    <label>Enter your password to confirm deletion</label>
                    <input type="password" name="password" class="form-control">
                    <span class="help-block"><?php echo $password_err; ?></span>
                </div>
                
                <div class="btn-container">
                    <a href="index.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-danger">Delete My Account</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>