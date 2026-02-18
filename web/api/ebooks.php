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
    case 'upload_book':
        uploadBook($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getFeaturedBooks($conn) {
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image FROM ebooks WHERE is_featured = 1 AND is_active = 1";
        
        // Filter by grade level for students
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
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image FROM ebooks WHERE is_active = 1";
        
        // Filter by grade level for students
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
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image FROM ebooks WHERE is_active = 1";
        
        // Filter by grade level for students
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in', 'books' => []]);
        return;
    }
    
    try {
        $user_id = $_SESSION['user_id'];
        
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
            SELECT e.ebook_id, e.title, e.author, e.category, e.grade_level, e.cover_image,
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
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image 
                FROM ebooks 
                WHERE (title LIKE ? OR author LIKE ? OR category LIKE ?) 
                AND is_active = 1 
                ORDER BY title ASC
                LIMIT 50";
        
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
    $content_type = isset($_GET['content_type']) ? sanitizeInput($_GET['content_type']) : '';
    
    try {
        $sql = "SELECT ebook_id, title, author, category, grade_level, cover_image FROM ebooks WHERE is_active = 1";
        $params = [];
        $types = "";
        
        // Filter by grade level for students
        $sql .= getGradeLevelFilter();
        
        if (!empty($subject)) {
            $sql .= " AND category = ?";
            $params[] = $subject;
            $types .= "s";
        }
        
        if (!empty($content_type)) {
            $sql .= " AND content_type = ?";
            $params[] = $content_type;
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ebook_id = isset($input['ebook_id']) ? (int)$input['ebook_id'] : (isset($_POST['ebook_id']) ? (int)$_POST['ebook_id'] : 0);
    
    try {
        $user_id = $_SESSION['user_id'];
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
    if (!isLoggedIn()) {
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
        $user_id = $_SESSION['user_id'];
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    try {
        $user_id = $_SESSION['user_id'];
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ebook_id = isset($input['ebook_id']) ? (int)$input['ebook_id'] : 0;
    $page_number = isset($input['page_number']) ? (int)$input['page_number'] : 1;
    $note = isset($input['note']) ? sanitizeInput($input['note']) : '';
    
    try {
        $user_id = $_SESSION['user_id'];
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
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        return;
    }
    
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $bookmark_id = isset($input['bookmark_id']) ? (int)$input['bookmark_id'] : 0;
    
    try {
        $user_id = $_SESSION['user_id'];
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
            }

            // Create download filename
            $download_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $book['title']) . '.' . $ext;

            if (file_exists($file_path)) {
                // Log download activity
                if (isLoggedIn()) {
                    logActivity($conn, $_SESSION['user_id'], 'download', 'ebook', $ebook_id, "Downloaded: {$book['title']}");
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
    // Check if user is logged in and is a teacher
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        return;
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
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    // Handle file uploads
    $cover_image = '';
    $file_path = '';

    // Cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $cover_image = generateUniqueFileName($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], COVERS_PATH . $cover_image);
    }

    // Book file upload
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
        $file_path = generateUniqueFileName($_FILES['book_file']['name']);
        move_uploaded_file($_FILES['book_file']['tmp_name'], BOOKS_PATH . $file_path);
    }

    $stmt = $conn->prepare("INSERT INTO ebooks (title, author, description, category, subject, grade_level, section_id, content_type, cover_image, file_path, uploaded_by, is_approved, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)");
    $uploaded_by = $_SESSION['user_id'];
    $section_id_value = $section_id > 0 ? $section_id : null;
    $stmt->bind_param("sssssissssi", $title, $author, $description, $category, $subject, $grade_level, $section_id_value, $content_type, $cover_image, $file_path, $uploaded_by);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Book uploaded successfully and is pending approval']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload book']);
    }

    $stmt->close();
}

// Get grade level filter SQL for students
function getGradeLevelFilter() {
    // Only filter for students
    if (!isLoggedIn()) {
        return "";
    }
    
    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
    $grade_level = isset($_SESSION['grade_level']) ? $_SESSION['grade_level'] : '';
    
    // Teachers and admins can see all books
    if ($user_type === 'teacher' || $user_type === 'admin') {
        return "";
    }
    
    // Students can only see books for their grade level or 'all' grades
    if ($user_type === 'student' && !empty($grade_level) && $grade_level !== 'n/a') {
        return " AND (grade_level = '" . addslashes($grade_level) . "' OR grade_level = 'all')";
    }
    
    return "";
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
        'Literature' => 'fas fa-scroll'
    ];
    
    return isset($icons[$category]) ? $icons[$category] : 'fas fa-book';
}
?>
