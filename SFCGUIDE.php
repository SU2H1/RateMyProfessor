<?php
/**
 * SFC Restaurant Guide - Multiple Restaurants
 * Michelin Guide Style with multiple restaurant entries
 */

// Function to get browser language
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
elseif (isset($_COOKIE['language'])) {
    $lang = $_COOKIE['language'] === 'ja' ? 'ja' : 'en';
} else {
    $lang = getBrowserLanguage(); // Default based on browser
    setcookie('language', $lang, time() + (86400 * 30), "/");
    $_COOKIE['language'] = $lang; // Set for current request
}

// ===== RESTAURANTS DATA - ADD NEW RESTAURANTS HERE =====
$restaurants = [
    // Restaurant 1
    [
        'name' => 'Sati',
        'name_ja' => 'サティー',
        'cuisine_type' => 'Indian Curry',
        'cuisine_type_ja' => 'インドカレー',
        'address' => 'Shonandai Parkside Building 102 1-9-13 Shonandai Fujisawa, Kanagawa',
        'address_ja' => '神奈川県藤沢市湘南台１丁目9−１３ 湘南台パークサイドビル 102',
        'phone' => '0466430672',
        'price_range' => '￥1,000～￥2,000',
        'stars' => 3, // Michelin stars (0-3)
        'description' => 'Cheap and delicious Indian curry restaurant.',
        'description_ja' => '安くて、美味しいインドカレー専門店。',
        'recommendation' => 'Keema Curry, Cheese Naan',
        'recommendation_ja' => 'キーマカレー、チーズナン',
        'hours' => 'Lunch 11:00～15:00, Lunch Buffet on Weekends and Public Holiday 11:00～15:00, Dinner 17:00～22:00',
        'hours_ja' => 'ランチ 11:00～15:00、土日祝にランチバイキング 11:00～15:00、ディナー 17:00～22:00',
        'closed' => 'N/A',
        'closed_ja' => '無し',
        'photo' => 'ratemyteachersfc.com/public_html/Images/Sati_Photo',
        'photo_dish' => 'ratemyteachersfc.com/public_html/Images/Sati_Dish'
    ],
    
    // Restaurant 2 
    [
        'name' => 'Ramen Jiro Shonan Fujisawa',
        'name_ja' => ' ラーメン二郎 湘南藤沢店 ',
        'cuisine_type' => 'Jiro Ramen',
        'cuisine_type_ja' => '二郎ラーメン',
        'address' => '1-10-14 Honcho Fujisawa, Kanagawa',
        'address_ja' => '神奈川県藤沢市本町1-10-14 ',
        'phone' => 'N/A',
        'price_range' => '¥1000',
        'stars' => 3, // Michelin stars (0-3)
        'description' => 'One of the best Ramen Jiro with the most delicious chashu.',
        'description_ja' => 'ラーメン二郎の中でも最高のチャーシューが食べられるお店。',
        'recommendation' => 'Half size Ramen, Garlic, SUPER Kimchi',
        'recommendation_ja' => 'ラーメン半分、ニンニク、SUPERキムチ',
        'hours' => 'Lunch 11:00～14:30, Dinner 17:00～21:00',
        'hours_ja' => '11:00-22:00',
        'closed' => 'Tuesday',
        'closed_ja' => '火曜日',
        'photo' => 'Images/FujisawaJiro_Photo.png',
        'photo_dish' => 'Images/FujisawaJiro_Dish.png'
    ]
    
    // Copy the format above to add more restaurants
];
// ===== END OF RESTAURANTS DATA =====

// Helper functions
function generateStars($count) {
    $html = '';
    for ($i = 0; $i < $count; $i++) {
        $html .= '★ ';
    }
    return trim($html);
}

function getLangSwitchUrl($currentLang) {
    $params = $_GET;
    $params['lang'] = $currentLang === 'en' ? 'ja' : 'en';
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ja' ? 'SFCグルメガイド' : 'SFC Food Guide'; ?></title>
    <style>
        /* Reset and basic styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 15px;
            background-color: #fff;
        }
        
        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .logo {
            font-size: 16px;
            font-weight: bold;
        }
        
        .lang-toggle a {
            margin-left: 10px;
            text-decoration: none;
            color: #999;
        }
        
        .lang-toggle a.active {
            color: #333;
            font-weight: bold;
        }
        
        /* Restaurant entry */
        .restaurant-entry {
            display: flex;
            margin-bottom: 40px;
            page-break-inside: avoid;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .restaurant-entry:last-child {
            border-bottom: none;
        }
        
        .restaurant-photos {
            width: 30%;
            margin-right: 20px;
        }
        
        .photo {
            width: 100%;
            height: auto;
            margin-bottom: 8px;
            border: 1px solid #eee;
        }
        
        .photo-placeholder {
            width: 100%;
            padding-top: 75%; /* 4:3 aspect ratio */
            position: relative;
            background-color: #f0f0f0;
            border: 1px solid #eee;
            margin-bottom: 8px;
        }
        
        .photo-placeholder::after {
            content: "Photo";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #aaa;
            font-size: 14px;
        }
        
        .restaurant-info {
            width: 70%;
        }
        
        .restaurant-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .restaurant-cuisine {
            font-style: italic;
            color: #666;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .restaurant-stars {
            color: #BF0000; /* Michelin red */
            margin-bottom: 8px;
        }
        
        .restaurant-description {
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .restaurant-recommendation {
            font-size: 14px;
            font-style: italic;
            margin-bottom: 12px;
        }
        
        .restaurant-details {
            font-size: 13px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 8px;
            line-height: 1.3;
        }
        
        .restaurant-details p {
            margin-bottom: 4px;
        }
        
        .price {
            font-weight: bold;
        }
        
        /* Index styling (for guide with many restaurants) */
        .index-section {
            margin-bottom: 30px;
        }
        
        .index-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #BF0000; /* Michelin red */
        }
        
        .index-list {
            columns: 2;
            column-gap: 20px;
        }
        
        .index-item {
            margin-bottom: 8px;
            break-inside: avoid;
        }
        
        .index-item a {
            text-decoration: none;
            color: #333;
        }
        
        .index-item a:hover {
            text-decoration: underline;
        }
        
        /* Table of contents */
        .toc {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }
        
        .toc h2 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .toc ul {
            list-style-type: none;
        }
        
        .toc li {
            margin-bottom: 6px;
        }
        
        .toc a {
            text-decoration: none;
            color: #333;
        }
        
        .toc a:hover {
            text-decoration: underline;
        }
        
        /* Cover page */
        .cover {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .cover h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #BF0000; /* Michelin red */
        }
        
        .cover h2 {
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: normal;
        }
        
        .cover .year {
            font-size: 36px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .cover .region {
            font-size: 20px;
            margin-bottom: 30px;
        }
        
        /* Footer */
        footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        /* Back to home button */
        .back-to-home {
            background-color: #1e3a8a;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 15px;
        }
        
        .back-to-home:hover {
            background-color: #152a63;
        }
        
        /* Language toggle styling from home.php */
        .language-toggle {
            display: flex;
            align-items: center;
        }
        
        .language-toggle a {
            display: inline-block;
            width: 80px;
            text-align: center;
            padding: 8px 0;
            margin-left: 5px;
            text-decoration: none;
            border: 1px solid #1e3a8a;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Print styles */
        @media print {
            body {
                font-size: 12px;
                padding: 0;
            }
            
            header {
                display: none;
            }
            
            .restaurant-entry {
                page-break-inside: avoid;
            }
            
            .cover {
                page-break-after: always;
            }
            
            .toc {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <a href="home.php" class="back-to-home">
                <?php echo $lang === 'ja' ? 'ホームに戻る' : 'Back to Home'; ?>
            </a>
            <?php echo $lang === 'ja' ? 'SFCグルメガイド' : 'SFC FOOD GUIDE'; ?>
        </div>
        <div class="language-toggle">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>"
               style="background: <?php echo $lang === 'en' ? '#1e3a8a' : 'white'; ?>; 
                      color: <?php echo $lang === 'en' ? 'white' : '#1e3a8a'; ?>;"
               onclick="document.cookie='language=en; path=/; max-age=2592000'; return true;">
                English
            </a>
            <a href="?lang=ja" class="<?php echo $lang === 'ja' ? 'active' : ''; ?>"
               style="background: <?php echo $lang === 'ja' ? '#1e3a8a' : 'white'; ?>; 
                      color: <?php echo $lang === 'ja' ? 'white' : '#1e3a8a'; ?>;"
               onclick="document.cookie='language=ja; path=/; max-age=2592000'; return true;">
                日本語
            </a>
        </div>
    </header>
    
    <!-- Cover page (you can comment out if not needed) -->
    <div class="cover">
        <h1><?php echo $lang === 'ja' ? 'SFCグルメガイド' : 'SFC FOOD GUIDE'; ?></h1>
        <h2><?php echo $lang === 'ja' ? '慶應義塾大学湘南藤沢キャンパス周辺' : 'Keio University Shonan Fujisawa Campus Area'; ?></h2>
        <div class="year">2025</div>
        <div class="region"><?php echo $lang === 'ja' ? '湘南台・藤沢・辻堂' : 'SHONANDAI・FUJISAWA・TSUJIDO'; ?></div>
    </div>
    
    <!-- Table of Contents -->
    <div class="toc">
        <h2><?php echo $lang === 'ja' ? '目次' : 'Contents'; ?></h2>
        <ul>
            <?php foreach($restaurants as $index => $restaurant): ?>
            <li>
                <a href="#restaurant-<?php echo $index; ?>">
                    <?php echo $lang === 'ja' && isset($restaurant['name_ja']) ? htmlspecialchars($restaurant['name_ja']) : htmlspecialchars($restaurant['name']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Restaurant entries -->
    <?php foreach($restaurants as $index => $restaurant): ?>
        <?php
        // Get language-specific content
        $name = $lang === 'ja' && isset($restaurant['name_ja']) ? $restaurant['name_ja'] : $restaurant['name'];
        $cuisine = $lang === 'ja' && isset($restaurant['cuisine_type_ja']) ? $restaurant['cuisine_type_ja'] : $restaurant['cuisine_type'];
        $address = $lang === 'ja' && isset($restaurant['address_ja']) ? $restaurant['address_ja'] : $restaurant['address'];
        $description = $lang === 'ja' && isset($restaurant['description_ja']) ? $restaurant['description_ja'] : $restaurant['description'];
        $recommendation = $lang === 'ja' && isset($restaurant['recommendation_ja']) ? $restaurant['recommendation_ja'] : $restaurant['recommendation'];
        $hours = $lang === 'ja' && isset($restaurant['hours_ja']) ? $restaurant['hours_ja'] : $restaurant['hours'];
        $closed = $lang === 'ja' && isset($restaurant['closed_ja']) ? $restaurant['closed_ja'] : $restaurant['closed'];
        ?>
        <div id="restaurant-<?php echo $index; ?>" class="restaurant-entry">
            <div class="restaurant-photos">
                <?php if (isset($restaurant['photo']) && file_exists($restaurant['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($restaurant['photo']); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="photo">
                <?php else: ?>
                    <div class="photo-placeholder"></div>
                <?php endif; ?>
                
                <?php if (isset($restaurant['photo_dish']) && file_exists($restaurant['photo_dish'])): ?>
                    <img src="<?php echo htmlspecialchars($restaurant['photo_dish']); ?>" alt="<?php echo htmlspecialchars($name); ?> dish" class="photo">
                <?php else: ?>
                    <div class="photo-placeholder"></div>
                <?php endif; ?>
            </div>
            
            <div class="restaurant-info">
                <h2 class="restaurant-name"><?php echo htmlspecialchars($name); ?></h2>
                <div class="restaurant-cuisine"><?php echo htmlspecialchars($cuisine); ?></div>
                
                <?php if (isset($restaurant['stars']) && $restaurant['stars'] > 0): ?>
                <div class="restaurant-stars"><?php echo generateStars($restaurant['stars']); ?></div>
                <?php endif; ?>
                
                <div class="restaurant-description"><?php echo htmlspecialchars($description); ?></div>
                
                <?php if (!empty($recommendation)): ?>
                <div class="restaurant-recommendation">
                    <strong><?php echo $lang === 'ja' ? 'おすすめ：' : 'Recommended: '; ?></strong>
                    <?php echo htmlspecialchars($recommendation); ?>
                </div>
                <?php endif; ?>
                
                <div class="restaurant-details">
                    <p><?php echo htmlspecialchars($address); ?></p>
                    <p><?php echo htmlspecialchars($restaurant['phone']); ?></p>
                    <p><?php echo htmlspecialchars($hours); ?></p>
                    <p><?php echo $lang === 'ja' ? '定休日: ' : 'Closed: '; ?><?php echo htmlspecialchars($closed); ?></p>
                    <p class="price"><?php echo $restaurant['price_range']; ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <footer>
        <p>&copy; 2025 <?php echo $lang === 'ja' ? 'SFCグルメガイド' : 'SFC Food Guide'; ?></p>
        <p style="margin-top: 10px;">
            <a href="home.php" style="color: #666; text-decoration: underline;">
                <?php echo $lang === 'ja' ? 'Rate My Teacherに戻る' : 'Back to Rate My Teacher'; ?>
            </a>
        </p>
    </footer>

    <script>
        // Script to handle language cookie setting
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listeners to language toggle links
            document.querySelectorAll('.language-toggle a').forEach(link => {
                link.addEventListener('click', function(e) {
                    const lang = this.href.includes('lang=ja') ? 'ja' : 'en';
                    document.cookie = `language=${lang}; path=/; max-age=2592000`; // 30 days
                });
            });
        });
    </script>
</body>
</html>