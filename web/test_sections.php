<?php
require_once 'api/config.php';

echo "Testing sections database structure...\n";

$conn = getDBConnection();
if (!$conn) {
    die('Database connection failed\n');
}

// Check if sections table exists
$result = $conn->query('SHOW TABLES LIKE "sections"');
if ($result->num_rows == 0) {
    echo "Sections table does not exist! Creating it...\n";

    // Create sections table
    $create_sql = "CREATE TABLE sections (
        section_id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(100) NOT NULL,
        grade_level VARCHAR(50) NOT NULL,
        teacher_id INT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_section_grade (section_name, grade_level),
        INDEX idx_teacher (teacher_id),
        INDEX idx_grade (grade_level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($create_sql)) {
        echo "Sections table created successfully!\n";
    } else {
        echo "Failed to create sections table: " . $conn->error . "\n";
        exit;
    }
} else {
    echo "Sections table exists.\n";

    // Show table structure
    echo "\nTable structure:\n";
    $result = $conn->query('DESCRIBE sections');
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
}

// Check if users table has section_id column
echo "\nChecking users table for section_id column...\n";
$result = $conn->query('DESCRIBE users');
$has_section_id = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'section_id') {
        $has_section_id = true;
        break;
    }
}

if (!$has_section_id) {
    echo "Adding section_id to users table...\n";
    if ($conn->query('ALTER TABLE users ADD COLUMN section_id INT DEFAULT NULL')) {
        echo "section_id column added to users table\n";
    } else {
        echo "Failed to add section_id column: " . $conn->error . "\n";
    }
} else {
    echo "users table already has section_id column\n";
}

// Test section insertion
echo "\nTesting section insertion...\n";
$stmt = $conn->prepare('INSERT INTO sections (section_name, grade_level, teacher_id, is_active) VALUES (?, ?, ?, 1)');
$section_name = 'Test Section';
$grade_level = 'grade1';
$teacher_id = null;

$stmt->bind_param('sss', $section_name, $grade_level, $teacher_id);

if ($stmt->execute()) {
    echo "Test section created successfully! Section ID: " . $conn->insert_id . "\n";
    // Clean up test data
    $conn->query('DELETE FROM sections WHERE section_name = "Test Section"');
    echo "Test data cleaned up.\n";
} else {
    echo "Failed to create test section: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();

echo "\nDatabase test completed.\n";
?>
