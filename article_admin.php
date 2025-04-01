<?php
// Basic session start
session_start();

// Determine current language - simple language toggle
$currentLang = isset($_COOKIE['language']) && $_COOKIE['language'] == 'ja' ? 'ja' : 'en';

// Set up translations
$translations = [
    'en' => [
        'pageTitle' => 'Article Management',
        'articleTitle' => 'Article Title',
        'titleHelp' => 'Maximum 150 characters',
        'articleImage' => 'Article Image',
        'articleBody' => 'Article Content',
        'author' => 'Author',
        'publishDate' => 'Publication Date',
        'submit' => 'Save Article',
        'charactersRemaining' => 'characters remaining',
        'success' => 'Article saved successfully!',
        'error' => 'Error saving article:',
        'fileError' => 'Error uploading image:'
    ],
    'ja' => [
        'pageTitle' => '記事管理',
        'articleTitle' => '記事タイトル',
        'titleHelp' => '最大150文字',
        'articleImage' => '記事画像',
        'articleBody' => '記事本文',
        'author' => '著者',
        'publishDate' => '公開日',
        'submit' => '記事を保存',
        'charactersRemaining' => '残り文字数',
        'success' => '記事が正常に保存されました！',
        'error' => '記事の保存中にエラーが発生しました：',
        'fileError' => '画像のアップロード中にエラーが発生しました：'
    ]
];

$t = $translations[$currentLang];

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $articleTitle = trim($_POST["article_title"]);
    $articleBody = trim($_POST["article_body"]);
    $author = trim($_POST["author"]);
    $publishDate = $_POST["publish_date"];
    
    // Validate data
    if (empty($articleTitle) || empty($articleBody) || empty($author) || empty($publishDate)) {
        $errorMessage = $t['error'] . ' ' . ($currentLang == 'ja' ? '全てのフィールドを入力してください。' : 'All fields are required.');
    } else {
        // Handle file upload
        $imagePath = '';
        if (isset($_FILES["article_image"]) && $_FILES["article_image"]["error"] == 0) {
            $targetDir = "uploads/articles/";
            
            // Create directory if it doesn't exist
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Generate unique filename
            $fileName = basename($_FILES["article_image"]["name"]);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '.' . $fileExt;
            $targetFilePath = $targetDir . $uniqueFileName;
            
            // Upload file
            if (move_uploaded_file($_FILES["article_image"]["tmp_name"], $targetFilePath)) {
                $imagePath = $targetFilePath;
            } else {
                $errorMessage = $t['fileError'] . ' ' . ($currentLang == 'ja' ? 'ファイルのアップロードに失敗しました。' : 'Failed to upload file.');
            }
        } else {
            $errorMessage = $t['fileError'] . ' ' . ($currentLang == 'ja' ? '画像は必須です。' : 'Image is required.');
        }
        
        // If no errors, save the article data to JSON file
        if (empty($errorMessage)) {
            $jsonFile = 'articles.json';
            
            // Read existing JSON file
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
            } else {
                $jsonData = ['articles' => []];
            }
            
            // Get next article ID
            $nextId = 0;
            if (!empty($jsonData['articles'])) {
                // Find the highest existing ID
                $ids = array_column($jsonData['articles'], 'id');
                $nextId = count($ids) > 0 ? max($ids) + 1 : 0;
            }
            
            // Create new article data
            $newArticle = [
                'id' => $nextId,
                'title' => $articleTitle,
                'body' => $articleBody,
                'author' => $author,
                'image' => $imagePath,
                'date' => $publishDate,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add new article to the array
            $jsonData['articles'][] = $newArticle;
            
            // Save updated data back to the JSON file
            if (file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT))) {
                $successMessage = $t['success'] . ' (ID: ' . $nextId . ')';
                
                // Reset form fields after successful save
                $_POST = [];
            } else {
                $errorMessage = $t['error'] . ' ' . ($currentLang == 'ja' ? 'JSONファイルへの書き込みに失敗しました。' : 'Failed to write to JSON file.');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['pageTitle']; ?> - Admin</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"], 
        input[type="date"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea {
            min-height: 300px;
            resize: vertical;
        }
        
        .help-text {
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        
        button {
            background-color: #1e3a8a;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #152a60;
        }
        
        .char-counter {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $t['pageTitle']; ?></h1>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <!-- Article Title -->
            <div class="form-group">
                <label for="article_title"><?php echo $t['articleTitle']; ?>:</label>
                <input type="text" id="article_title" name="article_title" maxlength="150" value="<?php echo isset($_POST['article_title']) ? htmlspecialchars($_POST['article_title']) : ''; ?>" required>
                <div class="help-text"><?php echo $t['titleHelp']; ?></div>
                <div class="char-counter">
                    <span id="title-counter">150</span> <?php echo $t['charactersRemaining']; ?>
                </div>
            </div>
            
            <!-- Article Image -->
            <div class="form-group">
                <label for="article_image"><?php echo $t['articleImage']; ?>:</label>
                <input type="file" id="article_image" name="article_image" accept="image/*" required>
            </div>
            
            <!-- Article Body -->
            <div class="form-group">
                <label for="article_body"><?php echo $t['articleBody']; ?>:</label>
                <textarea id="article_body" name="article_body" required><?php echo isset($_POST['article_body']) ? htmlspecialchars($_POST['article_body']) : ''; ?></textarea>
            </div>
            
            <!-- Author -->
            <div class="form-group">
                <label for="author"><?php echo $t['author']; ?>:</label>
                <input type="text" id="author" name="author" value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>" required>
            </div>
            
            <!-- Publication Date -->
            <div class="form-group">
                <label for="publish_date"><?php echo $t['publishDate']; ?>:</label>
                <input type="date" id="publish_date" name="publish_date" required>
            </div>
            
            <!-- Submit Button -->
            <button type="submit"><?php echo $t['submit']; ?></button>
        </form>
    </div>
    
    <script>
        // Character counter for title
        document.getElementById('article_title').addEventListener('input', function() {
            const maxLength = 150;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            document.getElementById('title-counter').textContent = remaining;
        });
        
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            document.getElementById('publish_date').value = `${year}-${month}-${day}`;
        });
    </script>
</body>
</html>