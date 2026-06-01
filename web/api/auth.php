<?php
require_once 'config.php';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        login();
        break;
    case 'register':
        register();
        break;
    case 'logout':
        logout();
        break;
    case 'check-session':
        checkSession();
        break;
    case 'send-otp':
        sendOTP();
        break;
    case 'verify-otp':
        verifyOTP();
        break;
    case 'reset-password':
        resetPassword();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function login() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    // Auto-repair database if tables are missing (handles InfinityFree DB resets)
    try {
        ensureDatabaseSetup($conn);
    } catch (Exception $e) {
        error_log("Database auto-setup failed: " . $e->getMessage());
    }
    
    // Accept either email or username from mobile/web
    $email = sanitizeInput($_POST['email'] ?? $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email/username and password are required']);
        return;
    }
    
    // Support login by email OR username
    try {
        $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, full_name, user_type, grade_level, profile_image, section_id FROM users WHERE (email = ? OR username = ?) AND is_active = 1");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error, please try again']);
            return;
        }
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();
    } catch (Exception $e) {
        error_log("Login query failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        $conn->close();
        return;
    }
    
    if ($stmt->num_rows === 1) {
        $u_id = null;
        $u_username = null;
        $u_email = null;
        $u_password_hash = '';
        $u_full_name = null;
        $u_user_type = null;
        $u_grade_level = null;
        $u_profile_image = null;
        $u_section_id = null;
        
        $stmt->bind_result($u_id, $u_username, $u_email, $u_password_hash, $u_full_name, $u_user_type, $u_grade_level, $u_profile_image, $u_section_id);
        $stmt->fetch();
        
        // Verify password
        if (password_verify($password, $u_password_hash)) {
            // Generate API token for mobile app
            $token = bin2hex(random_bytes(32));
            try {
                $tokenStmt = $conn->prepare("UPDATE users SET api_token = ?, last_login = NOW() WHERE user_id = ?");
                if ($tokenStmt) {
                    $tokenStmt->bind_param("si", $token, $u_id);
                    $tokenStmt->execute();
                    $tokenStmt->close();
                }
            } catch (Exception $e) {
                error_log("Token update failed: " . $e->getMessage());
                $token = null;
            }
            
            // Set session variables for web app
            $_SESSION['user_id'] = $u_id;
            $_SESSION['username'] = $u_username;
            $_SESSION['full_name'] = $u_full_name;
            $_SESSION['user_type'] = $u_user_type;
            $_SESSION['grade_level'] = $u_grade_level;
            $_SESSION['email'] = $u_email;
            $_SESSION['profile_image'] = $u_profile_image;
            $_SESSION['section_id'] = $u_section_id;
            
            // Log activity
            logActivity($conn, $u_id, 'login', 'user', $u_id, 'User logged in');
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'user_id' => $u_id,
                    'username' => $u_username,
                    'full_name' => $u_full_name,
                    'user_type' => $u_user_type,
                    'grade_level' => $u_grade_level
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
    
    $stmt->close();
    $conn->close();
}

function register() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $user_type = sanitizeInput($_POST['user_type'] ?? 'student');
    $grade_level = sanitizeInput($_POST['grade_level'] ?? 'n/a');
    $section_id = isset($_POST['section_id']) && $_POST['section_id'] !== '' && $_POST['section_id'] !== 'none' ? intval($_POST['section_id']) : null;
    
    // Convert grade level to lowercase to match database
    $grade_level = strtolower(str_replace(' ', '', $grade_level));
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Check if username or email already exists
    try {
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        if (!$checkStmt) {
            echo json_encode(['success' => false, 'message' => 'Database error, please try again']);
            $conn->close();
            return;
        }
        $checkStmt->bind_param("ss", $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            $checkStmt->close();
            $conn->close();
            return;
        }
        $checkStmt->close();
    } catch (Exception $e) {
        error_log("Register check query failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
        $conn->close();
        return;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    try {
        if ($section_id !== null) {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, section_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $username, $email, $password_hash, $full_name, $user_type, $grade_level, $section_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, user_type, grade_level, section_id) VALUES (?, ?, ?, ?, ?, ?, NULL)");
            $stmt->bind_param("ssssss", $username, $email, $password_hash, $full_name, $user_type, $grade_level);
        }
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Log activity
            logActivity($conn, $user_id, 'register', 'user', $user_id, 'New user registered');
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful. Please login.',
                'user_id' => $user_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Register insert failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
    $conn->close();
}

function logout() {
    $conn = getDBConnection();
    $userId = getAuthUserId();
    
    if ($userId && $conn) {
        try {
            // Clear API token if logging out via token (mobile app)
            $tokenStmt = $conn->prepare("UPDATE users SET api_token = NULL WHERE user_id = ?");
            if ($tokenStmt) {
                $tokenStmt->bind_param("i", $userId);
                $tokenStmt->execute();
                $tokenStmt->close();
            }
        } catch (Exception $e) {
            error_log("Logout token clear failed: " . $e->getMessage());
        }
        
        logActivity($conn, $userId, 'logout', 'user', $userId, 'User logged out');
        $conn->close();
    }
    
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout successful']);
}

function checkSession() {
    // Check session first (web)
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'user_type' => $_SESSION['user_type'],
                'grade_level' => $_SESSION['grade_level']
            ]
        ]);
        return;
    }
    
    // Check Bearer token (mobile)
    $userId = getAuthUserId();
    if ($userId) {
        $conn = getDBConnection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT user_id, username, full_name, user_type, grade_level FROM users WHERE user_id = ? AND is_active = 1");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => $user
                ]);
                $stmt->close();
                $conn->close();
                return;
            }
            $stmt->close();
            $conn->close();
        }
    }
    
    echo json_encode(['success' => true, 'logged_in' => false]);
}

// =====================================================
// FORGOT PASSWORD FUNCTIONS
// =====================================================

function sendOTP() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    // Ensure password_resets table exists
    $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        used TINYINT(1) DEFAULT 0,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_otp (otp_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($input['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found in our system']);
        $stmt->close();
        $conn->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Generate 6-digit OTP
    $otp = sprintf('%06d', mt_rand(0, 999999));
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete any existing OTP for this email
    $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $deleteStmt->bind_param("s", $email);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Store OTP in database
    $insertStmt = $conn->prepare("INSERT INTO password_resets (email, otp_code, expires_at) VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", $email, $otp, $expires_at);
    
    if (!$insertStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
        $insertStmt->close();
        $conn->close();
        return;
    }
    $insertStmt->close();
    
    // Send email with OTP
    $emailSent = sendOTPEmail($email, $user['full_name'], $otp);
    
    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email. Please check that SMTP credentials are configured, or contact the administrator.']);
    }
    
    $conn->close();
}

function verifyOTP() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($input['email'] ?? '');
    // Don't sanitize OTP to preserve leading zeros
    $otp = trim($input['otp'] ?? '');
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required. Email: ' . $email . ', OTP: ' . $otp]);
        return;
    }
    
    // First check what's in the database for this email
    $checkStmt = $conn->prepare("SELECT otp_code, expires_at, used FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No OTP found for this email. Please request a new one.']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    
    $storedData = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    $storedOTP = $storedData['otp_code'];
    
    // Compare OTP directly (case-insensitive string comparison)
    if ($otp === $storedOTP) {
        // Check if not expired and not used
        if (strtotime($storedData['expires_at']) < time()) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
            $conn->close();
            return;
        }
        if ($storedData['used'] == 1) {
            echo json_encode(['success' => false, 'message' => 'OTP has already been used. Please request a new one.']);
            $conn->close();
            return;
        }
        
        // Mark as verified
        $updateStmt = $conn->prepare("UPDATE password_resets SET verified = 1 WHERE email = ? AND otp_code = ?");
        $updateStmt->bind_param("ss", $email, $storedOTP);
        $updateStmt->execute();
        $updateStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'OTP verified']);
        $conn->close();
        return;
    }
    
    // OTP doesn't match
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. You entered: ' . $otp . ', Expected: ' . $storedOTP]);
    $conn->close();
}

function verifyOTP_OLD() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($input['email'] ?? '');
    // Don't sanitize OTP to preserve leading zeros
    $otp = trim($input['otp'] ?? '');
    
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        return;
    }
    
    // Pad OTP to 6 digits if needed
    $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
    
    // Check if OTP is valid and not expired
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp_code = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Mark OTP as verified (but not used yet - used when password is reset)
        $row = $result->fetch_assoc();
        $updateStmt = $conn->prepare("UPDATE password_resets SET verified = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'OTP verified']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    }
    
    $stmt->close();
    $conn->close();
}

function resetPassword() {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        return;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = sanitizeInput($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required. Email received: ' . $email]);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    // Check if there's a verified OTP for this email (without expiration check since we just verified)
    $checkStmt = $conn->prepare("SELECT id, verified, used, expires_at FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No password reset request found for this email.']);
        $checkStmt->close();
        $conn->close();
        return;
    }
    
    $resetRecord = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // Check if verified
    if ($resetRecord['verified'] != 1) {
        echo json_encode(['success' => false, 'message' => 'OTP not verified yet. Please verify your OTP first.']);
        $conn->close();
        return;
    }
    
    // Check if already used
    if ($resetRecord['used'] == 1) {
        echo json_encode(['success' => false, 'message' => 'This reset link has already been used.']);
        $conn->close();
        return;
    }
    
    // Hash the new password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user password
    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $password_hash, $email);
    
    if ($updateStmt->execute()) {
        // Mark OTP as used
        $markUsedStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $markUsedStmt->bind_param("i", $resetRecord['id']);
        $markUsedStmt->execute();
        $markUsedStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
    }
    
    $updateStmt->close();
    $conn->close();
}

function sendOTPEmail($email, $name, $otp) {
    require_once __DIR__ . '/../lib/PHPMailer.php';
    require_once __DIR__ . '/../lib/SMTP.php';
    require_once __DIR__ . '/../lib/Exception.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = "San Roque E-Library - Password Reset OTP";
        $mail->Body = "
        <html>
        <head>
            <title>Password Reset OTP</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #F5F5F5;'>
            <div style='background-color: #228B22; color: white; padding: 25px; text-align: center;'>
                <img src='https://sres-e-library.infinityfreeapp.com/assets/logos/school-logo.png' alt='School Logo' style='height: 80px; margin-bottom: 10px;'>
                <h1 style='margin: 5px 0;'>San Roque Elementary School</h1>
                <h2 style='margin: 0; font-weight: normal;'>E-Library System</h2>
            </div>
            <div style='padding: 25px; background-color: #FFFFFF;'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>You requested to reset your password. Use the OTP code below:</p>
                <div style='background-color: #228B22; color: white; font-size: 36px; padding: 20px; text-align: center; letter-spacing: 12px; margin: 20px 0; border-radius: 8px;'>
                    <strong>{$otp}</strong>
                </div>
                <p>This code will expire in <strong>15 minutes</strong>.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
            <div style='background-color: #2C3E50; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                <p style='margin: 2px 0;'>Department of Education — Division of San Pedro, Laguna</p>
                <p style='margin: 2px 0;'>San Roque Elementary School E-Library System</p>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Hello {$name},\n\nYour password reset OTP is: {$otp}\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this, please ignore this email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email send failed: " . $e->getMessage());
        return false;
    }
}
?>
