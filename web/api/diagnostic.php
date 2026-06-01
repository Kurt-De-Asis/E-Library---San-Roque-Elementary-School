<?php
require_once 'config.php';

header('Content-Type: text/plain');

echo "=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "Script Filename: " . __FILE__ . "\n";
echo "Upload Root: " . __DIR__ . '/../uploads/books/' . "\n";

echo "\n=== Disabled Functions ===\n";
$disabled = ini_get('disable_functions');
echo $disabled ?: 'none';

echo "\n\n=== Memory ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";

echo "\n=== Output Buffering ===\n";
echo "Output Handler: " . ob_get_level() . " levels\n";
echo "output_buffering: " . (ini_get('output_buffering') ?: 'Off') . "\n";

echo "\n=== Database ===\n";
$conn = getDBConnection();
if ($conn) {
    echo "DB Connection: OK\n";
    echo "DB Host: " . DB_HOST . "\n";
    echo "DB Name: " . DB_NAME . "\n";

    $result = $conn->query("SELECT COUNT(*) as cnt FROM ebooks");
    $row = $result->fetch_assoc();
    echo "Total books in DB: " . $row['cnt'] . "\n";

    $result = $conn->query("SELECT ebook_id, title, file_path FROM ebooks WHERE file_path LIKE '%.mp4%'");
    echo "\n=== Video Books ===\n";
    while ($row = $result->fetch_assoc()) {
        $diskPath = __DIR__ . '/../uploads/books/' . $row['file_path'];
        $exists = file_exists($diskPath) ? 'YES' : 'NO';
        $size = file_exists($diskPath) ? filesize($diskPath) . ' bytes' : 'N/A';
        echo "ID: {$row['ebook_id']} | Title: {$row['title']}\n";
        echo "  File: {$row['file_path']}\n";
        echo "  Disk Path: $diskPath\n";
        echo "  Exists: $exists\n";
        echo "  Size: $size\n\n";
    }
    $conn->close();
} else {
    echo "DB Connection: FAILED\n";
}

echo "\n=== Test readfile ===\n";
$testFile = __DIR__ . '/../uploads/books/';
$files = glob($testFile . '*.mp4');
if (count($files) > 0) {
    echo "Found " . count($files) . " MP4 files in uploads directory\n";
    $testFile = $files[0];
    echo "Test file: " . basename($testFile) . "\n";
    echo "Readable: " . (is_readable($testFile) ? 'YES' : 'NO') . "\n";
    echo "Size: " . filesize($testFile) . " bytes\n";
} else {
    echo "NO MP4 files found in uploads directory!\n";
    $allFiles = glob($testFile . '*');
    echo "All files in uploads: " . implode(', ', $allFiles) . "\n";
}

echo "\n=== Done ===\n";
