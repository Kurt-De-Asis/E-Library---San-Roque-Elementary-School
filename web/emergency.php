<?php
// COMPLETELY INDEPENDENT DATABASE FIX SCRIPT
// No includes, no dependencies.
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Restoration Script (Standalone)</h1>";
echo "Step 1: Script started... OK<br>";

// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'elibrary_db';

echo "Step 2: Connecting to database... ";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("<span style='color:red'>FAILED: " . $conn->connect_error . "</span><br>Please check if WAMP MySQL is running and the database name is correct.");
}
echo "<span style='color:green'>SUCCESS</span><br>";

// Helper function
function add_col_safe($conn, $table, $column, $definition) {
    echo "Checking $table -> $column... ";
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            echo "<span style='color:green'>ADDED</span><br>";
        } else {
            echo "<span style='color:red'>ERROR: " . $conn->error . "</span><br>";
        }
    } else {
        echo "<span style='color:blue'>EXISTS</span><br>";
    }
}

echo "<h3>Restoring Tables</h3>";
$tables = [
    "settings" => "CREATE TABLE settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT) ENGINE=InnoDB",
    "activity_logs" => "CREATE TABLE activity_logs (log_id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, action VARCHAR(50), entity_type VARCHAR(50), entity_id VARCHAR(50), description TEXT, ip_address VARCHAR(45), user_agent TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
    "reading_history" => "CREATE TABLE reading_history (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, ebook_id INT, action ENUM('view','download'), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB"
];

foreach ($tables as $name => $sql) {
    echo "Table $name... ";
    if ($conn->query("SHOW TABLES LIKE '$name'")->num_rows == 0) {
        if ($conn->query($sql)) echo "<span style='color:green'>CREATED</span><br>";
        else echo "<span style='color:red'>FAIL: " . $conn->error . "</span><br>";
    } else {
        echo "<span style='color:blue'>OK</span><br>";
    }
}

echo "<h3>Restoring Columns</h3>";
add_col_safe($conn, 'ebooks', 'subject', "VARCHAR(100) AFTER category");
add_col_safe($conn, 'ebooks', 'content_type', "VARCHAR(50) DEFAULT 'book' AFTER grade_level");
add_col_safe($conn, 'ebooks', 'section_id', "INT NULL AFTER content_type");
add_col_safe($conn, 'ebooks', 'uploaded_by', "INT NULL AFTER file_path");
add_col_safe($conn, 'ebooks', 'is_approved', "TINYINT(1) DEFAULT 0");
add_col_safe($conn, 'ebooks', 'is_featured', "TINYINT(1) DEFAULT 0");
add_col_safe($conn, 'ebooks', 'is_active', "TINYINT(1) DEFAULT 1");
add_col_safe($conn, 'sections', 'teacher_id', "INT NULL AFTER grade_level");
add_col_safe($conn, 'users', 'section_id', "INT NULL AFTER grade_level");

echo "<br><h2 style='color:green'>ALL TASKS COMPLETED!</h2>";
echo "<p>You can now close this page and try your upload again.</p>";

$conn->close();
?>
