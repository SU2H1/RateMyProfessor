<?php
// SFC Student Resources Website
// Based on resources compiled by Cecilia Anna Davidsen

// Get language from cookie, query parameter, or browser setting
$language = 'en'; // Default

// First check query parameter (highest priority)
if (isset($_GET['lang']) && ($_GET['lang'] == 'ja' || $_GET['lang'] == 'en')) {
    $language = $_GET['lang'];
    setcookie('language', $language, time() + (86400 * 30), "/"); // 30 days
}
// Then check cookie
else if (isset($_COOKIE['language']) && ($_COOKIE['language'] == 'ja' || $_COOKIE['language'] == 'en')) {
    $language = $_COOKIE['language'];
}
// Finally check browser language
else {
    // Get browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if ($browserLang == 'ja') {
            $language = 'ja';
        }
    }
    
    // Set cookie
    setcookie('language', $language, time() + (86400 * 30), "/"); // 30 days
}

// DEBUG: Force Japanese mode for testing
// $language = 'ja';

// Define translations
$translations = [
    'en' => [
        'title' => 'Tips and Tricks - Gaku Neko',
        'header' => 'Tips and Tricks',
        'description' => 'Helpful resources compiled for students',
        'back_link' => '← Back to Gaku Neko',
        'footer_text' => 'This website contains resources compiled for students.',
        'disclaimer' => 'Note: Some links are provided purely for educational purposes.',
        
        // Section titles
        'section_class' => 'Applying for Class/Courses',
        'section_navigation' => 'Navigation',
        'section_restaurant' => 'Restaurant Reviews',
        'section_browser' => 'Browser Recommendation',
        'section_dns' => 'DNS Recommendations:',
        'section_extensions' => 'Recommended Chromium/Firefox Extension',
        'section_price' => 'Price Comparison Tool/Websites',
        'section_student' => 'Student Discounts',
        'section_health' => 'Health Care',
        'section_reading' => 'Reading Material',
        'section_programming' => 'Programming',
        'section_llm' => 'Large Language Model (LLM)',
        'section_other' => 'Other',
        
        // Subsection titles
        'sub_sfc_bus' => 'SFC Bus:',
        'sub_general_nav' => 'General Navigation:',
        'sub_ad_blocker' => 'Ad Blocker:',
        'sub_fmhy' => 'FMHY SafeGuard:',
        'sub_sponsor' => 'Sponsor Block:',
        'sub_userscript' => 'Userscript manager:',
        'sub_password' => 'Password Manager:',
        'sub_writing' => 'Writing Assistance:',
        'sub_dark' => 'Dark Mode:',
        'sub_translation' => 'Translation:',
        'sub_discount' => 'Discount/Coupon Finder:',
        'sub_price_tracker' => 'Amazon Price Tracker:',
        'sub_review_checker' => 'Amazon Bot Review Checker:',
        'sub_paywall' => 'News Website Paywall Bypass (Do Not Use, Displayed Only For Educational Use):',
        'sub_shopping' => 'Online Shopping:',
        'sub_travel' => 'Traveling:',
        'sub_pirated' => 'Pirated Reading Material/Textbook Website (Do Not Use, Displayed Only For Educational Use):',
        'sub_official' => 'Official Reading Material/Textbook Website:',
        
        // Paragraphs and notes
        'chrome_desc' => 'Recommended for General Use (Cannot Install Adblock)',
        'brave_desc' => 'Improved Security with Built in Adblock while being Chromium Based (Cannot Install Alternative Adblock). Can download YouTube videos on the Phone version for offline replay.',
        'firefox_desc' => 'Recommended for General Use While Being Privacy Focused (Can install Ad block)',
        'librewolf_desc' => 'Extremely Privacy Focused Firefox Browser with Adblock(uBlock Origin) Installed',
        'mullvad_desc' => 'Extremely Privacy Focused Beginner Friendly Firefox Browser with Adblock(uBlock Origin) Installed',
        'google_dns_desc' => 'Improves internet speed drastically, however follows Google\'s Privacy Policy. Good for general use.',
        'cloudflare_dns_desc' => 'An alternative DNS to Google\'s with better privacy. Does not keep any logs of user activity. Has the downside of being slower than Google DNS.',
        'quad9_desc' => 'Best DNS if you care about your security and privacy (Maintained by a non-profit). Has options that block malicious domains with the downside of being slower compared to the two above.',
        'adblocker_desc' => 'Blocks Ads from popping up on your browser, and yes you can experience YouTube (and more) Ad free.',
        'fmhy_desc' => 'An extension that detects starred, safe, unsafe or potentially unsafe sites.',
        'sponsor_desc' => 'Skips YouTube video sponsor segment.',
        'revanced_desc' => 'Use YouTube, Spotify, X/Twitter, Reddit etc Ad Free.',
        'signal_desc' => 'An alternative Messaging App to Line, Whatsapp, Telegram, Facebook Messenger etc without Ads and logs of conversation. Has one of the best encryption with censorship circumvention.',
        'spicetify_desc' => 'A custom client for Spotify with improved performance with the option to add extensions or change the aesthetic of the app.',
        'camera_note' => '*Bic Camera and Yodobashi Camera\'s physical stores have a policy where they need to provide you with the cheapest price, so if you check 価格.com and see that another store is selling the product cheaper than Bic Camera or Yodobashi Camera, you can ask staff to decrease the price.',
        'kakaku_note' => '← Good for price checking general stuff sold in Japan.',
        'keepa_note' => '← Good for checking price history of a certain item.',
        'travel_note' => '← Good for price checking domestic (Japanese) hotels.',
        'sky_note' => '← Good for price checking plane tickets.',
        'personal_note' => '← Personal Recommendation',
        'library_note' => '← You need a library card to borrow books. In order to make a library card, you need to bring something that can prove your identity and address (i.e student ID and mail you have received).',
        'chatgpt_note' => '← Most Popular Large Language Model, good for most uses. Can be tailored or "improved" by installing extensions.',
        'gemini_note' => '← Google\'s LLM with fact checking functionality.',
        'notebook_note' => '← Make your tailored LLM, only references sources (PDFs, Websites, YouTube Videos and MP3) you uploaded. Even makes Podcasts based on the material.',
        'aistudio_note' => '← Use Gemini API for free. Test out unreleased but latest version of Google\'s LLM.',
        'claude_note' => '← Personally best LLM for coding and having it write natural sounding texts.',
        'deepseek_note' => '← An Open Source LLM created by a Chinese hedge fund High-Flyer. Regarded to be one of the best performing LLM.',
        'perplexity_note' => '← A free AI search engine. Based on my experience Gemini, ChatGPT Search and DeepSeek search provide similar results.',
        'lmstudio_note' => '← An app that allows you to run LLM locally, resulting in no data going to Big Tech\'s server. Can be used to run DeepSeek on your hardware.',
        'ollama_note' => '← Use these two applications to run any (Open Source including DeepSeek) LLM on your computer without accessing any servers.',
        'thereisai_note' => '← A database that has information on tons of AI tools.',
        'unethical_note' => '← Not Recommended due to Unethical Business Practices.'
    ],
    'ja' => [
        'title' => 'ヒントとコツ - Gaku Neko',
        'header' => 'ヒントとコツ',
        'description' => '学生のために集められた役立つリソース',
        'back_link' => '← Gaku Nekoに戻る',
        'footer_text' => 'このウェブサイトには学生のために集めたリソースが含まれています。',
        'disclaimer' => '注：一部のリンクは教育目的のためだけに提供されています。',
        
        // Section titles
        'section_class' => '授業登録',
        'section_navigation' => '交通案内',
        'section_restaurant' => 'レストランのレビュー',
        'section_browser' => 'ブラウザの推奨',
        'section_dns' => 'DNS推奨:',
        'section_extensions' => '推奨ChromiumおよびFirefoxの拡張機能',
        'section_price' => '価格比較ツール・サイト',
        'section_student' => '学割',
        'section_health' => '医療',
        'section_reading' => '読書資料',
        'section_programming' => 'プログラミング',
        'section_llm' => '大規模言語モデル (LLM)',
        'section_other' => 'その他',
        
        // Subsection titles
        'sub_sfc_bus' => 'SFCバス:',
        'sub_general_nav' => '一般的な交通案内:',
        'sub_ad_blocker' => '広告ブロッカー:',
        'sub_fmhy' => 'FMHY SafeGuard:',
        'sub_sponsor' => 'スポンサーブロック:',
        'sub_userscript' => 'ユーザースクリプトマネージャー:',
        'sub_password' => 'パスワードマネージャー:',
        'sub_writing' => '文章作成支援:',
        'sub_dark' => 'ダークモード:',
        'sub_translation' => '翻訳:',
        'sub_discount' => '割引・クーポン検索:',
        'sub_price_tracker' => 'Amazonの価格追跡:',
        'sub_review_checker' => 'Amazonボットレビューチェッカー:',
        'sub_paywall' => '使うべきで無いニュースサイトのペイウォールバイパス（使用しないでください、教育目的のみで表示）:',
        'sub_shopping' => 'オンラインショッピング:',
        'sub_travel' => '旅行:',
        'sub_pirated' => '使ってはいけない海賊版の読書資料・教科書のウェブサイト（使用しないでください、教育目的のみで表示）:',
        'sub_official' => '公式の読書資料・教科書のウェブサイト:',
        
        // Paragraphs and notes
        'chrome_desc' => '一般的な使用に推奨（アドブロックのインストール不可）',
        'brave_desc' => 'Chromiumベースながら内蔵アドブロックによるセキュリティ強化（代替アドブロックのインストール不可）。スマホ版ではYouTube動画をオフライン再生用にダウンロード可能。',
        'firefox_desc' => 'プライバシー重視の一般使用向け（アドブロックのインストール可能）',
        'librewolf_desc' => 'アドブロック（uBlock Origin）がインストールされた、非常にプライバシーに焦点を当てたFirefoxブラウザ',
        'mullvad_desc' => 'アドブロック（uBlock Origin）がインストールされた、初心者に優しい非常にプライバシーに焦点を当てたFirefoxブラウザ',
        'google_dns_desc' => 'インターネット速度を大幅に改善しますが、Googleのプライバシーポリシーに従います。一般的な使用に適しています。',
        'cloudflare_dns_desc' => 'Googleより優れたプライバシーを持つ代替DNS。ユーザー活動のログを保持しません。Google DNSより遅いというデメリットがあります。',
        'quad9_desc' => 'セキュリティとプライバシーを重視する場合に最適なDNS（非営利団体により維持）。悪意のあるドメインをブロックするオプションがありますが、上記2つに比べて遅いというデメリットがあります。',
        'adblocker_desc' => 'ブラウザでのポップアップ広告をブロックします。YouTubeなどで広告なしの体験が可能です。',
        'fmhy_desc' => '星付き、安全、危険、または潜在的に危険なサイトを検出する拡張機能です。',
        'sponsor_desc' => 'YouTubeビデオのスポンサーセグメントをスキップします。',
        'revanced_desc' => 'YouTube、Spotify、X/Twitter、Redditなどを広告なしで使用できます。',
        'signal_desc' => 'Line、WhatsApp、Telegram、Facebook Messengerなどの代替メッセージングアプリで、広告や会話のログがありません。最高の暗号化と検閲回避の一つを備えています。',
        'spicetify_desc' => '拡張機能の追加やアプリの外観変更オプションを備えた、パフォーマンスが向上したSpotifyのカスタムクライアント。',
        'camera_note' => '*ビックカメラとヨドバシカメラの実店舗では、最安値を提供する方針があります。価格.comで他の店舗がより安く販売しているのを確認したら、ビックカメラやヨドバシカメラのスタッフに価格を下げるよう依頼できます。',
        'kakaku_note' => '← 日本で販売されている一般的な商品の価格チェックに最適。',
        'keepa_note' => '← 特定のアイテムの価格履歴を確認するのに最適。',
        'travel_note' => '← 国内（日本）のホテルの価格チェックに最適。',
        'sky_note' => '← 航空券の価格チェックに最適。',
        'library_note' => '← 本を借りるには図書カードが必要です。図書カードを作るには、身分や住所を証明できるもの（学生証や受け取った郵便物など）を持参する必要があります。',
        'chatgpt_note' => '← 最も人気のある大規模言語モデル、ほとんどの用途に適しています。拡張機能をインストールすることでカスタマイズや「改善」が可能です。',
        'gemini_note' => '← ファクトチェック機能を備えたGoogleのLLM。',
        'notebook_note' => '← あなた専用のLLMを作成。アップロードしたソース（PDF、ウェブサイト、YouTubeビデオ、MP3）のみを参照します。素材に基づいたポッドキャストも作成します。',
        'aistudio_note' => '← Gemini APIを無料で使用。Googleの未リリースだが最新バージョンのLLMをテスト可能。',
        'claude_note' => '← 個人的に、コーディングや自然な文章を書かせるのに最適なLLM。',
        'deepseek_note' => '← 中国のヘッジファンドHigh-Flyerが作成したオープンソースLLM。最高のパフォーマンスを持つLLMの一つとされています。',
        'perplexity_note' => '← 無料のAI検索エンジン。私の経験では、Gemini、ChatGPT検索、DeepSeek検索は似たような結果を提供します。',
        'lmstudio_note' => '← LLMをローカルで実行できるアプリ。Big Techのサーバーにデータが送信されません。DeepSeekをあなたのハードウェアで実行するのに使用できます。',
        'ollama_note' => '← これら2つのアプリケーションを使用して、サーバーにアクセスせずに任意の（DeepSeekを含むオープンソース）LLMをコンピューターで実行できます。',
        'thereisai_note' => '← 多数のAIツールに関する情報を持つデータベース。',
        'unethical_note' => '← 非倫理的なビジネス慣行のため推奨されません。'
    ]
];

// Set language for UI
$t = $translations[$language];

// Create language switch URL
$switchLang = $language == 'ja' ? 'en' : 'ja';
$switchText = $language == 'ja' ? 'English' : '日本語';
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1649331460122770"
    crossorigin="anonymous"></script>
    <title><?php echo $t['title']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        header {
            background-color: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        h1, h2 {
            color: #003366;
        }
        ul {
            margin-bottom: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        footer {
            margin-top: 30px;
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .disclaimer {
            font-style: italic;
            color: #666;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .note {
            font-style: italic;
            color: #bf0000;
        }
        .language-switch {
            float: right;
            padding: 5px 10px;
            background-color: #eee;
            border-radius: 3px;
            margin-top: -40px;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo $t['header']; ?></h1>
        <p><?php echo $t['description']; ?></p>
        <p><a href="home.php"><?php echo $t['back_link']; ?></a></p>
        <div class="language-switch">
            <a href="?lang=<?php echo $switchLang; ?>"><?php echo $switchText; ?></a>
        </div>
    </header>

    <main>
        <section>
            <h2><?php echo $t['section_class']; ?></h2>
            <ul>
                <li><a href="https://www.keio.ac.jp/" target="_blank">Keio Website Portal</a></li>
                <li><a href="https://www.students.keio.ac.jp/en/sfc/class/registration/" target="_blank">Syllabus</a></li>
                <li><a href="https://sola.sfc.keio.ac.jp/faculty/students/sign_in"_blank">Course Registration</a></li>
                <li><a href="https://wellness.sfc.keio.ac.jp/" target="_blank">PE Registration</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_navigation']; ?></h2>
            <h3><?php echo $t['sub_sfc_bus']; ?></h3>
            <ul>
                <li><a href="https://apps.apple.com/jp/app/sfcway/id570439923" target="_blank">SFCWay (Apps Store)</a></li>
                <li><a href="https://bustimer.sfc.keio.ac.jp/" target="_blank">SFC Bus Navigation (Web)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_general_nav']; ?></h3>
            <ul>
                <li><a href="https://www.google.com/maps" target="_blank">Google Maps (Browser)</a></li>
                <li><a href="https://apps.apple.com/us/app/google-maps/id585027354" target="_blank">Google Maps (IOS)</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.maps" target="_blank">Google Maps (Android)</a></li>
                <li><a href="https://apps.apple.com/jp/app/navitime/id299471902" target="_blank">NAVITIME (IOS)</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.navitime.local.navitime" target="_blank">NAVITIME (Android)</a></li>
                <li><a href="https://apps.apple.com/jp/app/yahoo-%E4%B9%97%E6%8F%9B%E6%A1%88%E5%86%85/id291676451" target="_blank">Yahoo!乗換案内 (IOS)</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=jp.co.yahoo.android.apps.transit" target="_blank">Yahoo!乗換案内 (Android)</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_restaurant']; ?></h2>
            <ul>
                <li><a href="https://www.google.com/maps" target="_blank">Google Maps (Browser)</a></li>
                <li><a href="https://apps.apple.com/us/app/google-maps/id585027354" target="_blank">Google Maps (IOS)</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.maps" target="_blank">Google Maps (Android)</a></li>
                <li><a href="https://tabelog.com/" target="_blank">Tabelog (Browser)</a></li>
                <li><a href="https://play.google.com/store/apps/details?id=com.kakaku.tabelog" target="_blank">Tabelog (Android)</a></li>
                <li><a href="https://apps.apple.com/jp/app/%E9%A3%9F%E3%81%B9%E3%83%AD%E3%82%B0/id327103054" target="_blank">Tabelog (IOS)</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_browser']; ?></h2>
            <ul>
                <li><a href="https://www.google.com/chrome/" target="_blank">Chrome</a></li>
            </ul>
            <p><?php echo $t['chrome_desc']; ?></p>
            
            <ul>
                <li><a href="https://brave.com/" target="_blank">Brave</a></li>
            </ul>
            <p><?php echo $t['brave_desc']; ?></p>
            
            <ul>
                <li><a href="https://www.mozilla.org/firefox/" target="_blank">Firefox</a></li>
            </ul>
            <p><?php echo $t['firefox_desc']; ?></p>
            
            <ul>
                <li><a href="https://librewolf.net/" target="_blank">Librewolf</a></li>
            </ul>
            <p><?php echo $t['librewolf_desc']; ?></p>
            
            <ul>
                <li><a href="https://mullvad.net/en/browser" target="_blank">Mullvad Browser</a></li>
            </ul>
            <p><?php echo $t['mullvad_desc']; ?></p>
        </section>

        <section>
            <h2><?php echo $t['section_dns']; ?></h2>
            <ul>
                <li><a href="https://developers.google.com/speed/public-dns" target="_blank">Google DNS (8.8.8.8)</a></li>
            </ul>
            <p><?php echo $t['google_dns_desc']; ?></p>
            
            <ul>
                <li><a href="https://1.1.1.1/" target="_blank">Cloudflare DNS (1.1.1.1)</a></li>
            </ul>
            <p><?php echo $t['cloudflare_dns_desc']; ?></p>
            
            <ul>
                <li><a href="https://www.quad9.net/" target="_blank">Quad9 (9.9.9.9)</a></li>
            </ul>
            <p><?php echo $t['quad9_desc']; ?></p>
        </section>

        <section>
            <h2><?php echo $t['section_extensions']; ?></h2>
            <h3><?php echo $t['sub_ad_blocker']; ?></h3>
            <p><?php echo $t['adblocker_desc']; ?></p>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/ublock-origin/cjpalhdlnbpafiamejdnhcphjbkeiagm" target="_blank">uBlock Origin (Chromium) - Open Source</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/ublock-origin/" target="_blank">uBlock Origin (Firefox) - Open Source</a></li>
            </ul>
            
            <h3><?php echo $t['sub_fmhy']; ?></h3>
            <p><?php echo $t['fmhy_desc']; ?></p>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/fmhy-safeguard/pjcgkdipkkmdlmkagckhjpdnkgbppqej" target="_blank">FMHY SafeGuard (Chromium) - Open Source</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/fmhy-safeguard/" target="_blank">FMHY SafeGuard (Firefox) - Open Source</a></li>
            </ul>
            
            <h3><?php echo $t['sub_sponsor']; ?></h3>
            <p><?php echo $t['sponsor_desc']; ?></p>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/sponsorblock-for-youtube/mnjggcdmjocbbbhaepdhchncahnbgone" target="_blank">SponsorBlock for YouTube (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/sponsorblock/" target="_blank">SponsorBlock for YouTube (Firefox)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_userscript']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/tampermonkey/dhdgffkkebhmkfjojejmpbldmpobfkfo" target="_blank">Tampermonkey (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/tampermonkey/" target="_blank">Tampermonkey (Firefox)</a></li>
                <li><a href="https://chrome.google.com/webstore/detail/violentmonkey/jinjaccalgkegednnccohejagnlnfdag" target="_blank">Violentmonkey (Chromium) - Open Source</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/violentmonkey/" target="_blank">Violentmonkey (Firefox) - Open Source</a></li>
            </ul>
            
            <h3><?php echo $t['sub_password']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/bitwarden-free-password-m/nngceckbapebfimnlniiiahkandclblb" target="_blank">Bitwarden (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/bitwarden-password-manager/" target="_blank">Bitwarden (Firefox)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_writing']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/grammarly-grammar-checker/kbfnbcaeplbcioakkpcpgfkobkghlhen" target="_blank">Grammarly (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/grammarly-1/" target="_blank">Grammarly (Firefox)</a></li>
                <li><a href="https://chrome.google.com/webstore/detail/grammar-and-spell-checker/oldceeleldhonbafppcapldpdifcinji" target="_blank">LanguageTool (Chromium) - Open Source</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/languagetool/" target="_blank">LanguageTool (Firefox) - Open Source</a></li>
                <li><a href="https://github.com/search?q=Quillbot+Premium+Unlocker" target="_blank">Quillbot Premium Unlocker</a> ← Enable via Userscript manager (ie. Tampermonkey/Violentmonkey)</li>
            </ul>
            
            <h3><?php echo $t['sub_dark']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/dark-reader/eimadpbcbfnmbkopoojfekhnkhdbieeh" target="_blank">Dark Reader (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/darkreader/" target="_blank">Dark Reader (Firefox)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_translation']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/deepl-translate-reading-w/cofdbpoegempjloogbagkncekinflcnj" target="_blank">DeepL (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/deepl-translator/" target="_blank">DeepL (Firefox)</a></li>
                <li><a href="https://chrome.google.com/webstore/detail/google-translate/aapbdbdomjkkjkaonfhkkikfgjllcleb" target="_blank">Google Translate (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/to-google-translate/" target="_blank">Google Translate (Firefox)</a></li>
                <li><a href="https://github.com/search?q=Quillbot+Premium+Unlocker" target="_blank">Quillbot Premium Unlocker</a></li>
            </ul>
            
            <h3><?php echo $t['sub_discount']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/honey-automatic-coupons-r/bmnlcjabgnpnenekpadlanbbkooimhnj" target="_blank">Honey (Chromium)</a> <?php echo $t['unethical_note']; ?></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/honey/" target="_blank">Honey (Firefox)</a> <?php echo $t['unethical_note']; ?></li>
                <li><a href="https://chrome.google.com/webstore/detail/syrup-automatic-coupons-c/fblkbidhbaddnabbempnpfdlgopjlnpc" target="_blank">Syrup (Chromium) - Open Source</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/syrup/" target="_blank">Syrup (Firefox) - Open Source</a></li>
            </ul>
            
            <h3><?php echo $t['sub_price_tracker']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/keepa-amazon-price-tracke/neebplgakaahbhdphmkckjjcegoiijjo" target="_blank">Keepa (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/keepa/" target="_blank">Keepa (Firefox)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_review_checker']; ?></h3>
            <ul>
                <li><a href="https://chrome.google.com/webstore/detail/fakespot-detect-fake-amaz/nakplnnackehceedgkgkokbgbmfghain" target="_blank">Fakespot (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/fakespot-detector/" target="_blank">Fakespot (Firefox)</a></li>
                <li><a href="https://chrome.google.com/webstore/detail/sakura-checker-plus/nkjciiojcnhakemkbjlmhdojmgflbkpa" target="_blank">Sakura Checker Plus (Chromium)</a></li>
                <li><a href="https://addons.mozilla.org/ja/firefox/addon/sakura-checker-plus/" target="_blank">Sakura Checker Plus (Firefox)</a></li>
            </ul>
            
            <h3><?php echo $t['sub_paywall']; ?></h3>
            <ul>
                <li><a href="https://gitflic.ru/project/magnolia1234/bypass-paywalls-chrome-clean#installation" target="_blank">Bypass Paywalls (Chromium) - Open Source</a></li>
                <li><a href="https://gitflic.ru/project/magnolia1234/bypass-paywalls-firefox-clean#installation" target="_blank">Bypass Paywalls (Firefox) - Open Source</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_price']; ?></h2>
            <h3><?php echo $t['sub_shopping']; ?></h3>
            <ul>
                <li><a href="https://kakaku.com/" target="_blank">価格.com</a> <?php echo $t['kakaku_note']; ?></li>
                <li><a href="https://chrome.google.com/webstore/detail/keepa-amazon-price-tracke/neebplgakaahbhdphmkckjjcegoiijjo" target="_blank">Keepa (Chromium)</a> <?php echo $t['keepa_note']; ?></li>
                <li><a href="https://addons.mozilla.org/en-US/firefox/addon/keepa/" target="_blank">Keepa (Firefox)</a> <?php echo $t['keepa_note']; ?></li>
            </ul>
            <p class="note"><?php echo $t['camera_note']; ?></p>
            
            <h3><?php echo $t['sub_travel']; ?></h3>
            <ul>
                <li><a href="https://travel.kakaku.com/" target="_blank">トラベル価格.com</a> <?php echo $t['travel_note']; ?></li>
                <li><a href="https://www.tripadvisor.com/" target="_blank">Tripadvisor</a></li>
                <li><a href="https://www.skyscanner.com/" target="_blank">Skyscanner</a> <?php echo $t['sky_note']; ?></li>
                <li><a href="https://www.google.com/travel/flights" target="_blank">Google Flights</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_student']; ?></h2>
            <ul>
                <li><a href="https://www.amazon.co.jp/b?node=2410972051&tag=su2h1-22" target="_blank">Amazon Prime</a></li>
                <li><a href="https://www.apple.com/jp/shop/education-pricing" target="_blank">Apple Products</a></li>
                <li><a href="https://www.spotify.com/jp/student/" target="_blank">Spotify</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_health']; ?></h2>
            <ul>
                <li><a href="https://www.students.keio.ac.jp/en/com/life/health/" target="_blank">Keio Medical Care Benefits</a></li>
                <li><a href="https://keiiku.gr.jp/" target="_blank">Shonan Keiiku Hospital</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_reading']; ?></h2>
            <h3><?php echo $t['sub_pirated']; ?></h3>
            <ul>
                <li><a href="https://liber3.eth.limo/" target="_blank">Liber3</a></li>
                <li><a href="https://annas-archive.org/" target="_blank">Anna's Archive</a></li>
                <li><a href="https://libgen.is/" target="_blank">Library Genesis</a></li>
                <li><a href="https://gitflic.ru/project/magnolia1234/bypass-paywalls-chrome-clean#installation" target="_blank">Bypass Paywalls (Chrome) - Open Source</a></li>
                <li><a href="https://gitflic.ru/project/magnolia1234/bypass-paywalls-firefox-clean#installation" target="_blank">Bypass Paywalls (Firefox) - Open Source</a></li>
            </ul>
            
            <h3><?php echo $t['sub_official']; ?></h3>
            <ul>
                <li><a href="https://www.lib.keio.ac.jp/sfc/" target="_blank">SFC Media Center</a></li>
                <li><a href="https://archive.org/" target="_blank">Internet Archive</a></li>
                <li><a href="https://www.lib.city.fujisawa.kanagawa.jp/index" target="_blank">Fujisawa City Library</a> <?php echo $t['library_note']; ?></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_programming']; ?></h2>
            <ul>
                <li><a href="https://github.com/SU2H1/fit2"_blank">FIT2 Answers</a></li>
                <li><a href="https://leetcode.com/" target="_blank">LeetCode</a></li>
                <li><a href="https://www.glassdoor.com/Interview/tech-interview-questions-SRCH_II.0,4_IL.5,16_IC2989922.htm" target="_blank">IT Technical Questions</a></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_llm']; ?></h2>
            <ul>
                <li><a href="https://chat.openai.com/" target="_blank">ChatGPT</a> <?php echo $t['chatgpt_note']; ?></li>
                <li><a href="https://gemini.google.com/" target="_blank">Gemini</a> <?php echo $t['gemini_note']; ?></li>
                <li><a href="https://notebooklm.google.com/" target="_blank">NotebookLM</a> <?php echo $t['notebook_note']; ?></li>
                <li><a href="https://aistudio.google.com/" target="_blank">Google AI Studio</a> <?php echo $t['aistudio_note']; ?></li>
                <li><a href="https://claude.ai/" target="_blank">Claude</a> <?php echo $t['claude_note']; ?></li>
                <li><a href="https://github.com/deepseek-ai/DeepSeek-LLM" target="_blank">DeepSeek - Open Source</a> <?php echo $t['deepseek_note']; ?></li>
                <li><a href="https://www.perplexity.ai/" target="_blank">Perplexity</a> <?php echo $t['perplexity_note']; ?></li>
                <li><a href="https://lmstudio.ai/" target="_blank">LM Studio</a> <?php echo $t['lmstudio_note']; ?></li>
                <li><a href="https://ollama.com/" target="_blank">Ollama</a> + <a href="https://chatboxai.app/" target="_blank">Chatbox AI</a> <?php echo $t['ollama_note']; ?></li>
                <li><a href="https://theresanaiforthat.com/" target="_blank">There's An AI For That</a> <?php echo $t['thereisai_note']; ?></li>
            </ul>
        </section>

        <section>
            <h2><?php echo $t['section_other']; ?></h2>
            <ul>
                <li><a href="https://github.com/revanced" target="_blank">Revanced (Android Only)</a></li>
            </ul>
            <p><?php echo $t['revanced_desc']; ?></p>
            
            <ul>
                <li><a href="https://signal.org/" target="_blank">Signal (Windows/Android/IOS)</a></li>
            </ul>
            <p><?php echo $t['signal_desc']; ?></p>
            
            <ul>
                <li><a href="https://spicetify.app/" target="_blank">Spicetify (Windows/MacOS/Linux) - Open Source</a></li>
            </ul>
            <p><?php echo $t['spicetify_desc']; ?></p>
        </section>
    </main>

    <footer>
        <p><?php echo $t['footer_text']; ?></p>
        <p class="disclaimer"><?php echo $t['disclaimer']; ?></p>
        <p><a href="home.php"><?php echo $t['back_link']; ?></a></p>
    </footer>
</body>
</html>
