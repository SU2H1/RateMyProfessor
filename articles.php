<?php
// Include session configuration before starting the session
require_once 'session_config.php';

// Now start the session
    session_start();
}

// Debug session information
error_log('ARTICLES.PHP - Session ID: ' . session_id());
error_log('ARTICLES.PHP - Session data: ' . print_r($_SESSION, true));

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

// Sample articles data - in a real implementation, this would come from a database
$articles = [
    [
        'id' => 1,
        'title_en' => 'How to Choose the Right Elective Courses',
        'title_ja' => '選択科目の選び方',
        'excerpt_en' => 'Strategic tips for selecting electives that enhance your degree and career prospects.',
        'excerpt_ja' => '学位とキャリアの見通しを高める選択科目を選ぶための戦略的なヒント。',
        'author_en' => 'Dr. Tanaka Hiroshi',
        'author_ja' => '田中 博士',
        'date' => '2025-03-15',
        'image' => 'images/elective-courses.jpg',
        'category_en' => 'Academic Planning',
        'category_ja' => '学業計画'
    ],
    [
        'id' => 2,
        'title_en' => 'Research Methods: A Practical Guide for Students',
        'title_ja' => '研究方法：学生のための実践ガイド',
        'excerpt_en' => 'Learn the fundamentals of academic research that will help you excel in your assignments.',
        'excerpt_ja' => '課題で優れた成績を収めるのに役立つ学術研究の基礎を学びましょう。',
        'author_en' => 'Prof. Nakamura Yuki',
        'author_ja' => '中村 ゆき教授',
        'date' => '2025-03-10',
        'image' => 'images/research-methods.jpg',
        'category_en' => 'Research Skills',
        'category_ja' => '研究スキル'
    ],
    [
        'id' => 3,
        'title_en' => 'Networking Skills for College Students',
        'title_ja' => '大学生のためのネットワーキングスキル',
        'excerpt_en' => 'Building professional connections during your college years can open doors to opportunities.',
        'excerpt_ja' => '大学時代に専門的なつながりを構築することで、機会への扉が開きます。',
        'author_en' => 'Dr. Smith Karen',
        'author_ja' => 'スミス・カレン博士',
        'date' => '2025-03-05',
        'image' => 'images/networking.jpg',
        'category_en' => 'Career Development',
        'category_ja' => 'キャリア開発'
    ],
    [
        'id' => 4,
        'title_en' => 'Effective Study Techniques for Final Exams',
        'title_ja' => '期末試験のための効果的な学習テクニック',
        'excerpt_en' => 'Science-backed methods to improve retention and performance during exam season.',
        'excerpt_ja' => '試験シーズン中の記憶力とパフォーマンスを向上させるための科学的に裏付けられた方法。',
        'author_en' => 'Prof. Watanabe Kenji',
        'author_ja' => '渡辺 健二教授',
        'date' => '2025-02-28',
        'image' => 'images/study-techniques.jpg',
        'category_en' => 'Study Tips',
        'category_ja' => '学習のコツ'
    ],
    [
        'id' => 5,
        'title_en' => 'Managing Academic Stress: Mental Health for Students',
        'title_ja' => '学業ストレスの管理：学生のためのメンタルヘルス',
        'excerpt_en' => 'Practical strategies to maintain balance and wellbeing during high-pressure academic periods.',
        'excerpt_ja' => '高圧力の学術期間中にバランスと健康を維持するための実践的な戦略。',
        'author_en' => 'Dr. Yamamoto Aki',
        'author_ja' => '山本 アキ博士',
        'date' => '2025-02-20',
        'image' => 'images/mental-health.jpg',
        'category_en' => 'Student Wellness',
        'category_ja' => '学生の健康'
    ],
    [
        'id' => 6,
        'title_en' => 'International Exchange Programs: What You Need to Know',
        'title_ja' => '国際交換プログラム：知っておくべきこと',
        'excerpt_en' => 'A comprehensive guide to study abroad opportunities and how to prepare for them.',
        'excerpt_ja' => '留学の機会とその準備方法に関する包括的なガイド。',
        'author_en' => 'Prof. Johnson Michael',
        'author_ja' => 'ジョンソン・マイケル教授',
        'date' => '2025-02-15',
        'image' => 'images/exchange-program.jpg',
        'category_en' => 'International Education',
        'category_ja' => '国際教育'
    ]
];

// Featured article - usually the newest or most important
$featuredArticle = $articles[0];
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentLang == 'ja' ? 'Rate My Teacher - 記事' : 'Rate My Teacher - Articles'; ?></title>
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
            display: inline-block;
            width: 80px; 
            text-align: center;
            padding: 8px 0;
            margin-right: 5px;
            border: 1px solid white;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }
        
        .language-toggle a.active {
            background-color: white;
            color: #1e3a8a;
        }
        
        /* Articles section styles */
        .articles-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-title {
            margin-bottom: 2rem;
            color: #1e3a8a;
            text-align: center;
            font-size: 2.5rem;
        }
        
        .articles-intro {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 3rem auto;
            line-height: 1.6;
        }
        
        /* Featured article */
        .featured-article {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .featured-article-content {
            padding: 2rem;
        }
        
        .featured-article-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .featured-label {
            display: inline-block;
            background-color: #ff9800;
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .featured-article h2 {
            color: #1e3a8a;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .featured-article p {
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 1.1rem;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .article-category {
            display: inline-block;
            background-color: #e0e7ff;
            color: #1e3a8a;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            margin-right: 1rem;
        }
        
        .article-date {
            margin-right: 1rem;
        }
        
        .read-more-btn {
            display: inline-block;
            background-color: #1e3a8a;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .read-more-btn:hover {
            background-color: #152b5e;
        }
        
        /* Article grid */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .article-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .article-card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .article-card-content {
            padding: 1.5rem;
        }
        
        .article-card h3 {
            color: #1e3a8a;
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
        }
        
        .article-card p {
            margin-bottom: 1.2rem;
            color: #444;
            line-height: 1.5;
        }
        
        .article-card .article-meta {
            margin-bottom: 1rem;
        }
        
        .article-card .read-more-btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }
        
        /* Pagination */
        .pagination {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .pagination-btn {
            display: inline-block;
            background-color: #f0f0f0;
            color: #333;
            padding: 0.5rem 1rem;
            margin: 0 0.3rem;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .pagination-btn:hover {
            background-color: #e0e0e0;
        }
        
        .pagination-btn.active {
            background-color: #1e3a8a;
            color: white;
        }
        
        /* Search bar */
        .search-container {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 2rem;
        }
        
        .search-bar {
            width: 60%;
            max-width: 600px;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #1e3a8a;
            border-radius: 50px;
            font-size: 1.1rem;
        }
        
        .search-bar button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #1e3a8a;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            cursor: pointer;
        }
        
        footer {
            background-color: #1e3a8a;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            .featured-article {
                flex-direction: column;
            }
            
            .featured-article-image {
                width: 100%;
                height: 200px;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-left">
                <div class="dropdown">
                    <?php 
                    // Determine menu labels based on language
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
                    $homeLabel = $currentLang == 'ja' ? 'ホーム' : 'Home';
                    ?>
                    <button class="dropbtn"><?php echo $menuLabel; ?></button>
                    <div class="dropdown-content">
                        <a href="home.php"><?php echo $homeLabel; ?></a>
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
                    <a href="?lang=en" class="<?php echo $currentLang == 'en' ? 'active' : ''; ?>">
                        English
                    </a>
                    <a href="?lang=ja" class="<?php echo $currentLang == 'ja' ? 'active' : ''; ?>">
                        日本語
                    </a>
                </div>
            </div>
        </header>
        
        <div class="search-container">
            <div class="search-bar">
                <input type="text" id="articleSearchInput" placeholder="<?php echo $currentLang == 'ja' ? '記事を検索...' : 'Search articles...'; ?>">
                <button id="articleSearchButton"><?php echo $currentLang == 'ja' ? '検索' : 'Search'; ?></button>
            </div>
        </div>
        
        <div class="articles-container">
            <h1 class="page-title"><?php echo $currentLang == 'ja' ? '学生のための記事' : 'Articles for Students'; ?></h1>
            <p class="articles-intro">
                <?php echo $currentLang == 'ja' ? 
                    '学生生活、研究スキル、キャリア開発などに関する有益な情報や洞察を共有します。これらの記事は、あなたの大学での成功に役立つように設計されています。' : 
                    'Explore valuable information and insights on student life, research skills, career development, and more. These articles are designed to help you succeed in your university journey.'; 
                ?>
            </p>
            
            <!-- Featured Article Section -->
            <div class="featured-article">
                <img src="<?php echo $featuredArticle['image']; ?>" alt="<?php echo $currentLang == 'ja' ? $featuredArticle['title_ja'] : $featuredArticle['title_en']; ?>" class="featured-article-image">
                <div class="featured-article-content">
                    <span class="featured-label"><?php echo $currentLang == 'ja' ? '注目記事' : 'Featured'; ?></span>
                    <h2><?php echo $currentLang == 'ja' ? $featuredArticle['title_ja'] : $featuredArticle['title_en']; ?></h2>
                    <div class="article-meta">
                        <span class="article-category"><?php echo $currentLang == 'ja' ? $featuredArticle['category_ja'] : $featuredArticle['category_en']; ?></span>
                        <span class="article-date"><?php echo date('F j, Y', strtotime($featuredArticle['date'])); ?></span>
                        <span class="article-author"><?php echo $currentLang == 'ja' ? $featuredArticle['author_ja'] : $featuredArticle['author_en']; ?></span>
                    </div>
                    <p><?php echo $currentLang == 'ja' ? $featuredArticle['excerpt_ja'] : $featuredArticle['excerpt_en']; ?></p>
                    <a href="articles_indp.php?id=<?php echo $featuredArticle['id']; ?>&lang=<?php echo $currentLang; ?>" class="read-more-btn">
                        <?php echo $currentLang == 'ja' ? '続きを読む' : 'Read More'; ?>
                    </a>
                </div>
            </div>
            
            <!-- Articles Grid -->
            <div class="articles-grid">
                <?php foreach (array_slice($articles, 1) as $article): ?>
                <div class="article-card">
                    <img src="<?php echo $article['image']; ?>" alt="<?php echo $currentLang == 'ja' ? $article['title_ja'] : $article['title_en']; ?>" class="article-card-image">
                    <div class="article-card-content">
                        <h3><?php echo $currentLang == 'ja' ? $article['title_ja'] : $article['title_en']; ?></h3>
                        <div class="article-meta">
                            <span class="article-category"><?php echo $currentLang == 'ja' ? $article['category_ja'] : $article['category_en']; ?></span>
                            <span class="article-date"><?php echo date('F j, Y', strtotime($article['date'])); ?></span>
                        </div>
                        <p><?php echo $currentLang == 'ja' ? $article['excerpt_ja'] : $article['excerpt_en']; ?></p>
                        <a href="article_indp.php?id=<?php echo $article['id']; ?>&lang=<?php echo $currentLang; ?>" class="read-more-btn">
                            <?php echo $currentLang == 'ja' ? '続きを読む' : 'Read More'; ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div class="pagination">
                <a href="#" class="pagination-btn active">1</a>
                <a href="#" class="pagination-btn">2</a>
                <a href="#" class="pagination-btn">3</a>
                <a href="#" class="pagination-btn">›</a>
            </div>
        </div>
        
        <footer>
            <p><?php echo $currentLang == 'ja' ? '© 2025 Rate My Teacher. 全著作権所有。' : '© 2025 Rate My Teacher. All rights reserved.'; ?></p>
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
    
    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('articleSearchInput');
            const searchButton = document.getElementById('articleSearchButton');
            
            // Search on button click
            searchButton.addEventListener('click', function() {
                performSearch();
            });
            
            // Search on Enter key press
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    // In a real implementation, this would redirect to a search results page
                    // For now, we'll just log it
                    console.log('Searching for:', searchTerm);
                    
                    // Get current language from cookie
                    const currentLang = document.cookie.split('; ')
                        .find(row => row.startsWith('language='))
                        ?.split('=')[1] || 'en';
                        
                    // Redirect to a hypothetical search results page
                    window.location.href = `article_search.php?q=${encodeURIComponent(searchTerm)}&lang=${currentLang}`;
                }
            }
            
            // Language toggle functionality
            const languageLinks = document.querySelectorAll('.language-toggle a');
            languageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // The actual language change is handled by the server-side code
                    // This is just for visual feedback until the page reloads
                    languageLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
