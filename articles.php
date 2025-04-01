<?php
// Include session configuration before starting the session
require_once 'session_config.php';

// Now start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Get username if logged in
$username = $isLoggedIn ? htmlspecialchars($_SESSION["username"]) : '';

// Include database configuration
require_once 'config.php';

// Determine current language for data display
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - Rate My Teacher</title>
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
        
        .language-toggle {
            display: flex;
            align-items: center;
        }
        
        .language-toggle button {
            background: none;
            border: 1px solid white;
            color: white;
            padding: 0.5rem 1rem;
            margin-left: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            min-width: 80px; /* Set a fixed minimum width */
            text-align: center; /* Center the text */
            white-space: nowrap; /* Prevent text wrapping */
        }
        
        .language-toggle button.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        .content-container {
            flex: 1;
            padding: 2rem;
        }
        
        /* Article Cards Styling */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .article-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .article-image {
            width: 100%;
            height: 180px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 14px;
        }
        
        .article-content {
            padding: 1.25rem;
        }
        
        .article-title {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            font-weight: bold;
            color: #1e3a8a;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.85rem;
        }
        
        .article-author {
            display: flex;
            align-items: center;
        }
        
        .article-date {
            font-style: italic;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="dropdown">
                    <?php 
                    // Determine current language
                    $currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';
                    $menuLabel = $currentLang == 'ja' ? 'メニュー' : 'Menu';
                    $accountLabel = $currentLang == 'ja' ? 'マイアカウント' : 'My Account';
                    $logoutLabel = $currentLang == 'ja' ? 'ログアウト' : 'Logout';
                    $loginLabel = $currentLang == 'ja' ? 'ログイン' : 'Login';
                    $registerLabel = $currentLang == 'ja' ? '登録' : 'Register';
                    $topProfessorsLabel = $currentLang == 'ja' ? '人気の教授' : 'Top Professors';
                    $topCoursesLabel = $currentLang == 'ja' ? '人気のコース' : 'Top Courses';
                    $tipsLabel = $currentLang == 'ja' ? '裏ワザ' : 'Tips and Tricks';
                    $articlesLabel = $currentLang == 'ja' ? '記事' : 'Articles';
                    $deleteAccountLabel = $currentLang == 'ja' ? 'アカウント削除' : 'Delete Account';
                    ?>
                    <button class="dropbtn"><?php echo $menuLabel; ?></button>
                    <div class="dropdown-content">
                        <?php if ($isLoggedIn): ?>
                            <a href="account-section.php" id="account-link"><?php echo $accountLabel; ?></a>
                            <a href="logout.php"><?php echo $logoutLabel; ?></a>
                        <?php else: ?>
                            <a href="login.php" id="account-link"><?php echo $loginLabel; ?></a>
                            <a href="register.php"><?php echo $registerLabel; ?></a>
                        <?php endif; ?>
                        <a href="home.php#popular-professors"><?php echo $topProfessorsLabel; ?></a>
                        <a href="home.php#top-courses"><?php echo $topCoursesLabel; ?></a>
                        <a href="tipsandtricks.php"><?php echo $tipsLabel; ?></a>
                        <a href="articles.php"><?php echo $articlesLabel; ?></a>
                        <?php if ($isLoggedIn): ?>
                            <a href="delete_account.php" class="delete-account"><?php echo $deleteAccountLabel; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;"><?php echo $currentLang == 'ja' ? 'Rate My Teacher' : 'Rate My Teacher'; ?></a></h1>
                </div>
            </div>
            
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <span class="welcome-message"><?php echo $currentLang == 'ja' ? 'ようこそ、' : 'Welcome, '; ?><?php echo $username; ?>!</span>
                <?php endif; ?>
                <div class="auth-links">
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php"><?php echo $logoutLabel; ?></a>
                    <?php else: ?>
                        <a href="login.php"><?php echo $loginLabel; ?></a>
                        <a href="register.php"><?php echo $registerLabel; ?></a>
                    <?php endif; ?>
                </div>
                <div class="language-toggle">
                    <a href="?lang=en" class="<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? 'active' : ''; ?>" 
                    style="display:inline-block; width:80px; text-align:center; padding:8px 0; margin-right:5px; 
                            background:<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? 'white' : 'transparent'; ?>; 
                            color:<?php echo (!isset($_COOKIE['language']) || $_COOKIE['language'] == 'en') ? '#1e3a8a' : 'white'; ?>; 
                            text-decoration:none; border:1px solid white; border-radius:4px;"
                    onclick="document.cookie='language=en; path=/; max-age=2592000'; return true;">
                    English
                    </a>
                    <a href="?lang=ja" class="<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? 'active' : ''; ?>" 
                    style="display:inline-block; width:80px; text-align:center; padding:8px 0; 
                            background:<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? 'white' : 'transparent'; ?>; 
                            color:<?php echo (isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja') ? '#1e3a8a' : 'white'; ?>; 
                            text-decoration:none; border:1px solid white; border-radius:4px;"
                    onclick="document.cookie='language=ja; path=/; max-age=2592000'; return true;">
                    日本語
                    </a>
                </div>
            </div>
        </header>
        
        <div class="content-container">
            <!-- Content area for the articles page -->
            <h1><?php echo $currentLang == 'ja' ? '記事' : 'Articles'; ?></h1>
            
            <div class="articles-grid">
                <!-- Article Card 1 -->
                <a href="articles_template.php?id=1&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? '効果的な学習戦略：総合政策学部の成功への鍵' : 'Effective Study Strategies: Keys to Success in Policy Management'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 田中 啓介' : 'By: Keisuke Tanaka'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年3月15日' : 'Mar 15, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Article Card 2 -->
                <a href="articles_template.php?id=2&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? '慶應義塾大学のキャンパスライフを最大限に活用する方法' : 'How to Make the Most of Your Campus Life at Keio University'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 佐藤 優香' : 'By: Yuka Sato'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年3月10日' : 'Mar 10, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Article Card 3 -->
                <a href="articles_template.php?id=3&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? '臨床心理学者になるためのキャリアパス: 先輩からのアドバイス' : 'Career Path to Becoming a Clinical Psychologist: Advice from Seniors'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 山田 健太' : 'By: Kenta Yamada'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年3月5日' : 'Mar 5, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Article Card 4 -->
                <a href="articles_template.php?id=4&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? 'インターンシップの見つけ方と最大限に活用する方法' : 'How to Find and Make the Most of Internships'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 鈴木 大輔' : 'By: Daisuke Suzuki'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年2月28日' : 'Feb 28, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Article Card 5 -->
                <a href="articles_template.php?id=5&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? '留学の準備: 計画から出発までのガイド' : 'Preparing for Study Abroad: A Guide from Planning to Departure'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 小林 美咲' : 'By: Misaki Kobayashi'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年2月20日' : 'Feb 20, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
                
                <!-- Article Card 6 -->
                <a href="articles_template.php?id=6&lang=<?php echo $currentLang; ?>" class="article-card">
                    <div class="article-image">
                        <span><?php echo $currentLang == 'ja' ? '画像プレースホルダー' : 'Image Placeholder'; ?></span>
                    </div>
                    <div class="article-content">
                        <h3 class="article-title"><?php echo $currentLang == 'ja' ? '心理学研究の方法論: 学部生のための基本ガイド' : 'Research Methods in Psychology: A Basic Guide for Undergraduates'; ?></h3>
                        <div class="article-meta">
                            <div class="article-author">
                                <?php echo $currentLang == 'ja' ? '著者: 伊藤 直樹' : 'By: Naoki Ito'; ?>
                            </div>
                            <div class="article-date">
                                <?php echo $currentLang == 'ja' ? '2025年2月15日' : 'Feb 15, 2025'; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
            <p>2025 Rate My Teacher</p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; margin-top: 15px; gap: 25px;">
                <a href="ToS.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
                    <?php echo $currentLang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
                </a>
                <a href="privacy_policy.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
                    <?php echo $currentLang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?>
                </a>
                <a href="about_us.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
                    <?php echo $currentLang == 'ja' ? '私たちについて' : 'About Us'; ?>
                </a>
            </div>
        </footer>
    </div>
</body>
</html>