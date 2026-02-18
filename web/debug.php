<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

echo "<h2>File Check:</h2>";
$files = ['index.php', 'login.php', 'api/config.php', 'api/auth.php', 'api/ebooks.php'];
foreach ($files as $file) {
    $exists = file_exists($file);
    echo "<p><strong>$file:</strong> " . ($exists ? "✅ Exists" : "❌ Missing") . "</p>";
}

echo "<h2>Directory Structure:</h2>";
$dirs = ['api', 'css', 'js', 'assets', 'uploads'];
foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    echo "<p><strong>$dir/:</strong> " . ($exists ? "✅ Exists" : "❌ Missing") . "</p>";
}

echo "<h2>Test Links:</h2>";
echo "<p><a href='test.php'>Test PHP (Fixed)</a></p>";
echo "<p><a href='test.php'>Test PHP</a></p>";
echo "<p><a href='login.php'>Test Login Page</a></p>";
?>
