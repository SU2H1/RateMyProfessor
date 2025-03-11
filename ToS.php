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

// Determine current language
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang == 'ja' ? '利用規約 - Rate My Teacher' : 'Terms and Conditions - Rate My Teacher'; ?></title>
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
        
        .terms-container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .terms-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .terms-header h1 {
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }
        
        .terms-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .terms-section {
            margin-bottom: 2rem;
        }
        
        .terms-section h2 {
            color: #1e3a8a;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .terms-section p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .terms-section ul, .terms-section ol {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        
        .terms-section li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
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
        
        <div class="terms-container">
            <?php if ($currentLang == 'ja'): ?>
                <!-- Japanese Terms and Conditions -->
                <div class="terms-header">
                    <h1>利用規約</h1>
                    <p>最終更新日：2025年3月1日</p>
                </div>
                
                <div class="terms-section">
                    <h2>1. はじめに</h2>
                    <p>Rate My Teacher（以下「当サイト」）へようこそ。当サイトをご利用いただくことにより、ユーザーは以下の利用規約に同意したものとみなされます。これらの規約をよくお読みください。</p>
                </div>
                
                <div class="terms-section">
                    <h2>2. サービス内容</h2>
                    <p>当サイトは、学生が教授とコースに関する評価やレビューを投稿し、共有することを目的としています。当サイトは、教育機関の公式ウェブサイトではなく、教育機関の管理下にもありません。</p>
                    <p><strong>このサービスは慶應義塾大学と提携していません。</strong>教育目的のために作成された独立したプラットフォームです。</p>
                    <p>このサービスは主に<strong>慶應義塾大学湘南藤沢キャンパス</strong>に関連する個人を対象としています。慶應義塾大学湘南藤沢キャンパスの学生、教職員、卒業生のみがこのサービスを利用することができます。</p>
                </div>
                
                <div class="terms-section">
                    <h2>3. アカウント登録と利用</h2>
                    <p>当サイトの特定の機能を利用するには、アカウント登録が必要です。登録時には、正確かつ完全な情報を提供する必要があります。</p>
                    
                    <p>ユーザーは以下の責任を負います：</p>
                    <ul>
                        <li>アカウント情報の機密性を維持すること</li>
                        <li>アカウントを使用して行われるすべての活動に責任を持つこと</li>
                        <li>アカウントの不正使用を発見した場合、直ちに通知すること</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h2>4. コンテンツポリシー</h2>
                    <p>当サイトに投稿するすべてのレビューとコンテンツは、以下の基準を満たす必要があります：</p>
                    
                    <ul>
                        <li>正直かつ正確であること</li>
                        <li>実際の経験に基づいていること</li>
                        <li>個人的な攻撃や侮辱ではなく、建設的であること</li>
                        <li>教授とコースの評価においてできるだけ客観的であること</li>
                        <li>差別的、中傷的、または不適切な内容を含まないこと</li>
                        <li>著作権、商標、または知的財産権を侵害しないこと</li>
                    </ul>
                    
                    <p>当サイトは、ポリシーに違反するコンテンツを削除し、違反を繰り返すユーザーのアカウントを一時停止または削除する権利を有します。</p>
                </div>
                
                <div class="terms-section">
                    <h2>5. プライバシーポリシー</h2>
                    <p>当サイトは、ユーザーのプライバシーを尊重します。個人情報の収集と使用に関する詳細については、プライバシーポリシーをご参照ください。当サイトを利用することにより、ユーザーはプライバシーポリシーに同意したものとみなされます。</p>
                </div>
                
                <div class="terms-section">
                    <h2>6. 免責事項</h2>
                    <p>当サイトは「現状のまま」提供され、明示または黙示を問わず、いかなる種類の保証もありません。当サイトは、サイトの利用から生じるいかなる損害についても責任を負いません。</p>
                    
                    <p>当サイトのコンテンツは、教授やコースに関するユーザーの個人的な意見や経験を反映しています。当サイトは、これらの意見の正確性、信頼性、または完全性について保証しません。</p>
                </div>
                
                <div class="terms-section">
                    <h2>7. 知的財産権</h2>
                    <p>当サイトのコンテンツとデザインは、著作権法および他の知的財産法によって保護されています。ユーザーは、個人的、非商業的な目的でのみ当サイトのコンテンツを使用することができます。</p>
                </div>
                
                <div class="terms-section">
                    <h2>8. 規約の変更</h2>
                    <p>当サイトは、いつでも利用規約を変更する権利を有します。重要な変更がある場合は、サイト上で通知します。変更後も当サイトを利用し続けることにより、ユーザーは変更後の規約に同意したものとみなされます。</p>
                </div>
                
                <div class="terms-section">
                    <h2>9. 連絡先</h2>
                    <p>利用規約に関するご質問やご意見がございましたら、ratemyteachersfc@proton.me までお問い合わせください。</p>
                </div>
            <?php else: ?>
                <!-- English Terms and Conditions -->
                <div class="terms-header">
                    <h1>Terms and Conditions</h1>
                    <p>Last Updated: March 1, 2025</p>
                </div>
                
                <div class="terms-section">
                    <h2>1. Introduction</h2>
                    <p>Welcome to Rate My Teacher ("the Site"). By using the Site, users agree to be bound by the following terms and conditions. Please read them carefully.</p>
                </div>
                
                <div class="terms-section">
                    <h2>2. Service Description</h2>
                    <p>The Site allows students to post and share ratings and reviews of professors and courses. The Site is not an official website of any educational institution and is not under the control of any educational institution.</p>
                    <p><strong>This service is not affiliated with Keio University.</strong> It is an independent platform created for educational purposes.</p>
                    <p>This service is primarily intended for individuals related to <strong>Keio University Shonan Fujisawa Campus</strong>. Only students, faculty, staff, and alumni of Keio University Shonan Fujisawa Campus are permitted to use this service.</p>
                </div>
                
                <div class="terms-section">
                    <h2>3. Account Registration and Use</h2>
                    <p>To access certain features of the Site, users may be required to register for an account. During registration, users must provide accurate and complete information.</p>
                    
                    <p>Users are responsible for:</p>
                    <ul>
                        <li>Maintaining the confidentiality of account information</li>
                        <li>All activities that occur under their account</li>
                        <li>Promptly notifying us of any unauthorized use of their account</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h2>4. Content Policy</h2>
                    <p>All reviews and content posted to the Site must meet the following standards:</p>
                    
                    <ul>
                        <li>Be honest and accurate</li>
                        <li>Be based on actual experience</li>
                        <li>Be constructive rather than personal attacks or insults</li>
                        <li>Be as objective as possible in evaluating professors and courses</li>
                        <li>Not contain discriminatory, defamatory, or inappropriate content</li>
                        <li>Not infringe on copyright, trademark, or intellectual property rights</li>
                    </ul>
                    
                    <p>The Site reserves the right to remove content that violates this policy and to suspend or terminate accounts of users who repeatedly violate this policy.</p>
                </div>
                
                <div class="terms-section">
                    <h2>5. Privacy Policy</h2>
                    <p>The Site respects user privacy. Please refer to our Privacy Policy for details on how we collect and use personal information. By using the Site, users consent to our Privacy Policy.</p>
                </div>
                
                <div class="terms-section">
                    <h2>6. Disclaimer</h2>
                    <p>The Site is provided "as is" without any warranties, express or implied. The Site is not liable for any damages arising from the use of the Site.</p>
                    
                    <p>The content on the Site reflects users' personal opinions and experiences about professors and courses. The Site does not guarantee the accuracy, reliability, or completeness of these opinions.</p>
                </div>
                
                <div class="terms-section">
                    <h2>7. Intellectual Property</h2>
                    <p>The content and design of the Site are protected by copyright laws and other intellectual property laws. Users may use the content of the Site for personal, non-commercial purposes only.</p>
                </div>
                
                <div class="terms-section">
                    <h2>8. Changes to Terms</h2>
                    <p>The Site reserves the right to change these terms and conditions at any time. If we make significant changes, we will notify users on the Site. Continued use of the Site after changes constitutes acceptance of the new terms.</p>
                </div>
                
                <div class="terms-section">
                    <h2>9. Contact Information</h2>
                    <p>If you have any questions or concerns about these terms and conditions, please contact us at ratemyteachersfc@proton.me.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <footer>
            <p>2025 Rate My Teacher</p>
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