<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($token) || $id <= 0) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

// Validate token
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo 'Server error';
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM users WHERE api_token = ? AND is_active = 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(401);
    echo 'Unauthorized';
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Get book file path
$stmt = $conn->prepare("SELECT title, file_path, content_type FROM ebooks WHERE ebook_id = ? AND is_active = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);
    echo 'Book not found';
    $stmt->close();
    $conn->close();
    exit;
}

$book = $result->fetch_assoc();
$stmt->close();
$conn->close();

$file_path = __DIR__ . '/../uploads/books/' . $book['file_path'];

if (!file_exists($file_path)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$ext = strtolower(pathinfo($book['file_path'], PATHINFO_EXTENSION));
$content_types = [
    'pdf' => 'application/pdf',
    'epub' => 'application/epub+zip',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];

$content_type = $content_types[$ext] ?? 'application/octet-stream';

while (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $content_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . basename($book['file_path']) . '"');
header('Cache-Control: private, max-age=3600');
header('Accept-Ranges: bytes');

readfile($file_path);
exit;
