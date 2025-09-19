<?php
/**
 * Test Template Blast Functionality (Laravel Internal)
 * 
 * This file tests the template blast system that integrates:
 * 1. Google Sheets templates (OrderController@getJsonSetting)
 * 2. CS folder structure (OrderController@getJsonFolderCS)
 * 3. Excel file reading (OrderController@getJsonReadExcelFile)
 * 4. Template variable replacement
 * 
 * Run this via: php artisan tinker --execute="require 'test_template_blast.php';"
 * Or simply: php test_template_blast.php (after setting up Laravel bootstrap)
 */

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
// Test configuration using Laravel internal calls
$test_cases = [
    [
        'name' => 'ALFA',
        'product' => 'etawalin', 
        'type_blast' => 'konsul_2',
        'description' => 'Test ALFA CS with ETAWALIN product and KONSUL 2 blast type'
    ],
    [
        'name' => 'AMMA',
        'product' => 'etawaku',
        'type_blast' => 'konsultasi', 
        'description' => 'Test AMMA CS with ETAWAKU product and KONSULTASI blast type'
    ],
    [
        'name' => 'ALFA',
        'product' => 'bio insuleaf',
        'type_blast' => 'doa',
        'description' => 'Test ALFA CS with BIO INSULEAF product and DOA blast type'
    ]
];

echo "=== TEMPLATE BLAST SYSTEM TEST (Laravel Internal) ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Initialize OrderController
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;

$controller = app(OrderController::class);

/**
 * Test Step 1: Get template settings
 */
function testGetTemplateSettings($controller, $product, $type_blast) {
    echo "🔍 Step 1: Testing template settings...\n";
    
    try {
        $response = $controller->getJsonSetting();
        $data = $response->getData(true);
        
        if (isset($data['error'])) {
            echo "❌ Error: " . $data['error'] . "\n";
            return null;
        }
        
        if (!isset($data['data']) || empty($data['data'])) {
            echo "❌ No template data found\n";
            return null;
        }
        
        echo "✅ Found " . count($data['data']) . " template entries\n";
        
        // Find matching product with improved logic
        $matchingProduct = null;
        foreach ($data['data'] as $item) {
            if (!isset($item['PRODUK'])) continue;
            
            // Try exact match first
            if (strtolower($item['PRODUK']) === strtolower($product)) {
                $matchingProduct = $item;
                break;
            }
            
            // Try partial match
            if (stripos($item['PRODUK'], $product) !== false) {
                $matchingProduct = $item;
                break;
            }
        }
        
        // For special cases like ETAWALIN matching with NUTALIN
        if (!$matchingProduct) {
            $productMappings = [
                'etawalin' => ['nutalin', 'etawalin'],
                'nutriflakes' => ['nutalin', 'nutriflakes'],
                'bio insuleaf' => ['bio insuleaf', 'insuleaf'],
                'zymuno' => ['zymuno'],
                'etawaku' => ['etawaku'],
                'etawaherb' => ['etawaherb']
            ];
            
            $possibleMatches = $productMappings[strtolower($product)] ?? [strtolower($product)];
            foreach ($data['data'] as $item) {
                if (!isset($item['PRODUK'])) continue;
                foreach ($possibleMatches as $match) {
                    if (stripos($item['PRODUK'], $match) !== false) {
                        $matchingProduct = $item;
                        break 2;
                    }
                }
            }
        }
        
        if (!$matchingProduct) {
            echo "❌ No matching product found for: $product\n";
            echo "Available products: " . implode(', ', array_column($data['data'], 'PRODUK')) . "\n";
            return null;
        }
        
        echo "✅ Found matching product: " . $matchingProduct['PRODUK'] . "\n";
        
        // Map type_blast to template field
        $fieldMapping = [
            'perkenalan' => 'PERKENALAN',
            'reminder' => 'REMINDER', 
            'tips_1' => 'TIPS 1',
            'doa' => 'DOA',
            'konsul_1' => 'KONSUL 1',
            'konv_1' => 'KONV 1',
            'konv_2' => 'KONV 2',
            'tips_2' => 'TIPS 2',
            'konv_3' => 'KONV 3',
            'konsul_2' => 'KONSUL 2',
            'konv_4' => 'KONV 4',
            'konsultasi' => 'KONSULTASI',
            'konversi_1' => 'KONVERSI 1',
            'soft_selling' => 'SOFT SELLING',
            'konversi_2' => 'KONVERSI 2',
            'data_pasif' => 'DATA PASIF'
        ];
        
        $templateField = $fieldMapping[$type_blast] ?? null;
        if (!$templateField || !isset($matchingProduct[$templateField]) || empty($matchingProduct[$templateField])) {
            echo "❌ No template found for blast type: $type_blast\n";
            echo "Available fields: " . implode(', ', array_keys($fieldMapping)) . "\n";
            return null;
        }
        
        $template = $matchingProduct[$templateField];
        echo "✅ Found template for $type_blast: " . substr($template, 0, 50) . "...\n";
        
        return $template;
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Test Step 2: Get CS folder structure
 */
function testGetCSFolders($controller, $cs_name) {
    echo "\n🔍 Step 2: Testing CS folder structure...\n";
    
    try {
        $response = $controller->getJsonFolderCS();
        $data = $response->getData(true);
        
        if (isset($data['error'])) {
            echo "❌ Error: " . $data['error'] . "\n";
            return null;
        }
        
        if (!isset($data['files']) || empty($data['files'])) {
            echo "❌ No CS folders found\n";
            return null;
        }
        
        echo "✅ Found " . count($data['files']) . " CS entries\n";
        
        // Find matching CS folder
        $matchingFolder = null;
        foreach ($data['files'] as $folder) {
            if ($folder['isFolder'] && strtolower($folder['name']) === strtolower($cs_name)) {
                $matchingFolder = $folder;
                break;
            }
        }
        
        if (!$matchingFolder) {
            echo "❌ No matching CS folder found for: $cs_name\n";
            echo "Available folders: " . implode(', ', array_column(array_filter($data['files'], function($f) { return $f['isFolder']; }), 'name')) . "\n";
            return null;
        }
        
        echo "✅ Found CS folder: " . $matchingFolder['name'] . " (ID: " . $matchingFolder['id'] . ")\n";
        
        return $matchingFolder['id'];
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Test Step 3: Get Excel files in CS folder
 */
function testGetCSExcelFiles($controller, $folder_id, $type_blast) {
    echo "\n🔍 Step 3: Testing Excel files in CS folder...\n";
    
    try {
        $response = $controller->getJsonFolderCS($folder_id);
        $data = $response->getData(true);
        
        if (isset($data['error'])) {
            echo "❌ Error: " . $data['error'] . "\n";
            return null;
        }
        
        if (!isset($data['files']) || empty($data['files'])) {
            echo "❌ No files found in CS folder\n";
            return null;
        }
        
        $excelFiles = array_filter($data['files'], function($file) {
            return $file['isExcelFile'];
        });
        
        echo "✅ Found " . count($excelFiles) . " Excel files\n";
        
        // Find matching excel file based on type_blast
        $typeBlastMapping = [
            'konsultasi' => ['konsultasi', 'konsul'],
            'doa' => ['doa'],
            'reminder' => ['reminder'],
            'konversi_1' => ['konversi'],
            'konversi_2' => ['konversi'],
            'soft_selling' => ['soft'],
            'data_pasif' => ['pasif'],
            'perkenalan' => ['perkenalan'],
            'tips_1' => ['tips'],
            'tips_2' => ['tips'],
            'konv_1' => ['konv', 'konversi'],
            'konv_2' => ['konv', 'konversi'],
            'konv_3' => ['konv', 'konversi'],
            'konv_4' => ['konv', 'konversi'],
            'konsul_1' => ['konsul', 'konsultasi'],
            'konsul_2' => ['konsul', 'konsultasi']
        ];
        
        $searchTerms = $typeBlastMapping[$type_blast] ?? [$type_blast];
        $matchingFile = null;
        
        foreach ($excelFiles as $file) {
            foreach ($searchTerms as $term) {
                if (stripos($file['name'], $term) !== false) {
                    $matchingFile = $file;
                    break 2;
                }
            }
        }
        
        if (!$matchingFile) {
            echo "❌ No matching Excel file found for blast type: $type_blast\n";
            echo "Available files: " . implode(', ', array_column($excelFiles, 'name')) . "\n";
            return null;
        }
        
        echo "✅ Found matching Excel file: " . $matchingFile['name'] . "\n";
        
        return $matchingFile['id'];
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Test Step 4: Read Excel file data
 */
function testReadExcelData($controller, $file_id) {
    echo "\n🔍 Step 4: Testing Excel file reading...\n";
    
    try {
        $request = new Request();
        $response = $controller->getJsonReadExcelFile($request, $file_id);
        $data = $response->getData(true);
        
        if (isset($data['error'])) {
            echo "❌ Error: " . $data['error'] . "\n";
            return null;
        }
        
        if (!isset($data['data']) || empty($data['data'])) {
            echo "❌ No customer data found in Excel file\n";
            return null;
        }
        
        echo "✅ Found " . count($data['data']) . " customer records\n";
        echo "✅ File: " . $data['file_name'] . "\n";
        echo "✅ Headers: " . implode(', ', $data['header']) . "\n";
        
        return $data['data'];
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Test Step 5: Process template with customer data
 */
function testTemplateProcessing($template, $customerData) {
    echo "\n🔍 Step 5: Testing template processing...\n";
    
    if (!$template || empty($customerData)) {
        echo "❌ Missing template or customer data\n";
        return;
    }
    
    echo "✅ Processing " . count($customerData) . " customer records...\n";
    echo "📝 Template: " . substr($template, 0, 100) . "...\n\n";
    
    $processedCount = 0;
    foreach ($customerData as $index => $customer) {
        if ($index >= 3) break; // Show only first 3 for testing
        
        $customerValues = array_values($customer);
        $phoneNumber = $customerValues[0] ?? '';
        $customerName = $customerValues[1] ?? '';
        $product = $customerValues[2] ?? '';
        $alias = $customerValues[3] ?? '';
        $blastType = $customerValues[4] ?? '';
        $csName = $customerValues[5] ?? '';
        
        // Replace template variables
        $processedMessage = $template;
        $processedMessage = str_replace('{{A}}', $phoneNumber, $processedMessage);
        $processedMessage = str_replace('{{B}}', $customerName, $processedMessage);
        $processedMessage = str_replace('{{C}}', $product, $processedMessage);
        $processedMessage = str_replace('{{D}}', $alias, $processedMessage);
        $processedMessage = str_replace('{{E}}', $blastType, $processedMessage);
        $processedMessage = str_replace('{{F}}', $csName, $processedMessage);
        $processedMessage = str_replace('{{G}}', $alias, $processedMessage); // G also maps to alias
        
        echo "📱 Customer " . ($index + 1) . ":\n";
        echo "   Phone: $phoneNumber\n";
        echo "   Name: $customerName\n";
        echo "   Message: " . substr(str_replace('\\n', ' ', $processedMessage), 0, 80) . "...\n\n";
        
        $processedCount++;
    }
    
    echo "✅ Successfully processed $processedCount messages\n";
}

/**
 * Run complete test for a test case
 */
function runCompleteTest($controller, $testCase) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🧪 TEST CASE: " . $testCase['description'] . "\n";
    echo "📋 CS: {$testCase['name']}, Product: {$testCase['product']}, Blast: {$testCase['type_blast']}\n";
    echo str_repeat("=", 60) . "\n";
    
    // Step 1: Get template
    $template = testGetTemplateSettings($controller, $testCase['product'], $testCase['type_blast']);
    if (!$template) return false;
    
    // Step 2: Get CS folder ID
    $folderId = testGetCSFolders($controller, $testCase['name']);
    if (!$folderId) return false;
    
    // Step 3: Get Excel file ID
    $fileId = testGetCSExcelFiles($controller, $folderId, $testCase['type_blast']);
    if (!$fileId) return false;
    
    // Step 4: Read customer data
    $customerData = testReadExcelData($controller, $fileId);
    if (!$customerData) return false;
    
    // Step 5: Process template
    testTemplateProcessing($template, $customerData);
    
    echo "\n✅ TEST CASE COMPLETED SUCCESSFULLY!\n";
    return true;
}

// Run all test cases
$successCount = 0;
$totalTests = count($test_cases);

foreach ($test_cases as $testCase) {
    try {
        if (runCompleteTest($controller, $testCase)) {
            $successCount++;
        }
    } catch (Exception $e) {
        echo "❌ Test failed with exception: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n";
}

// Final summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Successful tests: $successCount/$totalTests\n";
echo "❌ Failed tests: " . ($totalTests - $successCount) . "/$totalTests\n";
echo "📊 Success rate: " . round(($successCount / $totalTests) * 100, 2) . "%\n";

if ($successCount === $totalTests) {
    echo "\n🎉 ALL TESTS PASSED! The template blast system is working correctly.\n";
} else {
    echo "\n⚠️  Some tests failed. Please check the error messages above.\n";
}

echo "\n📝 Next steps:\n";
echo "1. Fix any failing tests\n";
echo "2. Test the frontend implementation\n";
echo "3. Verify the AJAX calls work correctly\n";
echo "4. Test with different CS names and products\n";

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
?>
