<?php
// verify.php - Email verification handler
require_once 'config.php';

// For debugging - comment out in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force PHP to display errors instead of blank screen
ob_start();

// We're using the generateToken from config.php

// Function to send verification email
function sendVerificationEmail($email, $token) {
    $verification_link = SITE_URL . "/verify.php?email=" . urlencode($email) . "&token=" . $token;
    
    $to = $email;
    $subject = "Email Verification - SU2H1 Rating";
    
    // Set content-type header for sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_NAME . " <" . EMAIL_FROM . ">" . "\r\n";
    
    // Email template
    $message = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Verification</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                padding: 0;
                border: 1px solid #ddd;
                border-radius: 5px;
                background-color: #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background-color: #1e3a8a;
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .header h2 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 30px;
                background-color: #fff;
            }
            .button {
                display: inline-block;
                background-color: #1e3a8a;
                color: white !important;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 4px;
                margin: 25px 0;
                font-weight: bold;
            }
            .button:hover {
                background-color: #152c6c;
            }
            .link-container {
                word-break: break-all;
                margin: 15px 0;
                padding: 10px;
                background-color: #f8f9fa;
                border-radius: 4px;
                border: 1px solid #e9ecef;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding: 20px;
                color: #777;
                font-size: 0.9em;
                border-top: 1px solid #eee;
            }
            p {
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>SU2H1 Rating - Email Verification</h2>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>Thank you for registering with SU2H1 Rating. Please click the button below to verify your email address:</p>
                
                <p style="text-align: center;">
                    <a href="' . $verification_link . '" class="button">Verify Your Email</a>
                </p>
                
                <p>If the button above doesn\'t work, copy and paste the following link into your browser:</p>
                <div class="link-container">
                    <p style="font-size: 13px;">' . $verification_link . '</p>
                </div>
                
                <p>This verification link will expire in 5 minutes.</p>
                
                <p>If you didn\'t register for an account with us, please ignore this email.</p>
                
                <p>Best regards,<br>SU2H1 Rating Team</p>
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' SU2H1 Rating. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

$verification_status = '';

// Check if email and token parameters exist
if (isset($_GET['email']) && isset($_GET['token'])) {
    try {
        $email = $_GET['email'];
        $token = $_GET['token'];
        
        // For SQLite3, we need to use a different approach than mysqli
        $sql = "SELECT id, verification_token, token_expiry, is_verified FROM users WHERE email = :email";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Bind parameters
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            
            // Execute the statement
            $result = $stmt->execute();
            
            // Check if we found a user
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $id = $row['id'];
                $db_token = $row['verification_token'];
                $token_expiry = $row['token_expiry'];
                $is_verified = $row['is_verified'];
                
                echo "<pre style='display:none;'>Found user: ID=$id, Token=$db_token, Expiry=$token_expiry, Verified=$is_verified</pre>";
                    // Check if already verified
                if ($is_verified == 1) {
                    $verification_status = "Your account is already verified. You can now login.";
                } 
                // Check if token matches and has not expired
                elseif ($token == $db_token && (empty($token_expiry) || strtotime($token_expiry) > time())) {
                    // Update user to verified status
                    $update_sql = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = :id";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        
                        $result = $update_stmt->execute();
                        if ($result) {
                            $verification_status = "Your email has been verified successfully! You can now login.";
                        } else {
                            $verification_status = "Oops! Something went wrong updating verified status. Error: " . $conn->lastErrorMsg();
                        }
                    } else {
                        $verification_status = "Failed to prepare update statement.";
                    }
                } 
                // Token expired
                elseif (!empty($token_expiry) && strtotime($token_expiry) <= time()) {
                    // Generate new token
                    $new_token = generateToken();
                    $new_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Update with new token
                    $update_sql = "UPDATE users SET verification_token = :token, token_expiry = :expiry WHERE id = :id";
                    
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) {
                        $update_stmt->bindValue(':token', $new_token, SQLITE3_TEXT);
                        $update_stmt->bindValue(':expiry', $new_expiry, SQLITE3_TEXT);
                        $update_stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                        
                        $result = $update_stmt->execute();
                        if ($result) {
                            // Send new verification email
                            if (sendVerificationEmail($email, $new_token)) {
                                $verification_status = "Your verification link has expired. A new verification link has been sent to your email.";
                            } else {
                                $verification_status = "Error sending new verification email. Please contact support.";
                            }
                        } else {
                            $verification_status = "Oops! Something went wrong updating token. Error: " . $conn->lastErrorMsg();
                        }
                    } else {
                        $verification_status = "Failed to prepare update statement for new token.";
                    }
                } 
                // Invalid token
                else {
                    $verification_status = "Invalid verification token.";
                }
            } else {
                $verification_status = "No account found with that email address.";
            }
        } else {
            $verification_status = "Failed to prepare statement. Error: " . $conn->lastErrorMsg();
        }
    } catch (Exception $e) {
        $verification_status = "Error: " . $e->getMessage();
    } finally {
        // No need to close SQLite3 connection - it's automatically closed when the variable is unset
    }
} else {
    $verification_status = "Invalid verification link.";
}
// Print any buffered output
$output = ob_get_clean();
if ($output) {
    echo "<pre>Debug output: " . htmlspecialchars($output) . "</pre>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - SU2H1 Rating</title>
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
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #152c6c;
        }
        .btn-container {
            text-align: center;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Email Verification</h2>
        
        <?php if (strpos($verification_status, "successfully") !== false || strpos($verification_status, "already verified") !== false): ?>
            <div class="success-message">
                <?php echo $verification_status; ?>
            </div>
            <div class="btn-container">
                <a href="login.php" class="btn-primary">Go to Login</a>
            </div>
        <?php elseif (strpos($verification_status, "new verification link") !== false): ?>
            <div class="success-message">
                <?php echo $verification_status; ?>
            </div>
            <div class="btn-container">
                <p>Please check your email inbox (and spam folder) for the new verification link.</p>
            </div>
        <?php else: ?>
            <div class="error-message">
                <?php echo $verification_status; ?>
            </div>
            <div class="btn-container">
                <a href="register.php" class="btn-primary">Back to Registration</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>