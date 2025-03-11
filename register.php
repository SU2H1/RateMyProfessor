<?php
// register.php - User registration form and processing
require_once 'config.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = $terms_err = $keio_disclaimer_err = "";
$registration_success = false;

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        
        if ($stmt) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindValue(':username', trim($_POST["username"]), SQLITE3_TEXT);
            
            // Attempt to execute the prepared statement
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($row) {
                $username_err = "This username is already taken.";
            } else {
                $username = trim($_POST["username"]);
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        
        // Check if email is from keio.jp domain
        if (!isValidKeioEmail($email)) {
            $email_err = "Please use a valid keio.jp email address.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            
            if ($stmt) {
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);
                
                if ($row) {
                    $email_err = "This email is already registered.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
    
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
        
    // Validate terms of use agreement
    if (!isset($_POST["terms"]) || $_POST["terms"] != "agree") {
        $terms_err = "You must agree to the Terms of Service to register.";
    }
    
    // Validate Keio disclaimer acknowledgment 
    if (!isset($_POST["keio_disclaimer"]) || $_POST["keio_disclaimer"] != "agree") {
        $keio_disclaimer_err = "You must acknowledge that this service is not associated with Keio University.";
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($terms_err) && empty($keio_disclaimer_err)) {
        
        // Generate verification token
        $verification_token = generateToken();
        $token_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Prepare an insert statement
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, verification_token, token_expiry) VALUES (:username, :email, :password, :token, :token_expiry)");
         
        if ($stmt) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':token', $verification_token, SQLITE3_TEXT);
            $stmt->bindValue(':token_expiry', $token_expiry, SQLITE3_TEXT);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Send verification email
                if (sendConfirmationEmail($email, $verification_token)) {
                    $registration_success = true;
                } else {
                    echo "Error sending verification email. Please contact support.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Determine current language
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SU2H1 Rating</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .wrapper {
            width: 400px;
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
            width: 95%;
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

        .checkbox-group{
            margin-bottom: 20px;
        }

        .btn-primary:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
            opacity: 0.7;
            border: 1px solid #bbbbbb;
        }

        .terms-container {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .terms-agreement {
            margin-bottom: 15px;
        }

        .terms-link {
            color: #1e3a8a;
            text-decoration: underline;
        }

        .terms-link:hover {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        
        <?php if ($registration_success): ?>
            <div class="success-message">
                <p>Registration successful! Please check your email to verify your account.</p>
                <p>A verification link has been sent to your keio.jp email address.</p>
                <p><a href="login.php">Click here to login</a> after verifying your email.</p>
            </div>
        <?php else: ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                    <span class="help-block"><?php echo $username_err; ?></span>
                </div>    
                <div class="form-group">
                    <label>Email (must be a keio.jp address)</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                    <span class="help-block"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control">
                    <span class="help-block"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                    <span class="help-block"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="terms-agreement">
                    <input type="checkbox" name="keio_disclaimer" id="keio_disclaimer" value="agree">
                    <label for="keio_disclaimer">I hereby understand that this service is not associated with Keio University and is a personal project.</label>
                    <span class="help-block"><?php echo $keio_disclaimer_err; ?></span>
                </div>
                <div class="terms-agreement">
                    <input type="checkbox" name="terms" id="terms" value="agree">
                    <label for="terms">I agree to the <a href="ToS.php?lang=<?php echo $currentLang; ?>" class="terms-link" target="_blank"><?php echo $currentLang == 'ja' ? '利用規約' : 'Terms of Service'; ?></a></label>
                    <span class="help-block"><?php echo $terms_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn-primary" id='submit-btn' value="Submit" disabled>
                    <input type="reset" class="btn-default" value="Reset">
                </div>

                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </form>
        <?php endif; ?>
    </div> 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var termsCheckbox = document.getElementById('terms');
            var keioDisclaimerCheckbox = document.getElementById('keio_disclaimer');
            var submitButton = document.getElementById('submit-btn');
            
            function updateSubmitButton() {
                // Both checkboxes must be checked to enable the submit button
                submitButton.disabled = !(termsCheckbox.checked && keioDisclaimerCheckbox.checked);
            }
            
            if(termsCheckbox && keioDisclaimerCheckbox && submitButton) {
                termsCheckbox.addEventListener('change', updateSubmitButton);
                keioDisclaimerCheckbox.addEventListener('change', updateSubmitButton);
            } else {
                console.error('Cannot find one or more required elements');
            }
        });
    </script>   
</body>
</html>