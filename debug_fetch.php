<?php
// Debug script to fetch HTML from different course types

// Set maximum execution time
ini_set('max_execution_time', 300);
set_time_limit(300);

// Sample URLs for different course types
$urls = [
    'korean_language' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Btitle%5D=%E6%9C%9D%E9%AE%AE%E8%AA%9E&search%5Byear%5D=2024',
    'korean_language_en' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Btitle%5D=korean&search%5Byear%5D=2024',
    'earth_environment' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Btitle%5D=%E5%9C%B0%E7%90%83%E7%92%B0%E5%A2%83&search%5Byear%5D=2023',
    'earth_environment_en' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Btitle%5D=earth+environment&search%5Byear%5D=2023',
    'data_science' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F4.Fundamental+Subjects+-+Subjects+of+Data+Science+-+Data+Science+1&search%5Byear%5D=2024',
    'data_science_en' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F4.Fundamental+Subjects+-+Subjects+of+Data+Science+-+Data+Science+1&search%5Byear%5D=2024',
    'language_comm' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=ja&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F3.Fundamental+Subjects+-+Subjects+of+Language+Communication&search%5Byear%5D=2024',
    'language_comm_en' => 'https://syllabus.sfc.keio.ac.jp/courses?locale=en&search%5Bsfc_guide_title%5D=23%2F11%2F2014%2F3.Fundamental+Subjects+-+Subjects+of+Language+Communication&search%5Byear%5D=2024'
];

// Function to fetch URL content
function fetchUrlContent($url) {
    $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    // Get response headers to debug
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    $content = curl_exec($ch);
    
    // Split headers and body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($content, 0, $header_size);
    $body = substr($content, $header_size);

    if (curl_errno($ch)) {
        echo "Error fetching URL: " . curl_error($ch) . "\n";
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode != 200) {
        echo "HTTP Error: $httpCode for URL: $url\n";
    }

    curl_close($ch);
    return ['headers' => $headers, 'body' => $body];
}

// Create directory for samples if it doesn't exist
$sampleDir = __DIR__ . '/sample_html';
if (!file_exists($sampleDir)) {
    mkdir($sampleDir, 0755, true);
}

// Fetch and save each URL
foreach ($urls as $type => $url) {
    echo "Fetching $type from $url\n";
    
    $content = fetchUrlContent($url);
    
    if ($content) {
        // Save headers
        $headersFile = $sampleDir . '/' . $type . '_headers.txt';
        file_put_contents($headersFile, $content['headers']);
        echo "Saved headers to $headersFile\n";
        
        // Save body
        $bodyFile = $sampleDir . '/' . $type . '_body.html';
        file_put_contents($bodyFile, $content['body']);
        echo "Saved body to $bodyFile\n";
        
        // Now extract a single course example for detailed analysis
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content['body'], 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        // Find the first course listing
        $courseLi = $xpath->query('//div[@class="result"]/ul/li')->item(0);
        
        if ($courseLi) {
            // Save this single course HTML for detailed analysis
            $singleCourseFile = $sampleDir . '/' . $type . '_single_course.html';
            file_put_contents($singleCourseFile, $dom->saveHTML($courseLi));
            echo "Saved single course sample to $singleCourseFile\n";
            
            // Get basic information for verification
            $courseNameElement = $xpath->query('.//h2', $courseLi)->item(0);
            $courseName = $courseNameElement ? trim($courseNameElement->textContent) : 'Unknown Course';
            echo "Course name: $courseName\n";
            
            // Get registration number
            $regNumberText = (strpos($type, '_en') !== false) ? "Registration Number" : "登録番号";
            $regNumberElement = $xpath->query('.//dt[contains(text(), "' . $regNumberText . '")]/following-sibling::dd[1]', $courseLi)->item(0);
            $regNumber = $regNumberElement ? trim($regNumberElement->textContent) : 'Unknown';
            echo "Registration number: $regNumber\n";
        } else {
            echo "No course found in the page.\n";
        }
    }
    
    // Add a delay between requests
    sleep(2);
}

echo "Done! Sample HTML files have been saved to $sampleDir\n";