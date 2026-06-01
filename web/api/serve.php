<?php
require_once 'config.php';

if (function_exists('set_time_limit')) {
    set_time_limit(0);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo 'Server error';
    exit;
}

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
$isDownload = isset($_GET['download']);
$disposition = $isDownload ? 'attachment' : 'inline';

while (ob_get_level()) ob_end_clean();

$file_size = filesize($file_path);
$range = $_SERVER['HTTP_RANGE'] ?? '';

if ($range) {
    preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
    $start = intval($matches[1]);
    $end = $matches[2] !== '' ? intval($matches[2]) : $file_size - 1;

    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$file_size");
    header('Content-Length: ' . ($end - $start + 1));
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($book['file_path']) . '"');
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $fp = fopen($file_path, 'rb');
    if (!$fp) {
        http_response_code(500);
        echo 'Could not open file';
        exit;
    }
    fseek($fp, $start);
    $pos = $start;
    while (!feof($fp) && $pos <= $end && !connection_aborted()) {
        $bytes = min(65536, $end - $pos + 1);
        echo fread($fp, $bytes);
        ob_flush();
        flush();
        $pos += $bytes;
    }
    fclose($fp);
} else {
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . $file_size);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($book['file_path']) . '"');
    header('Accept-Ranges: bytes');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $fp = fopen($file_path, 'rb');
    if (!$fp) {
        http_response_code(500);
        echo 'Could not open file';
        exit;
    }
    while (!feof($fp) && !connection_aborted()) {
        echo fread($fp, 65536);
        ob_flush();
        flush();
    }
    fclose($fp);
}
exit;
