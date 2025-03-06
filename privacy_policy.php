<?php

header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - RateMyTeachersFC.com</title>
</head>
<body>
    <h1>Privacy Policy - RateMyTeachersFC.com</h1>

    <p><strong>Disclaimer:</strong> This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.</p>

    <?php
    $language = isset($_GET['lang']) ? $_GET['lang'] : 'en';

    if ($language == 'jp') {
    ?>
        <h2>プライバシーポリシー（日本語版）</h2>
        <p><strong>免責事項:</strong> 本サイトは慶應義塾大学の公式ウェブサイトではなく、学生が運営するプロジェクトです。本ウェブサイト上のサービスや広告によって生じた損失について、当サイトは責任を負いません。</p>
        
        <h3>1. 収集する情報</h3>
        <p>RateMyTeachersFC.comは、アカウント作成時に以下の情報を収集することがあります：</p>
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
    <?php
    } else {
    ?>
        <h2>Privacy Policy (English Version)</h2>
        <p><strong>Disclaimer:</strong> This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.</p>
        
        <h3>1. Information We Collect</h3>
        <p>RateMyTeachersFC.com may collect the following information when you create an account:</p>
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
        <p>RateMyTeachersFC.com does not share your personal information with third parties except in the following circumstances:</p>
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
        <p>If you have any questions or concerns about our privacy practices, please contact us at contact@ratemyteachersfc.com.</p>
    <?php
    }
    ?>

    <p><a href="?lang=en">English</a> | <a href="?lang=jp">日本語</a></p>
</body>
</html>