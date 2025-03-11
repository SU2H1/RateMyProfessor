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
    <title><?php echo $currentLang == 'ja' ? 'プライバシーポリシー - Rate My Teacher' : 'Privacy Policy - Rate My Teacher'; ?></title>
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
        
        .content-box {
            flex: 1;
            background-color: white;
            margin: 1rem;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .content-box h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .content-box h3 {
            color: #1e3a8a;
            margin-top: 1.5rem;
            margin-bottom: 0.8rem;
        }
        
        .content-box p {
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .content-box ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .content-box li {
            margin-bottom: 0.5rem;
        }
        
        .content-box strong {
            font-weight: bold;
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
            <div class="content-box">
                <h2><?php echo $currentLang == 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?></h2>
                
                <p><strong><?php echo $currentLang == 'ja' ? '免責事項:' : 'Disclaimer:'; ?></strong> 
                <?php echo $currentLang == 'ja' ? '本サイトは慶應義塾大学の公式ウェブサイトではなく、学生が運営するプロジェクトです。本ウェブサイト上のサービスや広告によって生じた損失について、当サイトは責任を負いません。' : 'This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.'; ?></p>
                
                <?php if ($currentLang == 'ja'): ?>
                    <!-- Japanese Content -->
                    <h3>1. 収集する情報</h3>
                    <p>RateMyTeacherSFC.comは、アカウント作成時に以下の情報を収集することがあります：</p>
                    <ul>
                        <li>Keioメールアドレス（確認用）</li>
                        <li>ユーザー名（実名は必要ありません）</li>
                        <li>パスワード（暗号化されて保存されます）</li>
                        <li>学部および学年（任意）</li>
                    </ul>
                    <p>また、当サイトの利用に関する以下の情報も自動的に収集されます：</p>
                    <ul>
                        <li>IPアドレス</li>
                        <li>ブラウザ情報</li>
                        <li>アクセス日時</li>
                        <li>参照元ページ</li>
                    </ul>

                    <h3>2. 情報の利用方法</h3>
                    <p>収集した情報は以下の目的で利用されます：</p>
                    <ul>
                        <li>アカウント管理</li>
                        <li>サイトの利用状況の分析と改善</li>
                        <li>不正行為の検出と防止</li>
                        <li>サービスの提供と機能向上</li>
                    </ul>

                    <h3>3. 情報の共有</h3>
                    <p>当サイトは、以下の場合を除き、ユーザーの個人情報を第三者と共有することはありません：</p>
                    <ul>
                        <li>法的要請に応じる必要がある場合</li>
                        <li>サイトの規約違反を調査する必要がある場合</li>
                        <li>サイト運営に必要なサービスプロバイダーとの共有（これらのプロバイダーは情報の機密性を保持する義務があります）</li>
                    </ul>

                    <h3>4. Cookie（クッキー）の使用</h3>
                    <p>当サイトでは、ユーザー体験の向上とサイト機能の提供のためにCookieを使用しています。ブラウザの設定でCookieの受け入れを管理することができます。</p>

                    <h3>5. データセキュリティ</h3>
                    <p>ユーザー情報の保護のため、適切なセキュリティ対策を実施していますが、インターネット上での完全な安全性は保証できません。</p>

                    <h3>6. ユーザーの権利</h3>
                    <p>ユーザーは以下の権利を有しています：</p>
                    <ul>
                        <li>個人情報へのアクセスと修正</li>
                        <li>アカウントの削除依頼</li>
                        <li>投稿したレビューの編集または削除</li>
                    </ul>

                    <h3>7. プライバシーポリシーの変更</h3>
                    <p>本プライバシーポリシーは予告なく変更される場合があります。変更後のポリシーは本ページに掲載された時点で有効となります。</p>

                    <h3>8. お問い合わせ</h3>
                    <p>プライバシーに関するご質問やご懸念がある場合は、ratemyteachersfc@proton.me までご連絡ください。</p>
                <?php else: ?>
                    <!-- English Content -->
                    <h3>1. Information We Collect</h3>
                    <p>RateMyTeacherSFC.com may collect the following information when you create an account:</p>
                    <ul>
                        <li>Keio email address (for verification purposes)</li>
                        <li>Username (real names are not required)</li>
                        <li>Password (stored in encrypted form)</li>
                        <li>Faculty and year of study (optional)</li>
                    </ul>
                    <p>We also automatically collect the following information about your use of our site:</p>
                    <ul>
                        <li>IP addresses</li>
                        <li>Browser information</li>
                        <li>Access times</li>
                        <li>Referring pages</li>
                    </ul>

                    <h3>2. How We Use Your Information</h3>
                    <p>The information we collect may be used for the following purposes:</p>
                    <ul>
                        <li>Account management</li>
                        <li>Analyzing and improving site usage</li>
                        <li>Detecting and preventing fraudulent activities</li>
                        <li>Providing and enhancing our services</li>
                    </ul>

                    <h3>3. Information Sharing</h3>
                    <p>RateMyTeacherSFC.com does not share your personal information with third parties except in the following circumstances:</p>
                    <ul>
                        <li>To comply with legal requirements</li>
                        <li>To investigate violations of our terms</li>
                        <li>With service providers necessary for site operations (who are obligated to keep your information confidential)</li>
                    </ul>

                    <h3>4. Cookies</h3>
                    <p>We use cookies to enhance user experience and provide website functionality. You can manage cookie acceptance through your browser settings.</p>

                    <h3>5. Data Security</h3>
                    <p>We implement appropriate security measures to protect user information, but cannot guarantee absolute security for data transmitted over the internet.</p>

                    <h3>6. User Rights</h3>
                    <p>Users have the right to:</p>
                    <ul>
                        <li>Access and correct their personal information</li>
                        <li>Request account deletion</li>
                        <li>Edit or delete their posted reviews</li>
                    </ul>

                    <h3>7. Changes to Privacy Policy</h3>
                    <p>This Privacy Policy may be modified at any time. Any changes will be effective immediately upon posting to this page.</p>

                    <h3>8. Contact Us</h3>
                    <p>If you have any questions or concerns about our privacy practices, please contact us at ratemyteachersfc@proton.me.</p>
                <?php endif; ?>
            </div>
        </main>
        
    </div>
    
    <!-- Footer outside the container to make it full width -->
    <footer style="background-color: #1e3a8a; color: white; text-align: center; padding: 1rem; width: 100%;">
        <p>2025 Rate My Teacher</p>
        <p style="margin-top: 10px;">
            <a href="ToS.php?lang=<?php echo $currentLang; ?>" style="color: white; text-decoration: underline;">
                <?php echo $currentLang == 'ja' ? '利用規約' : 'Terms and Conditions'; ?>
            </a>
        </p>
    </footer>
    
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