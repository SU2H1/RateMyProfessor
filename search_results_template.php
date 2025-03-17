<?php
/**
 * Search Results Template
 * This page displays search results for professors and courses
 */
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ja' ? '検索結果' : 'Search Results'; ?> - Rate My Teacher</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a86e8;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            font-family: var(--font-family);
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .logo a {
            color: white;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            transition: opacity 0.3s;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .language-toggle {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }
        
        .language-toggle button {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 10px;
            margin: 0 5px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .language-toggle button.active {
            background-color: white;
            color: var(--primary-color);
        }
        
        .search-container {
            position: relative;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 15px 20px;
            margin-top: 10px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0 20px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .results-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--dark-gray);
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 10px;
        }
        
        .result-card {
            padding: 15px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            margin-bottom: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .result-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .result-title a {
            text-decoration: none;
            color: var(--dark-gray);
        }
        
        .result-title a:hover {
            color: var(--primary-color);
        }
        
        .result-meta {
            color: var(--secondary-color);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .result-rating {
            color: var(--warning-color);
            font-size: 14px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 0;
            color: var(--secondary-color);
        }
        
        .footer {
            background-color: var(--dark-gray);
            color: white;
            padding: 30px 0;
            margin-top: 40px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 5px;
        }
        
        .footer-links a {
            display: block;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 20px;
            }
            
            .nav-links {
                margin-top: 10px;
            }
            
            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-button {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">
                <a href="home.php" style="color: white; text-decoration: none; font-size: 24px; font-weight: bold;">Rate My Teacher</a>
            </div>
            <div class="nav-links">
                <a href="home.php"><?php echo $lang === 'ja' ? 'ホーム' : 'Home'; ?></a>
                <a href="search.php"><?php echo $lang === 'ja' ? 'コース' : 'Courses'; ?></a>
                <?php if ($isLoggedIn): ?>
                    <a href="my_account.php"><?php echo $lang === 'ja' ? 'マイアカウント' : 'My Account'; ?></a>
                    <a href="logout.php"><?php echo $lang === 'ja' ? 'ログアウト' : 'Logout'; ?></a>
                <?php else: ?>
                    <a href="login.php"><?php echo $lang === 'ja' ? 'ログイン' : 'Login'; ?></a>
                    <a href="register.php"><?php echo $lang === 'ja' ? '登録' : 'Register'; ?></a>
                <?php endif; ?>
                <div class="language-toggle">
                    <button <?php echo $lang === 'ja' ? 'class="active"' : ''; ?> onclick="window.location.href='?<?php
                        $queryParams = $_GET;
                        $queryParams['lang'] = 'ja';
                        echo http_build_query($queryParams);
                    ?>'"><?php echo $lang === 'ja' ? '日本語' : '日本語'; ?></button>
                    <button <?php echo $lang === 'en' ? 'class="active"' : ''; ?> onclick="window.location.href='?<?php
                        $queryParams = $_GET;
                        $queryParams['lang'] = 'en';
                        echo http_build_query($queryParams);
                    ?>'"><?php echo $lang === 'ja' ? 'English' : 'English'; ?></button>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="search-container">
            <form class="search-form" action="search.php" method="GET">
                <input type="text" name="q" class="search-input" value="<?php echo htmlspecialchars($query); ?>" placeholder="<?php echo $lang === 'ja' ? '教授またはコースを検索...' : 'Search professors or courses...'; ?>">
                <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                <button type="submit" class="search-button"><?php echo $lang === 'ja' ? '検索' : 'Search'; ?></button>
            </form>
        </div>

        <?php if (empty($query)): ?>
            <div class="results-section">
                <div class="no-results">
                    <h2><?php echo $lang === 'ja' ? '検索キーワードを入力してください' : 'Please enter a search term'; ?></h2>
                    <p><?php echo $lang === 'ja' ? '教授名やコース名を入力して検索してください。' : 'Enter a professor or course name to search.'; ?></p>
                </div>
            </div>
        <?php elseif (empty($results['professors']) && empty($results['courses'])): ?>
            <div class="results-section">
                <div class="no-results">
                    <h2><?php echo $lang === 'ja' ? '検索結果が見つかりませんでした' : 'No results found'; ?></h2>
                    <p><?php echo $lang === 'ja' ? '「' . htmlspecialchars($query) . '」に一致する結果は見つかりませんでした。' : 'No matches found for "' . htmlspecialchars($query) . '".'; ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Professor Results -->
            <?php if (!empty($results['professors'])): ?>
                <div class="results-section">
                    <h2 class="section-title"><?php echo $lang === 'ja' ? '教授' : 'Professors'; ?> (<?php echo count($results['professors']); ?>)</h2>
                    
                    <?php foreach ($results['professors'] as $professor): ?>
                        <div class="result-card">
                            <div class="result-title">
                                <?php 
                                    // Use name_no_spaces if available, otherwise replace spaces with + signs
                                    $profNameForUrl = isset($professor['name_no_spaces']) 
                                        ? $professor['name_no_spaces'] 
                                        : str_replace(' ', '+', $professor['name']);
                                    $profNameDisplay = $lang === 'ja' && isset($professor['name_ja']) ? $professor['name_ja'] : $professor['name'];
                                ?>
                                <a href="professor.php?name=<?php echo urlencode($profNameForUrl); ?>&lang=<?php echo $lang; ?>">
                                    <?php echo htmlspecialchars($profNameDisplay); ?>
                                </a>
                                <!-- Debug info -->
                                <div style="font-size: 10px; color: #999; margin-top: 4px;">
                                    URL name: <?php echo htmlspecialchars($profNameForUrl); ?> | 
                                    EN: <?php echo htmlspecialchars($professor['name']); ?> | 
                                    JA: <?php echo htmlspecialchars($professor['name_ja'] ?? 'N/A'); ?> |
                                    No spaces: <?php echo htmlspecialchars($professor['name_no_spaces'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="result-meta">
                                <?php echo htmlspecialchars($lang === 'ja' && isset($professor['department_ja']) ? $professor['department_ja'] : ($professor['department'] ?? '')); ?>
                            </div>
                            <div class="result-rating">
                                <?php if (!empty($professor['avg_rating'])): ?>
                                    <span style="color: #ffc107;">★</span> <?php echo $professor['avg_rating']; ?> 
                                    (<?php echo $professor['rating_count']; ?> <?php echo $lang === 'ja' ? '件の評価' : 'ratings'; ?>)
                                <?php else: ?>
                                    <?php echo $lang === 'ja' ? 'まだ評価はありません' : 'No ratings yet'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Course Results -->
            <?php if (!empty($results['courses'])): ?>
                <div class="results-section">
                    <h2 class="section-title"><?php echo $lang === 'ja' ? 'コース' : 'Courses'; ?> (<?php echo count($results['courses']); ?>)</h2>
                    
                    <?php foreach ($results['courses'] as $course): ?>
                        <div class="result-card">
                            <div class="result-title">
                                <?php 
                                    $courseNameForUrl = $lang === 'ja' && isset($course['name_ja']) ? $course['name_ja'] : $course['name'];
                                    $profNameForUrl = !empty($course['professor_name_no_spaces']) ? $course['professor_name_no_spaces'] : 
                                        (!empty($course['professor_name']) ? str_replace(' ', '', $course['professor_name']) : '');
                                    $courseNameDisplay = $lang === 'ja' && isset($course['name_ja']) ? $course['name_ja'] : $course['name'];
                                    $courseCode = !empty($course['course_code']) ? $course['course_code'] : '';
                                ?>
                                <a href="course_page_template.php?course_code=<?php echo urlencode($courseCode); ?>&lang=<?php echo $lang; ?>">
                                    <?php echo htmlspecialchars($courseNameDisplay); ?>
                                </a>
                                <!-- Debug info -->
                                <div style="font-size: 10px; color: #999; margin-top: 4px;">
                                    Course code: <?php echo htmlspecialchars($courseCode); ?> |
                                    Course: <?php echo htmlspecialchars($courseNameForUrl); ?> | 
                                    Professor: <?php echo htmlspecialchars($profNameForUrl); ?> | 
                                    EN: <?php echo htmlspecialchars($course['name']); ?> | 
                                    JA: <?php echo htmlspecialchars($course['name_ja'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="result-meta">
                                <?php if (!empty($course['professor_name'])): ?>
                                    <?php echo $lang === 'ja' ? '担当教員: ' : 'Taught by: '; ?>
                                    <a href="professor.php?name=<?php echo urlencode(!empty($course['professor_name_no_spaces']) ? $course['professor_name_no_spaces'] : str_replace(' ', '+', $course['professor_name'])); ?>&lang=<?php echo $lang; ?>">
                                        <?php echo htmlspecialchars($lang === 'ja' && isset($course['professor_name_ja']) ? $course['professor_name_ja'] : $course['professor_name']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="result-rating">
                                <?php if (!empty($course['avg_rating'])): ?>
                                    <span style="color: #ffc107;">★</span> <?php echo $course['avg_rating']; ?> 
                                    (<?php echo $course['rating_count']; ?> <?php echo $lang === 'ja' ? '件の評価' : 'ratings'; ?>)
                                <?php else: ?>
                                    <?php echo $lang === 'ja' ? 'まだ評価はありません' : 'No ratings yet'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3 class="footer-title">Rate My Teacher</h3>
                <div class="footer-links">
                    <a href="#"><?php echo $lang === 'ja' ? 'サイトについて' : 'About Us'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? '利用規約' : 'Terms of Use'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? 'プライバシーポリシー' : 'Privacy Policy'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? 'お問い合わせ' : 'Contact Us'; ?></a>
                </div>
            </div>
            <div class="footer-section">
                <h3 class="footer-title"><?php echo $lang === 'ja' ? 'コンテンツ' : 'Content'; ?></h3>
                <div class="footer-links">
                    <a href="#"><?php echo $lang === 'ja' ? 'コース一覧' : 'Course List'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? '教授一覧' : 'Professor List'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? '学部別コース' : 'Courses by Faculty'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? 'ランキング' : 'Rankings'; ?></a>
                </div>
            </div>
            <div class="footer-section">
                <h3 class="footer-title"><?php echo $lang === 'ja' ? '学生向け' : 'For Students'; ?></h3>
                <div class="footer-links">
                    <a href="#"><?php echo $lang === 'ja' ? 'アカウント登録' : 'Register'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? 'レビューを書く' : 'Write a Review'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? '履修計画ツール' : 'Course Planner'; ?></a>
                    <a href="#"><?php echo $lang === 'ja' ? '学習リソース' : 'Learning Resources'; ?></a>
                </div>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Rate My Teacher - SFC. All rights reserved.
        </div>
    </footer>
</body>
</html>