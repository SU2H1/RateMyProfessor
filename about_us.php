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
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title><?php echo $currentLang == 'ja' ? '私たちについて - Gaku Neko' : 'About Us - Gaku Neko'; ?></title>
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
        
        .language-toggle a {
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
            text-decoration: none;
            display: inline-block;
        }
        
        .language-toggle a.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        main {
            flex: 1;
            padding: 2rem;
        }
        
        .about-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .about-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .about-header h1 {
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }
        
        .about-section {
            margin-bottom: 2rem;
        }
        
        .about-section h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .about-section p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .contact-info {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 6px;
            margin-top: 2rem;
        }
        
        .disclaimer {
            background-color: #fff8e1;
            padding: 1rem;
            border-left: 4px solid #ffc107;
            margin-bottom: 2rem;
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
                    // Determine labels based on current language
                    $menuLabel = $currentLang == 'ja' ? 'メニュー' : 'Menu';
                    $accountLabel = $currentLang == 'ja' ? 'マイアカウント' : 'My Account';
                    $logoutLabel = $currentLang == 'ja' ? 'ログアウト' : 'Logout';
                    $loginLabel = $currentLang == 'ja' ? 'ログイン' : 'Login';
                    $registerLabel = $currentLang == 'ja' ? '登録' : 'Register';
                    $topProfessorsLabel = $currentLang == 'ja' ? '人気の教授' : 'Top Professors';
                    $professorsLabel = $currentLang == 'ja' ? '教授一覧' : 'Professors';
                    $topCoursesLabel = $currentLang == 'ja' ? '人気のコース' : 'Top Courses';
                    $coursesLabel = $currentLang == 'ja' ? 'コース一覧' : 'Courses';
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
                        <a href="ratemyteacher-instructions.php?section=professors&lang=<?php echo $currentLang; ?>"><?php echo $professorsLabel; ?></a>
                        <a href="home.php#top-courses"><?php echo $topCoursesLabel; ?></a>
                        <a href="ratemyteacher-instructions.php?section=courses&lang=<?php echo $currentLang; ?>"><?php echo $coursesLabel; ?></a>
                        <a href="tipsandtricks.php"><?php echo $tipsLabel; ?></a>
                        <?php if ($isLoggedIn): ?>
                            <a href="delete_account.php" class="delete-account"><?php echo $deleteAccountLabel; ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="header-center">
                <div class="logo">
                    <h1><a href="home.php" style="color: white; text-decoration: none;">Gaku Neko</a></h1>
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
            <div class="about-container">
                <?php if ($currentLang == 'ja'): ?>
                    <!-- Japanese content -->
                    <div class="about-header">
                        <h1>私たちについて</h1>
                    </div>
                    
                    <div class="disclaimer">
                        <p><strong>免責事項:</strong> このサービスは慶應義塾大学の公式ウェブサイトではなく、大学とは一切関係のない学生による個人プロジェクトです。</p>
                    </div>
                    
                    <div class="about-section">
                        <h2>私たちのミッション</h2>
                        <p style="font-style: italic; text-align: center; margin: 20px 0; font-size: 1.1em;">「学生の、学生による、学生のための」プロジェクト</p>
                        <p>リンカーン大統領の有名な言葉「人民の、人民による、人民のための政治」にインスピレーションを受け、Gaku Nekoは学生コミュニティのために設計されました。このプラットフォームは、慶應義塾大学湘南藤沢キャンパス（SFC）の学生が教授やコースについて有益な情報を共有し、アクセスできるよう作られています。</p>
                    </div>
                    
                    <div class="about-section">
                        <h2>プロジェクトについて</h2>
                        <p>Gaku Nekoは、日本人学生とGIGA（Global Information and Governance Academic）プログラムの国際学生が協力して開発した独立したプラットフォームです。このウェブサイトは、学生が自分に最適な教授やコースを見つけるための情報に基づいた選択をサポートするために作成されました。</p>
                        <p>学生は履修登録の期間に、どの教授を選ぶべきか、どのコースが自分の学習スタイルに合っているかを知る必要があります。私たちは、この重要な決断をするための透明性と信頼できる情報を提供することを目指しています。</p>
                    </div>
                    
                    <div class="contact-info">
                        <h2>お問い合わせ</h2>
                        <p>ご質問、ご提案、またはフィードバックがある場合は、以下のメールアドレスまでご連絡ください：</p>
                        <p><strong>メール:</strong> <a href="mailto:ratemyteachersfc@proton.me" style="color: #1e3a8a;">ratemyteachersfc@proton.me</a></p>
                    </div>
                <?php else: ?>
                    <!-- English content -->
                    <div class="about-header">
                        <h1>About Us</h1>
                    </div>
                    
                    <div class="disclaimer">
                        <p><strong>Disclaimer:</strong> This service is not affiliated with Keio University and is entirely a student-led personal project.</p>
                    </div>
                    
                    <div class="about-section">
                        <h2>Our Mission</h2>
                        <p style="font-style: italic; text-align: center; margin: 20px 0; font-size: 1.1em;">"A project of the Student, By the Student, For the Student"</p>
                        <p>Inspired by Lincoln's famous words "government of the people, by the people, for the people," Gaku Neko was designed with the student community in mind. This platform was created to allow students at Keio University's Shonan Fujisawa Campus (SFC) to share and access valuable information about professors and courses.</p>
                    </div>
                    
                    <div class="about-section">
                        <h2>About the Project</h2>
                        <p>Gaku Neko is an independent platform developed collaboratively by Japanese students and international students from the GIGA (Global Information and Governance Academic) program. This website was created to support students in making informed choices about which professors and courses best suit their educational needs.</p>
                        <p>During course registration periods, students need to know which professors to select and which courses align with their learning style. We aim to provide transparency and reliable information for this important decision-making process.</p>
                    </div>
                    
                    <div class="contact-info">
                        <h2>Contact Us</h2>
                        <p>If you have any questions, suggestions, or feedback, please reach out to us at:</p>
                        <p><strong>Email:</strong> <a href="mailto:ratemyteachersfc@proton.me" style="color: #1e3a8a;">ratemyteachersfc@proton.me</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
            <p>2025 Gaku Neko</p>
            <p style="margin-top: 10px;">
                <a href="ToS.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline; margin-right: 20px;">
                    <?php echo $currentLang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
                </a>
                <a href="privacy_policy.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
                    <?php echo $currentLang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?>
                </a>
            </p>
        </footer>
    </div>
    
    <script>
        // Language toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Update language variables when page loads
            const languageCookie = document.cookie.split('; ')
                .find(row => row.startsWith('language='));
            
            if (languageCookie) {
                const currentLanguage = languageCookie.split('=')[1];
                console.log("Current language:", currentLanguage);
            }
            
            // Language toggle functionality
            const languageLinks = document.querySelectorAll('.language-toggle a');
            languageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // The cookie setting is handled by the onclick attribute
                    console.log("Language changed");
                });
            });
        });
    </script>
</body>
</html>
