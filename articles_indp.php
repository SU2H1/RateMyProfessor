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

// Determine current language for data display
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';

// Article content based on language
$articleTitle = $currentLang == 'ja' ? '効果的な勉強方法: 大学生のための5つのヒント' : 'Effective Study Methods: 5 Tips for College Students';

$articleContent = [];

if ($currentLang == 'ja') {
    $articleContent = [
        'intro' => '大学生活は忙しく、効果的に勉強する方法を見つけることは重要です。このガイドでは、学業成績を向上させるための5つの重要な勉強法を紹介します。',
        'tip1_title' => '1. ポモドーロテクニックを活用する',
        'tip1_content' => '25分間集中して勉強し、5分間の休憩を取るというサイクルを繰り返す方法です。この技術は注意力を維持し、疲労を防ぐのに効果的です。4サイクル後には、より長い15〜30分の休憩を取りましょう。',
        'tip2_title' => '2. アクティブラーニングを実践する',
        'tip2_content' => '単に読むだけでなく、読んだ内容を自分の言葉で要約したり、問題を解いたり、他の人に教えたりすることで、理解度が大幅に向上します。',
        'tip3_title' => '3. 分散学習を行う',
        'tip3_content' => '短時間でも毎日コンスタントに勉強することは、試験前に一気に詰め込むよりもはるかに効果的です。記憶の定着率が高まり、ストレスも軽減されます。',
        'tip4_title' => '4. 学習環境を整える',
        'tip4_content' => '集中できる静かな場所を選び、スマートフォンなどの誘惑を遠ざけましょう。図書館やカフェなど、自分に合った環境を見つけることが重要です。',
        'tip5_title' => '5. 十分な睡眠と適切な休息',
        'tip5_content' => '睡眠不足は集中力や記憶力を低下させます。毎晩7〜8時間の質の良い睡眠を心がけ、適切な休息を取りながら勉強のスケジュールを立てましょう。'
    ];
} else {
    $articleContent = [
        'intro' => 'College life is busy, and finding effective ways to study is crucial. This guide introduces five essential study methods to improve your academic performance.',
        'tip1_title' => '1. Use the Pomodoro Technique',
        'tip1_content' => 'This method involves studying intensely for 25 minutes followed by a 5-minute break. This technique is effective for maintaining focus and preventing fatigue. After four cycles, take a longer break of 15-30 minutes.',
        'tip2_title' => '2. Practice Active Learning',
        'tip2_content' => 'Instead of just reading, try summarizing what you\'ve read in your own words, solving problems, or teaching others. This significantly improves your understanding and retention.',
        'tip3_title' => '3. Implement Spaced Learning',
        'tip3_content' => 'Studying consistently for short periods every day is much more effective than cramming before exams. This improves memory retention and reduces stress.',
        'tip4_title' => '4. Optimize Your Study Environment',
        'tip4_content' => 'Choose a quiet place where you can concentrate and keep distractions like smartphones away. It\'s important to find an environment that works for you, whether it\'s the library, a café, or another space.',
        'tip5_title' => '5. Get Adequate Sleep and Rest',
        'tip5_content' => 'Sleep deprivation decreases concentration and memory. Aim for 7-8 hours of quality sleep each night and plan your study schedule with appropriate rest periods.'
    ];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $articleTitle; ?> - Rate My Teacher</title>
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
            min-width: 80px;
            text-align: center;
            white-space: nowrap;
        }
        
        .language-toggle button.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        /* Article styles */
        .article-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .article-header {
            margin-bottom: 2rem;
        }
        
        .article-title {
            color: #1e3a8a;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .article-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .article-content {
            line-height: 1.6;
        }
        
        .article-content p {
            margin-bottom: 1.5rem;
        }
        
        .tip-section {
            margin-bottom: 2rem;
        }
        
        .tip-title {
            color: #1e3a8a;
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
        }
        
        .tip-content {
            color: #333;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
        
        .footer-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 15px;
            gap: 25px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: underline;
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
                    $menuLabel = $currentLang == 'ja' ? 'メニュー' : 'Menu';
                    $accountLabel = $currentLang == 'ja' ? 'マイアカウント' : 'My Account';
                    $logoutLabel = $currentLang == 'ja' ? 'ログアウト' : 'Logout';
                    $loginLabel = $currentLang == 'ja' ? 'ログイン' : 'Login';
                    $registerLabel = $currentLang == 'ja' ? '登録' : 'Register';
                    $topProfessorsLabel = $currentLang == 'ja' ? '人気の教授' : 'Top Professors';
                    $topCoursesLabel = $currentLang == 'ja' ? '人気のコース' : 'Top Courses';
                    $tipsLabel = $currentLang == 'ja' ? '裏ワザ' : 'Tips and Tricks';
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
                        <?php if ($isLoggedIn): ?>
                            <a href="delete_account.php" class="delete-account"><?php echo $deleteAccountLabel; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;">Rate My Teacher</a></h1>
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
        
        <main>
            <div class="article-container">
                <div class="article-header">
                    <h2 class="article-title"><?php echo $articleTitle; ?></h2>
                    <div class="article-meta">
                        <span><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
                
                <div class="article-content">
                    <p><?php echo $articleContent['intro']; ?></p>
                    
                    <div class="tip-section">
                        <h3 class="tip-title"><?php echo $articleContent['tip1_title']; ?></h3>
                        <p class="tip-content"><?php echo $articleContent['tip1_content']; ?></p>
                    </div>
                    
                    <div class="tip-section">
                        <h3 class="tip-title"><?php echo $articleContent['tip2_title']; ?></h3>
                        <p class="tip-content"><?php echo $articleContent['tip2_content']; ?></p>
                    </div>
                    
                    <div class="tip-section">
                        <h3 class="tip-title"><?php echo $articleContent['tip3_title']; ?></h3>
                        <p class="tip-content"><?php echo $articleContent['tip3_content']; ?></p>
                    </div>
                    
                    <div class="tip-section">
                        <h3 class="tip-title"><?php echo $articleContent['tip4_title']; ?></h3>
                        <p class="tip-content"><?php echo $articleContent['tip4_content']; ?></p>
                    </div>
                    
                    <div class="tip-section">
                        <h3 class="tip-title"><?php echo $articleContent['tip5_title']; ?></h3>
                        <p class="tip-content"><?php echo $articleContent['tip5_content']; ?></p>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>© 2025 Rate My Teacher</p>
            <div class="footer-links">
                <a href="ToS.php?lang=<?php echo $currentLang; ?>">
                    <?php echo $currentLang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
                </a>
                <a href="privacy_policy.php?lang=<?php echo $currentLang; ?>">
                    <?php echo $currentLang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?>
                </a>
                <a href="about_us.php?lang=<?php echo $currentLang; ?>">
                    <?php echo $currentLang == 'ja' ? '私たちについて' : 'About Us'; ?>
                </a>
            </div>
        </footer>
    </div>
    
    <script>
        // Language toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const languageLinks = document.querySelectorAll('.language-toggle a');
            
            languageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // The page will reload with the new language parameter
                    // No need for additional JS logic
                });
            });
        });
    </script>
</body>
</html>