<?php
// register.php - User registration form and processing
require_once 'config.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = $terms_err = $keio_disclaimer_err = "";
$registration_success = false;
$email_valid = false;

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

// Get browser language
function getBrowserLanguage() {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        return $browserLang == 'ja' ? 'ja' : 'en';
    }
    return 'en'; // Default to English
}

// Check for URL language parameter
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ja' ? 'ja' : 'en';
    setcookie('language', $lang, time() + (86400 * 30), "/"); // 30 days
    $_COOKIE['language'] = $lang; // Set for current request
}
// Set language preference if not already set
elseif (!isset($_COOKIE['language'])) {
    $browserLang = getBrowserLanguage();
    setcookie('language', $browserLang, time() + (86400 * 30), "/"); // 30 days
    $_COOKIE['language'] = $browserLang; // Set for current request
}

// Determine current language
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';

// Set language-specific text
$textSignUp = $currentLang == 'ja' ? 'アカウント登録' : 'Sign Up';
$textPleaseForm = $currentLang == 'ja' ? 'アカウント作成のためにフォームに記入してください。' : 'Please fill this form to create an account.';
$textRegSuccess = $currentLang == 'ja' ? '登録完了！メールを確認してアカウントを認証してください。' : 'Registration successful! Please check your email to verify your account.';
$textVerifLink = $currentLang == 'ja' ? '認証リンクがkeio.jpメールアドレスに送信されました。' : 'A verification link has been sent to your keio.jp email address.';
$textClickLogin = $currentLang == 'ja' ? 'メール認証後、<a href="login.php">こちらからログイン</a>してください。' : '<a href="login.php">Click here to login</a> after verifying your email.';
$textUsername = $currentLang == 'ja' ? 'ユーザー名' : 'Username';
$textUsernamePlease = $currentLang == 'ja' ? 'ユーザー名を入力してください。' : 'Please enter a username.';
$textUsernameTaken = $currentLang == 'ja' ? 'このユーザー名は既に使用されています。' : 'This username is already taken.';
$textEmail = $currentLang == 'ja' ? 'メール（keio.jpのメールアドレスが必要です）' : 'Email (must be a keio.jp address)';
$textEmailPlease = $currentLang == 'ja' ? 'メールアドレスを入力してください。' : 'Please enter an email.';
$textValidEmail = $currentLang == 'ja' ? '有効なkeio.jpメールアドレスを使用してください。' : 'Please use a valid keio.jp email address.';
$textEmailRegistered = $currentLang == 'ja' ? 'このメールアドレスは既に登録されています。' : 'This email is already registered.';
$textPassword = $currentLang == 'ja' ? 'パスワード' : 'Password';
$textPasswordPlease = $currentLang == 'ja' ? 'パスワードを入力してください。' : 'Please enter a password.';
$textPasswordLength = $currentLang == 'ja' ? 'パスワードは8文字以上必要です。' : 'Password must have at least 8 characters.';
$textConfirmPassword = $currentLang == 'ja' ? 'パスワード（確認）' : 'Confirm Password';
$textConfirmPlease = $currentLang == 'ja' ? 'パスワードを確認してください。' : 'Please confirm password.';
$textPasswordMatch = $currentLang == 'ja' ? 'パスワードが一致しません。' : 'Password did not match.';
$textKeioDisclaimer = $currentLang == 'ja' ? 'このサービスは慶應義塾大学とは関連がなく、個人プロジェクトであることを理解しています。' : 'I hereby understand that this service is not associated with Keio University and is a personal project.';
$textAgreeToS = $currentLang == 'ja' ? '私は<a href="ToS.php?lang=' . $currentLang . '" class="terms-link" target="_blank">利用規約</a>に同意します' : 'I agree to the <a href="ToS.php?lang=' . $currentLang . '" class="terms-link" target="_blank">Terms of Service</a>';
$textTermsErr = $currentLang == 'ja' ? '登録するには利用規約に同意する必要があります。' : 'You must agree to the Terms of Service to register.';
$textDisclaimerErr = $currentLang == 'ja' ? 'このサービスが慶應義塾大学と関連していないことを確認する必要があります。' : 'You must acknowledge that this service is not associated with Keio University.';
$textSubmit = $currentLang == 'ja' ? '送信' : 'Submit';
$textReset = $currentLang == 'ja' ? 'リセット' : 'Reset';
$textHaveAccount = $currentLang == 'ja' ? 'すでにアカウントをお持ちですか？ <a href="login.php">こちらからログイン</a>してください。' : 'Already have an account? <a href="login.php">Login here</a>.';
$textErr = $currentLang == 'ja' ? 'エラーが発生しました。後でもう一度お試しください。' : 'Oops! Something went wrong. Please try again later.';
$textVerifErr = $currentLang == 'ja' ? '認証メールの送信中にエラーが発生しました。サポートにお問い合わせください。' : 'Error sending verification email. Please contact support.';
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $textSignUp; ?> - Rate My Teacher</title>
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
        
        .language-toggle {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .language-toggle a {
            display: inline-block;
            width: 80px;
            text-align: center;
            padding: 8px 0;
            text-decoration: none;
            border: 1px solid #1e3a8a;
            border-radius: 4px;
            margin-left: 5px;
        }
        
        .language-toggle a.active {
            background-color: #1e3a8a;
            color: white;
        }
        
        .language-toggle a:not(.active) {
            background-color: white;
            color: #1e3a8a;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="language-toggle">
            <a href="?lang=en" class="<?php echo $currentLang == 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=ja" class="<?php echo $currentLang == 'ja' ? 'active' : ''; ?>">日本語</a>
        </div>
        
        <h2><?php echo $textSignUp; ?></h2>
        <p><?php echo $textPleaseForm; ?></p>
        
        <?php if ($registration_success): ?>
            <div class="success-message">
                <p><?php echo $textRegSuccess; ?></p>
                <p><?php echo $textVerifLink; ?></p>
                <p><?php echo $textClickLogin; ?></p>
            </div>
        <?php else: ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label><?php echo $textUsername; ?></label>
                    <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                    <span class="help-block"><?php 
                        if (!empty($username_err)) {
                            echo ($username_err == "Please enter a username.") ? $textUsernamePlease : 
                                 (($username_err == "This username is already taken.") ? $textUsernameTaken : $username_err);
                        }
                    ?></span>
                </div>    
                <div class="form-group">
                    <label><?php echo $textEmail; ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
                    <span class="help-block"><?php 
                        if (!empty($email_err)) {
                            echo ($email_err == "Please enter an email.") ? $textEmailPlease : 
                                 (($email_err == "Please use a valid keio.jp email address.") ? $textValidEmail : 
                                 (($email_err == "This email is already registered.") ? $textEmailRegistered : $email_err));
                        }
                    ?></span>
                </div>
                <div class="form-group">
                    <label><?php echo $textPassword; ?></label>
                    <input type="password" name="password" class="form-control">
                    <span class="help-block"><?php 
                        if (!empty($password_err)) {
                            echo ($password_err == "Please enter a password.") ? $textPasswordPlease : 
                                 (($password_err == "Password must have at least 8 characters.") ? $textPasswordLength : $password_err);
                        }
                    ?></span>
                </div>
                <div class="form-group">
                    <label><?php echo $textConfirmPassword; ?></label>
                    <input type="password" name="confirm_password" class="form-control">
                    <span class="help-block"><?php 
                        if (!empty($confirm_password_err)) {
                            echo ($confirm_password_err == "Please confirm password.") ? $textConfirmPlease : 
                                 (($confirm_password_err == "Password did not match.") ? $textPasswordMatch : $confirm_password_err);
                        }
                    ?></span>
                </div>
                <div class="terms-agreement">
                    <input type="checkbox" name="keio_disclaimer" id="keio_disclaimer" value="agree">
                    <label for="keio_disclaimer"><?php echo $textKeioDisclaimer; ?></label>
                    <span class="help-block"><?php echo !empty($keio_disclaimer_err) ? $textDisclaimerErr : ''; ?></span>
                </div>
                <div class="terms-agreement">
                    <input type="checkbox" name="terms" id="terms" value="agree">
                    <label for="terms"><?php echo $textAgreeToS; ?></label>
                    <span class="help-block"><?php echo !empty($terms_err) ? $textTermsErr : ''; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn-primary" id='submit-btn' value="<?php echo $textSubmit; ?>" disabled>
                    <input type="reset" class="btn-default" value="<?php echo $textReset; ?>">
                </div>

                <p><?php echo $textHaveAccount; ?></p>
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