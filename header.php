<?php
// Default page title if not set
if (!isset($page_title)) {
    $page_title = "Rate My Teacher";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Rate My Teacher</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f0f2f5;
        }
        
        /* Header Styles */
        header {
            background-color: #1e3a8a;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .site-logo {
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .site-logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        /* Navigation Styles */
        .main-nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }
        
        .main-nav a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .main-nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .main-nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* User Menu Styles */
        .user-menu {
            position: relative;
        }
        
        .user-menu-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 16px;
            font-weight: 500;
        }
        
        .user-menu-btn i {
            margin-left: 5px;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            min-width: 180px;
            display: none;
            z-index: 100;
        }
        
        .user-dropdown.show {
            display: block;
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.2s;
        }
        
        .user-dropdown a:hover {
            background-color: #f8f9fa;
        }
        
        .user-dropdown a.danger {
            color: #dc3545;
        }
        
        .user-dropdown .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 5px 0;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-nav {
                position: fixed;
                top: 63px;
                left: 0;
                width: 100%;
                background-color: #1e3a8a;
                padding: 15px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                display: none;
                z-index: 99;
            }
            
            .main-nav.show {
                display: block;
            }
            
            .main-nav ul {
                flex-direction: column;
                gap: 10px;
            }
            
            .main-nav a {
                display: block;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="site-logo">
                <i class="fas fa-star"></i>
                Rate My Teacher
            </a>
            
            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="home.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="search.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active' : ''; ?>">Search</a></li>
                    <li><a href="tipsandtricks.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tipsandtricks.php' ? 'active' : ''; ?>">Tips</a></li>
                </ul>
            </nav>
            
            <div class="user-menu">
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <button class="user-menu-btn" id="user-menu-btn">
                        <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="account-section.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account-section.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> My Account
                        </a>
                        <a href="account-section.php#my-reviews">
                            <i class="fas fa-star"></i> My Reviews
                        </a>
                        <div class="divider"></div>
                        <a href="logout.php" class="danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="login.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">Login</a>
                        <a href="register.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="main-container">