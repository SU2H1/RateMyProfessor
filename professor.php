<?php
/**
 * Professor Detail Page
 * This is a simplified version that shows professor details
 */

// Debugging - log all parameters
error_log("Professor.php accessed with parameters: " . print_r($_GET, true));

// Get professor info from URL - support multiple formats
$professor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$professor_name = isset($_GET['professor']) ? $_GET['professor'] : '';
$professor_name = isset($_GET['name']) ? $_GET['name'] : $professor_name; // Support name parameter
$language = isset($_GET['lang']) ? $_GET['lang'] : 'en';

// Output HTML header with styling
echo '<!DOCTYPE html>
<html lang="' . $language . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title>' . ($language == 'ja' ? '教授詳細' : 'Professor Details') . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e3a8a;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
        }
        p {
            margin-bottom: 15px;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background-color: #1e3a8a;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .professor-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #1e3a8a;
        }
        .debug-info {
            margin-top: 30px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>' . ($language == 'ja' ? '教授詳細' : 'Professor Details') . '</h1>';

// Try to identify the professor - first by ID, then by name
$found = false;

// Check by ID first
if ($professor_id > 0) {
    if ($professor_id == 1) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '三次 仁' : 'Jin Mitsugi') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? 'データサイエンス' : 'Data Science') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '月曜日・水曜日 13:00-15:00' : 'Monday & Wednesday 13:00-15:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': mitsugi@example.com</p>
        </div>';
        $found = true;
    } elseif ($professor_id == 2) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '加茂 具樹' : 'Tomoki Kamo') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '火曜日・木曜日 10:00-12:00' : 'Tuesday & Thursday 10:00-12:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': kamo@example.com</p>
        </div>';
        $found = true;
    } elseif ($professor_id == 3) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '清水 匠' : 'Takumi Shimizu') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '水曜日 15:00-17:00' : 'Wednesday 15:00-17:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': shimizu@example.com</p>
        </div>';
        $found = true;
    } elseif ($professor_id == 4) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '木原 盾' : 'Tate Kihara') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '金曜日 10:00-12:00' : 'Friday 10:00-12:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': kihara@example.com</p>
        </div>';
        $found = true;
    } elseif ($professor_id == 5) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '鈴木 治夫' : 'Haruo Suzuki') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '環境情報学' : 'Environment and Information') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '月曜日・金曜日 9:00-11:00' : 'Monday & Friday 9:00-11:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': suzuki@example.com</p>
        </div>';
        $found = true;
    } elseif ($professor_id == 6) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '田中 浩也' : 'Hiroya Tanaka') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '環境情報学' : 'Environment and Information') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '水曜日・木曜日 13:00-15:00' : 'Wednesday & Thursday 13:00-15:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': tanaka@example.com</p>
        </div>';
        $found = true;
    }
} 
// If not found by ID, try using professor name
else if (!empty($professor_name)) {
    if (strcasecmp($professor_name, 'JinMitsugi') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '三次 仁' : 'Jin Mitsugi') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? 'データサイエンス' : 'Data Science') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '月曜日・水曜日 13:00-15:00' : 'Monday & Wednesday 13:00-15:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': mitsugi@example.com</p>
        </div>';
        $found = true;
    } elseif (strcasecmp($professor_name, 'TomokiKamo') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '加茂 具樹' : 'Tomoki Kamo') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '火曜日・木曜日 10:00-12:00' : 'Tuesday & Thursday 10:00-12:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': kamo@example.com</p>
        </div>';
        $found = true;
    } elseif (strcasecmp($professor_name, 'TakumiShimizu') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '清水 匠' : 'Takumi Shimizu') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '水曜日 15:00-17:00' : 'Wednesday 15:00-17:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': shimizu@example.com</p>
        </div>';
        $found = true;
    } elseif (strcasecmp($professor_name, 'TateKihara') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '木原 盾' : 'Tate Kihara') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '政策管理学' : 'Policy Management') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '金曜日 10:00-12:00' : 'Friday 10:00-12:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': kihara@example.com</p>
        </div>';
        $found = true;
    } elseif (strcasecmp($professor_name, 'HaruoSuzuki') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '鈴木 治夫' : 'Haruo Suzuki') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '環境情報学' : 'Environment and Information') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '月曜日・金曜日 9:00-11:00' : 'Monday & Friday 9:00-11:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': suzuki@example.com</p>
        </div>';
        $found = true;
    } elseif (strcasecmp($professor_name, 'HiroyaTanaka') == 0) {
        echo '<div class="professor-info">
            <h2>' . ($language == 'ja' ? '田中 浩也' : 'Hiroya Tanaka') . '</h2>
            <p>' . ($language == 'ja' ? '学部' : 'Department') . ': ' . ($language == 'ja' ? '環境情報学' : 'Environment and Information') . '</p>
            <p>' . ($language == 'ja' ? 'オフィスアワー' : 'Office Hours') . ': ' . ($language == 'ja' ? '水曜日・木曜日 13:00-15:00' : 'Wednesday & Thursday 13:00-15:00') . '</p>
            <p>' . ($language == 'ja' ? '連絡先' : 'Contact') . ': tanaka@example.com</p>
        </div>';
        $found = true;
    }
}

// If professor not found with any method, redirect to the template page
if (!$found) {
    // Redirect to professor_page_template.php with the same parameters
    $queryParams = http_build_query($_GET);
    header("Location: professor_page_template.php?$queryParams");
    exit;
}

// Add back button
echo '<p><a href="home.php?lang=' . htmlspecialchars($language) . '" class="back-button">' . 
     ($language == 'ja' ? 'ホームに戻る' : 'Back to Home') . '</a></p>
    </div>
</body>
</html>';
