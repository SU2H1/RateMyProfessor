<?php
// account.php - User account management page
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Get additional user info if needed
$additional_info = [];
$sql = "SELECT created_at FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION["id"]);
    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($created_at);
            $stmt->fetch();
            $_SESSION["created_at"] = $created_at;
        }
    }
    $stmt->close();
}

// Get review count
$review_count = 0;
$sql = "SELECT COUNT(*) as review_count FROM reviews WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION["id"]);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $review_count = $row["review_count"];
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - SU2H1 Rating</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        header {
            background-color: #1e3a8a;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            width: 25%;
        }
        
        .header-center {
            width: 50%;
            text-align: center;
        }
        
        .header-right {
            width: 25%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .auth-links {
            margin-right: 15px;
        }
        
        .auth-links a, .auth-links span {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 14px;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .welcome-message {
            margin-right: 15px;
            font-size: 14px;
        }
        
        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background-color: transparent;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .dropbtn::after {
            content: "▼";
            font-size: 12px;
            margin-left: 5px;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .delete-account {
            color: #dc3545 !important;
            border-top: 1px solid #eee;
            margin-top: 5px;
            padding-top: 10px;
        }
        
        .delete-account:hover {
            background-color: #ffebee !important;
        }
        
        main {
            flex: 1;
            padding: 2rem;
        }
        
        .content-box {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .content-box h2 {
            color: #1e3a8a;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .user-info {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .user-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .user-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stats-container {
            display: flex;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-box {
            flex: 1;
            min-width: 150px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .stat-box h4 {
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e3a8a;
        }
        
        .account-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .account-option {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: all 0.2s ease;
        }
        
        .account-option:hover {
            background-color: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .account-option h4 {
            margin-top: 0;
            color: #1e3a8a;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .account-option p {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .account-option a {
            display: inline-block;
            padding: 10px 16px;
            background-color: #1e3a8a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .account-option a:hover {
            background-color: #152c6c;
        }
        
        .account-option.danger {
            border-color: #ffcdd2;
        }
        
        .account-option.danger h4 {
            color: #c62828;
        }
        
        .account-option.danger a {
            background-color: #dc3545;
        }
        
        .account-option.danger a:hover {
            background-color: #c82333;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
            }
            
            .stat-box {
                margin-bottom: 10px;
            }
            
            .account-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="dropdown">
                    <button class="dropbtn">Menu</button>
                    <div class="dropdown-content">
                        <a href="account.php">My Account</a>
                        <a href="logout.php">Logout</a>
                        <a href="delete_account.php" class="delete-account">Delete Account</a>
                        <a href="index.php#popular-professors">Popular Professors</a>
                        <a href="index.php#top-courses">Top Courses</a>
                        <a href="index.php#sfc-tips">SFC Tips and Tricks</a>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;">Rate My Teacher</a></h1>
                    <p>SU2H1</p>
                </div>
            </div>
            
            <div class="header-right">
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
                <div class="auth-links">
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </header>
        
        <main>
            <div class="content-box">
                <h2>My Account</h2>
                
                <div class="user-info">
                    <h3>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h3>
                    <p>Email: <?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                    <p>Member since: <?php echo date('F j, Y', strtotime($_SESSION["created_at"])); ?></p>
                </div>
                
                <div class="stats-container">
                    <div class="stat-box">
                        <h4>Total Reviews</h4>
                        <div class="number"><?php echo $review_count; ?></div>
                    </div>
                    <div class="stat-box">
                        <h4>Helpful Votes</h4>
                        <div class="number">0</div>
                    </div>
                    <div class="stat-box">
                        <h4>Account Status</h4>
                        <div class="number" style="font-size: 1.5rem; color: #28a745;">Active</div>
                    </div>
                </div>
                
                <h3 style="margin-bottom: 1rem; color: #1e3a8a;">Account Management</h3>
                
                <div class="account-options">
                    <div class="account-option">
                        <h4>Profile Settings</h4>
                        <p>Update your personal information, change your password, and manage your profile settings.</p>
                        <a href="#">Edit Profile</a>
                    </div>
                    
                    <div class="account-option">
                        <h4>My Reviews</h4>
                        <p>View and manage all your course and professor reviews. Edit or delete past reviews.</p>
                        <a href="#">View Reviews</a>
                    </div>
                    
                    <div class="account-option">
                        <h4>Notification Settings</h4>
                        <p>Manage your email preferences and notifications for review responses and site updates.</p>
                        <a href="#">Manage Notifications</a>
                    </div>
                    
                    <div class="account-option danger">
                        <h4>Delete Account</h4>
                        <p>Permanently delete your account and anonymize your reviews. This action cannot be undone.</p>
                        <a href="delete_account.php">Delete Account</a>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Rate My Teacher - SU2H1. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>