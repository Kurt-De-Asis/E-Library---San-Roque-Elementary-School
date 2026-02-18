<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check teacher privileges
if (!isLoggedIn() || !isTeacher()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_uploads':
        getUploads();
        break;
    case 'get_stats':
        getStats();
        break;
    case 'get_students':
        getStudents();
        break;
    case 'get_my_sections':
        getMySections();
        break;
    case 'get_my_uploads':
        getMyUploads();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getUploads() {
    $conn = getDBConnection();

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT ebook_id, title, author, cover_image, subject, grade_level, content_type, is_approved, view_count, download_count, created_at FROM ebooks WHERE uploaded_by = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $uploads = [];
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'uploads' => $uploads]);
}

function getStats() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    $stats = [];

    // Total students in teacher's sections
    $result = $conn->query("
        SELECT COUNT(DISTINCT u.user_id) as count
        FROM users u
        INNER JOIN sections s ON u.section_id = s.section_id
        WHERE s.teacher_id = $user_id AND u.user_type = 'student'
    ");
    $stats['total_students'] = $result->fetch_assoc()['count'];

    // Total sections assigned to teacher
    $result = $conn->query("SELECT COUNT(*) as count FROM sections WHERE teacher_id = $user_id");
    $stats['total_sections'] = $result->fetch_assoc()['count'];

    // Total uploads by teacher
    $result = $conn->query("SELECT COUNT(*) as count FROM ebooks WHERE uploaded_by = $user_id");
    $stats['total_uploads'] = $result->fetch_assoc()['count'];

    // Pending approvals
    $result = $conn->query("SELECT COUNT(*) as count FROM ebooks WHERE uploaded_by = $user_id AND is_approved = 0");
    $stats['pending_approvals'] = $result->fetch_assoc()['count'];

    $conn->close();

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getStudents() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    $result = $conn->query("
        SELECT u.user_id, u.username, u.email, u.full_name, u.grade_level, u.is_active, u.last_login,
               s.section_name
        FROM users u
        LEFT JOIN sections s ON u.section_id = s.section_id
        WHERE s.teacher_id = $user_id AND u.user_type = 'student'
        ORDER BY u.full_name ASC
    ");

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $conn->close();

    echo json_encode(['success' => true, 'students' => $students]);
}

function getMySections() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    $result = $conn->query("
        SELECT s.*, COUNT(u.user_id) as student_count
        FROM sections s
        LEFT JOIN users u ON s.section_id = u.section_id AND u.user_type = 'student'
        WHERE s.teacher_id = $user_id
        GROUP BY s.section_id
        ORDER BY s.grade_level ASC, s.section_name ASC
    ");

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }

    $conn->close();

    echo json_encode(['success' => true, 'sections' => $sections]);
}

function getMyUploads() {
    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }

    $user_id = $_SESSION['user_id'];

    $result = $conn->query("
        SELECT e.*, s.section_name
        FROM ebooks e
        LEFT JOIN sections s ON e.section_id = s.section_id
        WHERE e.uploaded_by = $user_id
        ORDER BY e.created_at DESC
    ");

    $uploads = [];
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }

    $conn->close();

    echo json_encode(['success' => true, 'uploads' => $uploads]);
}
?>
