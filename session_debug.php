<?php
// Include session configuration before starting the session
require_once 'session_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Collect session information
$session_info = [
    'Session ID' => session_id(),
    'Session Status' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not active',
    'PHP Session Path' => session_save_path(),
    'Session Cookies' => isset($_COOKIE[session_name()]) ? 'Yes (' . $_COOKIE[session_name()] . ')' : 'No',
    'Logged In Status' => isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true ? 'Logged in' : 'Not logged in',
    'Session Variables' => print_r($_SESSION, true),
    'Cookie Information' => print_r($_COOKIE, true),
    'Server Name' => $_SERVER['SERVER_NAME'],
    'Request URI' => $_SERVER['REQUEST_URI'],
    'PHP Version' => phpversion(),
    'Session Config' => [
        'session.save_handler' => ini_get('session.save_handler'),
        'session.save_path' => ini_get('session.save_path'),
        'session.use_cookies' => ini_get('session.use_cookies'),
        'session.use_only_cookies' => ini_get('session.use_only_cookies'),
        'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'session.cookie_path' => ini_get('session.cookie_path'),
        'session.cookie_domain' => ini_get('session.cookie_domain'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly'),
        'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    ]
];

// Output as readable HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        h2 {
            color: #444;
            margin-top: 30px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .login-actions {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .button.red {
            background-color: #f44336;
        }
        .test-form {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="login-actions">
        <h3>Login Actions</h3>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <p>You are currently logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <a href="logout.php" class="button red">Logout</a>
        <?php else: ?>
            <p>You are not currently logged in.</p>
            <a href="login.php" class="button">Login</a>
        <?php endif; ?>
    </div>
    
    <h2>Session Information</h2>
    <table>
        <?php foreach ($session_info as $key => $value): ?>
            <?php if (!is_array($value)): ?>
                <tr>
                    <th><?php echo htmlspecialchars($key); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    
    <h2>Session Configuration</h2>
    <table>
        <?php foreach ($session_info['Session Config'] as $key => $value): ?>
            <tr>
                <th><?php echo htmlspecialchars($key); ?></th>
                <td><?php echo htmlspecialchars($value); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>Session Variables</h2>
    <pre><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
    
    <h2>Cookies</h2>
    <pre><?php echo htmlspecialchars(print_r($_COOKIE, true)); ?></pre>

    <div class="test-form">
        <h3>Test Session Write</h3>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="text" name="test_value" placeholder="Enter a test value">
            <button type="submit" name="write_session">Write to Session</button>
        </form>
        
        <?php
        // Handle test session write
        if (isset($_POST['write_session']) && isset($_POST['test_value'])) {
            $_SESSION['test_value'] = $_POST['test_value'];
            echo '<p>Value written to session. Refresh page to confirm.</p>';
        }
        ?>
    </div>
</body>
</html>