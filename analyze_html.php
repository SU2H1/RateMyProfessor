<?php
// Script to analyze HTML structure differences between course types

// Directory containing sample HTML files
$sampleDir = __DIR__ . '/sample_html';

// Course types to analyze
$courseTypes = [
    'korean_language' => 'ja',
    'korean_language_en' => 'en',
    'earth_environment' => 'ja',
    'earth_environment_en' => 'en',
    'data_science' => 'ja',
    'data_science_en' => 'en',
    'language_comm' => 'ja',
    'language_comm_en' => 'en'
];

// Function to extract course data from HTML
function extractCourseData($html, $language) {
    $data = [
        'language' => $language,
        'name' => 'Not found',
        'reg_number' => 'Not found',
        'field' => 'Not found',
        'credits' => 'Not found',
        'instructor' => 'Not found',
        'html_structure' => []
    ];
    
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);
    
    // Extract course name
    $nameElement = $xpath->query('//h2')->item(0);
    if ($nameElement) {
        $data['name'] = trim($nameElement->textContent);
    }
    
    // Extract registration number
    $regNumberText = ($language == 'en') ? "Registration Number" : "登録番号";
    $regNumberElement = $xpath->query('//dt[contains(text(), "' . $regNumberText . '")]/following-sibling::dd[1]')->item(0);
    if ($regNumberElement) {
        $data['reg_number'] = trim($regNumberElement->textContent);
    }
    
    // Extract field
    $fieldText = ($language == 'en') ? "Field" : "分野";
    $fieldElement = $xpath->query('//dt[contains(text(), "' . $fieldText . '")]/following-sibling::dd[1]')->item(0);
    if ($fieldElement) {
        $data['field'] = trim($fieldElement->textContent);
    } else {
        // Try alternate text for English field
        $fieldElement = $xpath->query('//dt[contains(text(), "Category")]/following-sibling::dd[1]')->item(0);
        if ($fieldElement) {
            $data['field'] = trim($fieldElement->textContent);
        }
    }
    
    // Extract credits
    $creditsText = ($language == 'en') ? "Unit" : "単位";
    $creditsElement = $xpath->query('//dt[contains(text(), "' . $creditsText . '")]/following-sibling::dd[1]')->item(0);
    if ($creditsElement) {
        $data['credits'] = trim($creditsElement->textContent);
    }
    
    // Extract instructor
    $instructorText = ($language == 'en') ? "Lecturer Name" : "授業教員名";
    $instructorElement = $xpath->query('//dt[contains(text(), "' . $instructorText . '")]/following-sibling::dd[1]')->item(0);
    if ($instructorElement) {
        $data['instructor'] = trim($instructorElement->textContent);
    }
    
    // Analyze HTML structure
    $data['html_structure'] = analyzeHtmlStructure($dom);
    
    return $data;
}

// Function to analyze HTML structure
function analyzeHtmlStructure($dom) {
    $structure = [];
    
    // Get all dt elements and check their text content
    $dts = $dom->getElementsByTagName('dt');
    foreach ($dts as $dt) {
        $structure['dt'][] = trim($dt->textContent);
    }
    
    // Get all h2 elements
    $h2s = $dom->getElementsByTagName('h2');
    foreach ($h2s as $h2) {
        $structure['h2'][] = trim($h2->textContent);
    }
    
    // Analyze div class attributes
    $divs = $dom->getElementsByTagName('div');
    $divClasses = [];
    foreach ($divs as $div) {
        if ($div->hasAttribute('class')) {
            $divClasses[] = $div->getAttribute('class');
        }
    }
    $structure['div_classes'] = array_unique($divClasses);
    
    return $structure;
}

// Process each course type
$results = [];
foreach ($courseTypes as $type => $language) {
    $singleCourseFile = $sampleDir . '/' . $type . '_single_course.html';
    
    if (file_exists($singleCourseFile)) {
        $html = file_get_contents($singleCourseFile);
        $data = extractCourseData($html, $language);
        $results[$type] = $data;
        
        echo "Analyzed $type (language: $language):\n";
        echo "  Name: {$data['name']}\n";
        echo "  Registration Number: {$data['reg_number']}\n";
        echo "  Field: {$data['field']}\n";
        echo "  Credits: {$data['credits']}\n";
        echo "  Instructor: {$data['instructor']}\n";
        
        echo "  HTML Structure:\n";
        echo "    DT elements: " . implode(", ", isset($data['html_structure']['dt']) ? $data['html_structure']['dt'] : ['none']) . "\n";
        echo "    Div classes: " . implode(", ", isset($data['html_structure']['div_classes']) ? $data['html_structure']['div_classes'] : ['none']) . "\n";
        echo "----------\n";
    } else {
        echo "Sample file for $type not found: $singleCourseFile\n";
    }
}

// Compare structures between Japanese and English versions of the same course type
foreach (['korean_language', 'earth_environment', 'data_science', 'language_comm'] as $baseType) {
    $jaType = $baseType;
    $enType = $baseType . '_en';
    
    if (isset($results[$jaType]) && isset($results[$enType])) {
        echo "\nComparison between $jaType and $enType:\n";
        
        // Compare registration numbers
        if ($results[$jaType]['reg_number'] != $results[$enType]['reg_number']) {
            echo "* Different registration numbers: {$results[$jaType]['reg_number']} vs {$results[$enType]['reg_number']}\n";
        } else {
            echo "✓ Same registration number: {$results[$jaType]['reg_number']}\n";
        }
        
        // Compare DT elements
        $jaDts = isset($results[$jaType]['html_structure']['dt']) ? $results[$jaType]['html_structure']['dt'] : [];
        $enDts = isset($results[$enType]['html_structure']['dt']) ? $results[$enType]['html_structure']['dt'] : [];
        
        $jaCount = count($jaDts);
        $enCount = count($enDts);
        
        if ($jaCount != $enCount) {
            echo "* Different number of DT elements: $jaCount vs $enCount\n";
        } else {
            echo "✓ Same number of DT elements: $jaCount\n";
        }
        
        // Compare div classes
        $jaDivs = isset($results[$jaType]['html_structure']['div_classes']) ? $results[$jaType]['html_structure']['div_classes'] : [];
        $enDivs = isset($results[$enType]['html_structure']['div_classes']) ? $results[$enType]['html_structure']['div_classes'] : [];
        
        $jaCount = count($jaDivs);
        $enCount = count($enDivs);
        
        if ($jaCount != $enCount) {
            echo "* Different number of div classes: $jaCount vs $enCount\n";
        } else {
            echo "✓ Same number of div classes: $jaCount\n";
        }
    }
}

// Look specifically for the problematic courses
echo "\nLooking for problematic course patterns...\n";

// Check for Korean language course with listening skills
if (isset($results['korean_language'])) {
    $koreanListeningFound = false;
    foreach ($results as $type => $data) {
        if (strpos($data['name'], '聴解') !== false || strpos($data['name'], 'Listening') !== false) {
            $koreanListeningFound = true;
            echo "Found Korean Listening course in $type: {$data['name']} (Reg#: {$data['reg_number']})\n";
            echo "  Field: {$data['field']}\n";
            echo "  Instructor: {$data['instructor']}\n";
        }
    }
    
    if (!$koreanListeningFound) {
        echo "No Korean Listening course found in samples.\n";
    }
}

// Check for Earth Environment course
if (isset($results['earth_environment_en'])) {
    echo "Earth Environment course details (English):\n";
    echo "  Name: {$results['earth_environment_en']['name']}\n";
    echo "  Reg#: {$results['earth_environment_en']['reg_number']}\n";
    echo "  Field: {$results['earth_environment_en']['field']}\n";
}

// Output recommendation based on findings
echo "\nRecommendation based on analysis:\n";
echo "1. Check if the HTML structure differs for the Korean language courses vs. other courses\n";
echo "2. Verify the registration number extraction logic for all course types\n";
echo "3. Make sure the field extraction works with both 'Field' and 'Category' labels in English\n";
echo "4. Check for any inconsistencies in how translations are stored in the JSON structure\n";