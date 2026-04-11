<?php
require_once 'config.php';

echo "<h3>Path Diagnostics</h3>";
echo "BOOKS_PATH: " . BOOKS_PATH . "<br>";
echo "COVERS_PATH: " . COVERS_PATH . "<br>";

echo "<h3>Directory Status</h3>";
foreach (['BOOKS_PATH' => BOOKS_PATH, 'COVERS_PATH' => COVERS_PATH] as $name => $path) {
    echo "Checking $name ($path):<br>";
    if (file_exists($path)) {
        echo "- Folder exists.<br>";
        echo "- Writable: " . (is_writable($path) ? "YES" : "NO") . "<br>";
        
        // Try to write a test file
        $testFile = $path . 'test_write.txt';
        if (@file_put_contents($testFile, 'test')) {
            echo "- <strong>SUCCESS:</strong> Successfully wrote a test file.<br>";
            @unlink($testFile);
        } else {
            echo "- <strong>FAILED:</strong> Could not write test file.<br>";
            $error = error_get_last();
            echo "- Error: " . ($error['message'] ?? 'Unknown error') . "<br>";
        }
    } else {
        echo "- Folder does not exist. Attempting to create...<br>";
        if (mkdir($path, 0777, true)) {
            echo "- Created successfully.<br>";
        } else {
            echo "- Failed to create.<br>";
        }
    }
    echo "<hr>";
}
?>
