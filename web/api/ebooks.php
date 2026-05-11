<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$conn = getDBConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

switch ($action) {
    case 'get_featured':
        getFeaturedBooks($conn);
        break;
    case 'get_recent':
        getRecentBooks($conn);
        break;
    case 'get_all':
        getAllBooks($conn);
        break;
    case 'get_categories':
        getCategories($conn);
        break;
    case 'get_my_books':
        getMyBooks($conn);
        break;
    case 'get_book':
        getBook($conn);
        break;
    case 'search':
        searchBooks($conn);
        break;
    case 'get_filtered':
        getFilteredBooks($conn);
        break;
    case 'get_progress':
        getReadingProgress($conn);
        break;
    case 'mark_read':
        markAsRead($conn);
        break;
    case 'get_bookmarks':
        getBookmarks($conn);
        break;
    case 'add_bookmark':
        addBookmark($conn);
        break;
    case 'delete_bookmark':
        deleteBookmark($conn);
        break;
    case 'download_book':
        downloadBook($conn);
        break;
    case 'get_sections':
        getSections($conn);
        break;
    case 'upload_book':
        uploadBook($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getFeaturedBooks($conn) {
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image, content_type FROM ebooks WHERE is_featured = 1 AND is_active = 1";
        
        // Filter by visibility rules
        $sql .= getGradeLevelFilter();
        
        $sql .= " ORDER BY created_at DESC LIMIT 8";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode(['success' => true, 'books' => $books]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading featured books']);
        error_log($e->getMessage());
    }
}

function getRecentBooks($conn) {
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image, content_type FROM ebooks WHERE is_active = 1";
        
        // Filter by visibility rules
        $sql .= getGradeLevelFilter();
        
        $sql .= " ORDER BY created_at DESC LIMIT 8";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode(['success' => true, 'books' => $books]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading recent books']);
        error_log($e->getMessage());
    }
}

function getAllBooks($conn) {
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image, content_type FROM ebooks WHERE is_active = 1";
        
        // Filter by visibility rules
        $sql .= getGradeLevelFilter();
        
        $sql .= " ORDER BY title ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode(['success' => true, 'books' => $books]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading books']);
        error_log($e->getMessage());
    }
}

function getCategories($conn) {
    try {
        // Get only from categories table (no duplicates)
        $stmt = $conn->prepare("SELECT category_id, category_name, description, icon FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'categories' => $categories]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading categories']);
        error_log($e->getMessage());
    }
}

function getMyBooks($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in', 'books' => []]);
        return;
    }
    
    try {
        
        // Ensure reading_progress table exists
        $conn->query("CREATE TABLE IF NOT EXISTS reading_progress (
            progress_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            ebook_id INT NOT NULL,
            current_page INT DEFAULT 1,
            total_pages INT DEFAULT 1,
            progress_percentage DECIMAL(5,2) DEFAULT 0,
            last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_book (user_id, ebook_id),
            INDEX idx_user (user_id),
            INDEX idx_ebook (ebook_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Get books with reading progress for this user
        $stmt = $conn->prepare("
            SELECT e.ebook_id, e.title, e.author, e.category, e.grade_level, e.cover_image, e.content_type,
                   rp.progress_percentage as progress,
                   rp.current_page,
                   rp.last_accessed as last_read
            FROM reading_progress rp
            INNER JOIN ebooks e ON e.ebook_id = rp.ebook_id
            WHERE rp.user_id = ? AND e.is_active = 1 
            ORDER BY rp.last_accessed DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'books' => $books]);
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'books' => [], 'debug' => $e->getMessage()]);
        error_log($e->getMessage());
    }
}

function getBook($conn) {
    $ebook_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($ebook_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM ebooks WHERE ebook_id = ? AND is_active = 1");
        $stmt->bind_param("i", $ebook_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $book = $result->fetch_assoc();
            echo json_encode(['success' => true, 'book' => $book]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading book']);
        error_log($e->getMessage());
    }
}

function searchBooks($conn) {
    $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
    
    if (empty($query)) {
        echo json_encode(['success' => true, 'books' => []]);
        return;
    }
    
    try {
        $search_term = "%$query%";
        
        // Simple search query - only searches title, author, category
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image, content_type 
                FROM ebooks 
                WHERE (title LIKE ? OR author LIKE ? OR category LIKE ?) 
                AND is_active = 1 ";
        
        $sql .= getGradeLevelFilter();
        
        $sql .= " ORDER BY title ASC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode(['success' => true, 'books' => $books, 'query' => $query]);
        $stmt->close();
    } catch (Exception $e) {
        // Return empty results on error instead of failing
        echo json_encode(['success' => true, 'books' => [], 'error' => $e->getMessage()]);
        error_log("Search error: " . $e->getMessage());
    }
}

function getFilteredBooks($conn) {
    $subject = isset($_GET['subject']) ? sanitizeInput($_GET['subject']) : '';
    $content_type_filter = isset($_GET['content_type']) ? sanitizeInput($_GET['content_type']) : '';
    
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image, content_type FROM ebooks WHERE is_active = 1";
        $params = [];
        $types = "";
        
        // Filter by visibility rules
        $sql .= getGradeLevelFilter();
        
        if (!empty($subject)) {
            $sql .= " AND category = ?";
            $params[] = $subject;
            $types .= "s";
        }
        
        if (!empty($content_type_filter)) {
            $sql .= " AND content_type = ?";
            $params[] = $content_type_filter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY title ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        echo json_encode(['success' => true, 'books' => $books]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Filter failed']);
        error_log($e->getMessage());
    }
}

function getReadingProgress($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Accept JSON body, POST, or GET params (mobile uses GET)
    $input = json_decode(file_get_contents('php://input'), true);
    $ebook_id = 0;
    if (isset($input['ebook_id'])) {
        $ebook_id = (int)$input['ebook_id'];
    } elseif (isset($_GET['ebook_id'])) {
        $ebook_id = (int)$_GET['ebook_id'];
    } elseif (isset($_POST['ebook_id'])) {
        $ebook_id = (int)$_POST['ebook_id'];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM reading_progress WHERE user_id = ? AND ebook_id = ?");
        $stmt->bind_param("ii", $user_id, $ebook_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $progress = $result->fetch_assoc();
            echo json_encode(['success' => true, 'progress' => $progress]);
        } else {
            echo json_encode(['success' => true, 'progress' => null]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading progress']);
        error_log($e->getMessage());
    }
}

function markAsRead($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ebook_id = isset($input['ebook_id']) ? (int)$input['ebook_id'] : (isset($_POST['ebook_id']) ? (int)$_POST['ebook_id'] : 0);
    $current_page = isset($input['page']) ? (int)$input['page'] : (isset($input['current_page']) ? (int)$input['current_page'] : 1);
    $total_pages = isset($input['total_pages']) ? (int)$input['total_pages'] : (isset($_POST['total_pages']) ? (int)$_POST['total_pages'] : $totalPages ?? 1);
    
    // Use current page as total pages estimate if not provided
    if ($total_pages < $current_page) $total_pages = $current_page;
    
    try {
        $progress_percentage = ($current_page / $total_pages) * 100;
        
        $stmt = $conn->prepare("
            INSERT INTO reading_progress (user_id, ebook_id, current_page, total_pages, progress_percentage, last_accessed) 
            VALUES (?, ?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            current_page = VALUES(current_page), 
            progress_percentage = VALUES(progress_percentage), 
            last_accessed = VALUES(last_accessed)
        ");
        $stmt->bind_param("iiiii", $user_id, $ebook_id, $current_page, $total_pages, $progress_percentage);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Progress saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save progress']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving progress']);
        error_log($e->getMessage());
    }
}

function getBookmarks($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM bookmarks WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookmarks = [];
        while ($row = $result->fetch_assoc()) {
            $bookmarks[] = $row;
        }
        
        echo json_encode(['success' => true, 'bookmarks' => $bookmarks]);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading bookmarks']);
        error_log($e->getMessage());
    }
}

function addBookmark($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ebook_id = isset($input['ebook_id']) ? (int)$input['ebook_id'] : 0;
    $page_number = isset($input['page_number']) ? (int)$input['page_number'] : 1;
    $note = isset($input['note']) ? sanitizeInput($input['note']) : '';
    
    try {
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, ebook_id, page_number, note) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $ebook_id, $page_number, $note);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bookmark added']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add bookmark']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding bookmark']);
        error_log($e->getMessage());
    }
}

function deleteBookmark($conn) {
    $user_id = getAuthUserId();
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $bookmark_id = isset($input['bookmark_id']) ? (int)$input['bookmark_id'] : 0;
    
    try {
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE bookmark_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $bookmark_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Bookmark deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete bookmark']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting bookmark']);
        error_log($e->getMessage());
    }
}

function downloadBook($conn) {
    $ebook_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($ebook_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT title, file_path, content_type FROM ebooks WHERE ebook_id = ? AND is_active = 1");
        $stmt->bind_param("i", $ebook_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $book = $result->fetch_assoc();

            // Check if download is allowed (not allowed for 'book' content type)
            if ($book['content_type'] === 'book') {
                echo json_encode(['success' => false, 'message' => 'Download not allowed for this content type']);
                return;
            }

            // Build proper file path
            $file_path = '../uploads/books/' . $book['file_path'];

            // Get file extension for Content-Type
            $ext = strtolower(pathinfo($book['file_path'], PATHINFO_EXTENSION));
            $content_type = 'application/octet-stream';
            if ($ext === 'pdf') {
                $content_type = 'application/pdf';
            } elseif ($ext === 'epub') {
                $content_type = 'application/epub+zip';
            } elseif (in_array($ext, ['ppt', 'pptx'])) {
                $content_type = 'application/vnd.ms-powerpoint';
            } elseif ($ext === 'mp4') {
                $content_type = 'video/mp4';
            } elseif ($ext === 'webm') {
                $content_type = 'video/webm';
            }

            // Create download filename
            $download_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $book['title']) . '.' . $ext;

            if (file_exists($file_path)) {
                // Log download activity
                $logUserId = getAuthUserId();
                if ($logUserId) {
                    logActivity($conn, $logUserId, 'download', 'ebook', $ebook_id, "Downloaded: {$book['title']}");
                }

                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Set headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $content_type);
                header('Content-Disposition: attachment; filename="' . $download_name . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));

                // Read and output file
                readfile($file_path);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'File not found: ' . $book['file_path']]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
        }

        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error downloading book']);
        error_log($e->getMessage());
    }
}

function uploadBook($conn) {
    // Prevent any stray output from breaking JSON
    ob_start();
    
    // Limits are primarily handled by .user.ini for FastCGI
    // but we set them here as a secondary measure for some environments
    @ini_set('max_execution_time', '1200');
    @ini_set('max_input_time', '1200');
    @ini_set('memory_limit', '1024M');
    @set_time_limit(1200);

    try {
        // Check if user is logged in and is a teacher (supports token + session auth)
        $userId = getAuthUserId();
        if (!$userId) {
            throw new Exception('Unauthorized access');
        }
        // Verify teacher role for upload
        $userCheck = $conn->prepare("SELECT user_type FROM users WHERE user_id = ? AND is_active = 1");
        $userCheck->bind_param("i", $userId);
        $userCheck->execute();
        $userResult = $userCheck->get_result();
        if ($userResult->num_rows === 0) {
            $userCheck->close();
            throw new Exception('Unauthorized access');
        }
        $userRow = $userResult->fetch_assoc();
        $userCheck->close();
        if ($userRow['user_type'] !== 'teacher' && $userRow['user_type'] !== 'admin') {
            throw new Exception('Only teachers and admins can upload');
        }

        // Detect if upload was too large for PHP settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $max_post = ini_get('post_max_size');
            throw new Exception("The file is too large for the server (Server Post Limit: $max_post).");
        }

        $title = sanitizeInput($_POST['title'] ?? '');
        $author = sanitizeInput($_POST['author'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $grade_level = sanitizeInput($_POST['grade_level'] ?? 'all');
        $content_type = sanitizeInput($_POST['content_type'] ?? 'book');
        $section_id = intval($_POST['section_id'] ?? 0);

        if (empty($title)) {
            throw new Exception('Title is required');
        }

        // Check for upload errors
        if (!isset($_FILES['book_file'])) {
            throw new Exception('No file was received by the server.');
        }

        if ($_FILES['book_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . getUploadErrorMessage($_FILES['book_file']['error']));
        }

        // Check file size
        if ($_FILES['book_file']['size'] > MAX_FILE_SIZE) {
            throw new Exception('File size exceeds system limit (' . formatFileSize(MAX_FILE_SIZE) . ')');
        }

        // Ensure directories exist
        if (!file_exists(UPLOAD_ROOT)) {
            mkdir(UPLOAD_ROOT, 0777, true);
        }
        
        foreach ([BOOKS_PATH, COVERS_PATH] as $path) {
            if (!file_exists($path)) {
                if (!mkdir($path, 0777, true)) {
                    throw new Exception("Failed to create storage directory: " . basename($path));
                }
            }
        }

        // Handle cover image upload
        $cover_image = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['cover_image']['size'] <= 10 * 1024 * 1024) { // 10MB limit for covers
                $cover_image = generateUniqueFileName($_FILES['cover_image']['name']);
                if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], COVERS_PATH . $cover_image)) {
                    $cover_image = ''; // Non-fatal if cover fails
                }
            }
        }

        // Handle main file upload
        $file_name = $_FILES['book_file']['name'];
        $unique_file_path = generateUniqueFileName($file_name);
        $full_dest_path = BOOKS_PATH . $unique_file_path;

        if (!move_uploaded_file($_FILES['book_file']['tmp_name'], $full_dest_path)) {
            $error = error_get_last();
            $msg = isset($error['message']) ? ": " . $error['message'] : ". Please check folder permissions in WAMP.";
            throw new Exception('Failed to save the file to server storage' . $msg);
        }

        // Prepare database insert
        $uploaded_by = $userId;
        $section_id_value = $section_id > 0 ? $section_id : null;
        
        $sql = "INSERT INTO ebooks (title, author, description, category, subject, grade_level, section_id, content_type, cover_image, file_path, uploaded_by, is_approved, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database preparation failed: ' . $conn->error);
        }

        $stmt->bind_param("ssssssisssi", 
            $title, $author, $description, $category, $subject, $grade_level, 
            $section_id_value, $content_type, $cover_image, $unique_file_path, $uploaded_by
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to save material information to database: ' . $stmt->error);
        }

        $stmt->close();
        
        // Success! Clear buffer and send response
        ob_end_clean();
        $type_display = ($content_type === 'video') ? 'Video' : (($content_type === 'lesson') ? 'Lesson Plan' : 'Book');
        echo json_encode(['success' => true, 'message' => $type_display . ' uploaded successfully and is now available']);

    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getUploadErrorMessage($err_code) {
    switch ($err_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}


// Get visibility filter SQL based on user type and grade/section
function getGradeLevelFilter() {
    $user_id = null;
    $user_type = null;
    $grade_level = 'n/a';
    $section_id = null;
    
    // Try session-based auth first (web app)
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $user_type = $_SESSION['user_type'];
        $grade_level = $_SESSION['grade_level'] ?? 'n/a';
        $section_id = $_SESSION['section_id'] ?? null;
    } else {
        // Check Bearer token (mobile app)
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $conn = getDBConnection();
            if ($conn) {
                $token = $matches[1];
                $stmt = $conn->prepare("SELECT user_id, user_type, grade_level, section_id FROM users WHERE api_token = ? AND is_active = 1");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $user_id = (int)$user['user_id'];
                    $user_type = $user['user_type'];
                    $grade_level = $user['grade_level'] ?? 'n/a';
                    $section_id = $user['section_id'] ?? null;
                }
                $stmt->close();
                $conn->close();
            }
        }
    }
    
    if (!$user_id) {
        return " AND is_active = 1 AND is_approved = 1";
    }
    
    // Admin can see everything
    if ($user_type === 'admin') {
        return "";
    }
    
    // Teachers only see their own uploads
    if ($user_type === 'teacher') {
        return " AND uploaded_by = " . intval($user_id);
    }
    
    // Students and Parents
    $filter = " AND is_active = 1 AND is_approved = 1";
    
    // Filter by grade level
    if (!empty($grade_level) && $grade_level !== 'n/a') {
        $filter .= " AND (grade_level = '" . addslashes($grade_level) . "' OR grade_level = 'all')";
    }
    
    // Filter by section
    if (!empty($section_id)) {
        // Show material if it's for their section OR if it's for the whole grade (section_id IS NULL)
        $filter .= " AND (section_id = " . intval($section_id) . " OR section_id IS NULL)";
    } else {
        // If student has no section, they only see grade-wide materials
        $filter .= " AND section_id IS NULL";
    }
    
    return $filter;
}

function getCategoryIcon($category) {
    $icons = [
        'English' => 'fas fa-book',
        'Mathematics' => 'fas fa-calculator',
        'Science' => 'fas fa-atom',
        'Filipino' => 'fas fa-flag',
        'Araling Panlipunan' => 'fas fa-landmark',
        'MAPEH' => 'fas fa-palette',
        'Reading' => 'fas fa-book-open',
        'Writing' => 'fas fa-pen',
        'Grammar' => 'fas fa-language',
        'Literature' => 'fas fa-scroll',
        'lesson' => 'fas fa-file-powerpoint',
        'video' => 'fas fa-video',
        'module' => 'fas fa-file-alt'
    ];
    
    return isset($icons[$category]) ? $icons[$category] : 'fas fa-book';
}

function getSections($conn) {
    // Convert to lowercase and remove spaces to match database format
    $grade_level = isset($_GET['grade_level']) ? strtolower(str_replace(' ', '', $_GET['grade_level'])) : '';
    
    if (empty($grade_level)) {
        // Return all sections if no grade level specified
        $sql = "SELECT section_id, section_name, grade_level FROM sections WHERE is_active = 1 ORDER BY grade_level ASC, section_name ASC";
        $result = $conn->query($sql);
    } else {
        // Return sections for specific grade level
        $stmt = $conn->prepare("SELECT section_id, section_name FROM sections WHERE grade_level = ? AND is_active = 1 ORDER BY section_name ASC");
        $stmt->bind_param("s", $grade_level);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    echo json_encode(['success' => true, 'sections' => $sections]);
    
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>
