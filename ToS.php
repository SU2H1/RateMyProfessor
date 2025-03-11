<?php

header("Content-Type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - RateMyTeachersFC.com</title>
</head>
<body>
    <h1>Terms and Conditions - RateMyTeachersFC.com</h1>

    <p><strong>Disclaimer:</strong> This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.</p>

    <?php
    $language = isset($_GET['lang']) ? $_GET['lang'] : 'en';

    if ($language == 'jp') {
    ?>
        <h2>日本語版</h2>
        <p><strong>免責事項:</strong> 本サイトは慶應義塾大学の公式ウェブサイトではなく、学生が運営するプロジェクトです。本ウェブサイト上のサービスや広告によって生じた損失について、当サイトは責任を負いません。</p>
        
        <h3>1. はじめに</h3>
        <p>RateMyTeachersFC.comへようこそ。本サイトは慶應義塾大学SFCの学生が教授に関するレビューやフィードバックを共有するためのプラットフォームです。本ウェブサイトにアクセスまたは利用することにより、以下の利用規約に同意し、拘束されることとなります。</p>

        <h3>2. ユーザーコンテンツ</h3>
        <p>ユーザーは誠実に情報を提供し、不正確な内容や誹謗中傷を含む投稿を行わないようにしてください。</p>
        
        <h3>3. プライバシーと匿名性</h3>
        <p>ユーザーの匿名性を保つよう努めますが、完全な保証はできません。</p>
        
        <h3>4. アカウントの停止と終了</h3>
        <p>違反行為が発覚した場合、アカウントの停止または削除が行われる可能性があります。</p>
        
        <h3>5. 責任の制限</h3>
        <p>本サイトの利用に関連するいかなる損害についても、当サイトは責任を負いません。</p>
        
        <h3>6. 利用規約の変更</h3>
        <p>利用規約は予告なしに変更される場合があります。</p>
    <?php
    } else {
    ?>
        <h2>English Version</h2>
        <p><strong>Disclaimer:</strong> This is not an official website of Keio University. It is a student-run project. We are not responsible for any loss incurred by using services that are on or advertised on this website.</p>
        
        <h3>1. Introduction</h3>
        <p>Welcome to RateMyTeachersFC.com, a platform designed for Keio University SFC students to share reviews and feedback about professors. By accessing or using our website, you agree to comply with and be bound by the following terms and conditions.</p>

        <h3>2. User Content</h3>
        <p>Users must provide honest and fair assessments and refrain from posting false or defamatory content.</p>
        
        <h3>3. Privacy and Anonymity</h3>
        <p>While we strive to maintain user anonymity, we cannot guarantee complete anonymity in all circumstances.</p>
        
        <h3>4. Account Suspension and Termination</h3>
        <p>Users violating these terms may have their accounts suspended or terminated.</p>
        
        <h3>5. Limitation of Liability</h3>
        <p>RateMyTeachersFC.com is not liable for any damages resulting from the use of this website.</p>
        
        <h3>6. Changes to Terms and Conditions</h3>
        <p>We reserve the right to modify these Terms and Conditions at any time.</p>
    <?php
    }
    ?>

    <p><a href="?lang=en">English</a> | <a href="?lang=jp">日本語</a></p>
</body>
</html>
