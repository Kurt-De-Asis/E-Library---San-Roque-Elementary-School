<?php
echo "<h2>Environment Diagnostics (Deep Scan)</h2>";

// Check PHP version
echo "<p>PHP Version: " . PHP_VERSION . "</p>";

// Check extensions
$required_extensions = ['mysqli', 'json', 'session'];
echo "<h4>Extensions</h4><ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color: green;'>✓ Extension '$ext' is loaded</li>";
    } else {
        echo "<li style='color: red;'>✗ Extension '$ext' is NOT loaded</li>";
    }
}
echo "</ul>";

// Check config file
$config_file = 'api/config.php';
if (file_exists($config_file)) {
    echo "<p style='color: green;'>✓ File '$config_file' exists</p>";
    require_once $config_file;
    
    // Check DB Connection
    $conn = getDBConnection();
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Check Tables
        $required_tables = ['users', 'categories', 'ebooks', 'sections', 'reading_progress', 'bookmarks', 'activity_logs', 'password_resets', 'settings'];
        echo "<h4>Database Tables</h4><ul>";
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<li style='color: green;'>✓ Table '$table' exists</li>";
            } else {
                echo "<li style='color: red;'>✗ Table '$table' is MISSING!</li>";
            }
        }
        echo "</ul>";
        $conn->close();
    } else {
        echo "<p style='color: red;'>✗ Database connection failed. Check your DB credentials in '$config_file'.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ File '$config_file' is missing!</p>";
}

// Check writable directories
echo "<h4>Permissions</h4><ul>";
$dirs = ['uploads', 'uploads/books', 'uploads/covers', 'logs'];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "<li style='color: green;'>✓ Directory '$dir' is writable</li>";
    } else {
        echo "<li style='color: red;'>✗ Directory '$dir' is NOT writable or does not exist</li>";
    }
}
echo "</ul>";
?>
