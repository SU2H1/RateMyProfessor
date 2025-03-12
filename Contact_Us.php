<?php

header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RateMyTeacherSFC.com</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #1a3c6e;
        }
        .contact-form {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type=text], input[type=email], textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
        }
        button {
            background-color: #1a3c6e;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0f2952;
        }
        .contact-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .language-selector {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <h1>Contact Us - RateMyTeachersFC.com</h1>

    <p><strong>Disclaimer:</strong> This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.</p>

    <?php
    $language = isset($_GET['lang']) ? $_GET['lang'] : 'en';

    if ($language == 'jp') {
    ?>
        <h2>お問い合わせ（日本語版）</h2>
        <p>RateMyTeacherSFC.comをご利用いただきありがとうございます。ご質問、ご意見、またはサポートが必要な場合は、以下のフォームにご記入いただくか、直接メールでお問い合わせください。</p>
        
        <div class="contact-form">
            <form action="#" method="post">
                <div class="form-group">
                    <label for="name">お名前：</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">メールアドレス：</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">件名：</label>
                    <select id="subject" name="subject">
                        <option value="general">一般的な質問</option>
                        <option value="account">アカウントに関する問題</option>
                        <option value="review">レビューに関する問題</option>
                        <option value="bug">バグ報告</option>
                        <option value="suggestion">改善提案</option>
                        <option value="privacy">プライバシーに関する質問</option>
                        <option value="other">その他</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">メッセージ：</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                
                <button type="submit">送信</button>
            </form>
        </div>
        
        <div class="contact-info">
            <h3>直接お問い合わせ</h3>
            <p>フォームを使用せずに直接お問い合わせいただくこともできます：</p>
            <p><strong>メール：</strong> <a href="mailto:ratemyteachersfc@proton.me">ratemyteachersfc@proton.me</a></p>
            <p><strong>対応時間：</strong> 平日9:00〜18:00（日本時間）</p>
            <p><strong>返信について：</strong> すべてのお問い合わせには、通常48時間以内に返信いたします。</p>
        </div>
    <?php
    } else {
    ?>
        <h2>Contact Us (English Version)</h2>
        <p>Thank you for using RateMyTeacherSFC.com. If you have any questions, comments, or need support, please fill out the form below or contact us directly via email.</p>
        
        <div class="contact-form">
            <form action="#" method="post">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <select id="subject" name="subject">
                        <option value="general">General Inquiry</option>
                        <option value="account">Account Issue</option>
                        <option value="review">Review Issue</option>
                        <option value="bug">Bug Report</option>
                        <option value="suggestion">Suggestion</option>
                        <option value="privacy">Privacy Question</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required></textarea>
                </div>
                
                <button type="submit">Submit</button>
            </form>
        </div>
        
        <div class="contact-info">
            <h3>Direct Contact</h3>
            <p>You can also contact us directly without using the form:</p>
            <p><strong>Email:</strong> <a href="mailto:ratemyteachersfc@proton.me">ratemyteachersfc@proton.me</a></p>
            <p><strong>Response Hours:</strong> Weekdays 9:00 AM - 6:00 PM (Japan Standard Time)</p>
            <p><strong>Response Time:</strong> We typically respond to all inquiries within 48 hours.</p>
        </div>
    <?php
    }
    ?>

    <div class="language-selector">
        <p><a href="?lang=en">English</a> | <a href="?lang=jp">日本語</a></p>
    </div>
</body>
</html>