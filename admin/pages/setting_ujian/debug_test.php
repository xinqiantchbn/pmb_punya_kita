<?php
// File: debug_test.php
echo "=== AJAX ENDPOINT TEST ===\n\n";

// Test langsung ke endpoint
$url = "http://localhost/admin/index.php?page=setting_ujian&ajax_action=start_ujian";
echo "Testing URL: $url\n\n";

// Gunakan curl untuk test
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    echo "=== HEADERS ===\n";
    echo $headers . "\n";
    
    echo "=== BODY ===\n";
    
    // Coba parse JSON
    $json = json_decode($body);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Valid JSON:\n";
        echo json_encode($json, JSON_PRETTY_PRINT);
    } else {
        echo "NOT JSON (Error: " . json_last_error_msg() . ")\n";
        echo "First 500 chars:\n";
        echo substr($body, 0, 500) . "...\n";
        
        // Cari tanda-tanda error
        if (strpos($body, '<?php') !== false) {
            echo "\n⚠️ WARNING: Contains PHP tags!\n";
        }
        if (strpos($body, '<!DOCTYPE') !== false) {
            echo "⚠️ WARNING: Contains HTML document!\n";
        }
        if (strpos($body, 'Fatal error') !== false) {
            echo "❌ ERROR: Contains PHP fatal error!\n";
        }
        if (strpos($body, 'Parse error') !== false) {
            echo "❌ ERROR: Contains PHP parse error!\n";
        }
    }
    
    curl_close($ch);
} else {
    echo "CURL not available. Using file_get_contents...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === FALSE) {
        echo "Failed to fetch URL\n";
        $error = error_get_last();
        echo "Error: " . $error['message'] . "\n";
    } else {
        echo "Response:\n";
        echo $response . "\n";
    }
}

echo "\n=== RAW PHP INFO ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";

// Test database connection
echo "\n=== DATABASE TEST ===\n";
require_once 'config/database.php';

if ($conn) {
    echo "Database connection: SUCCESS\n";
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM ujian_setting");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Ujian setting count: " . $row['count'] . "\n";
    } else {
        echo "Query failed: " . $conn->error . "\n";
    }
} else {
    echo "Database connection: FAILED\n";
    echo "Error: " . mysqli_connect_error() . "\n";
}
?>