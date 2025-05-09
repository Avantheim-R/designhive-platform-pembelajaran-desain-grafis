<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Session timeout after 30 minutes of inactivity
$session_timeout = 1800; // 30 minutes in seconds

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /public/login.php");
        exit();
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header("Location: /public/dashboard.php");
        exit();
    }
}

function checkSessionTimeout() {
    global $session_timeout;
    
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time >= $session_timeout) {
            // Session has expired
            session_unset();
            session_destroy();
            header("Location: /public/login.php?timeout=1");
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
    return true;
}

// Check if session has timed out
if (isLoggedIn()) {
    checkSessionTimeout();
}

// Function to get user data
function getUserData($pdo) {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

// Function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to validate file upload
function validateFileUpload($file, $allowed_types, $max_size) {
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File terlalu besar';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File upload tidak lengkap';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'Tidak ada file yang diupload';
                break;
            default:
                $errors[] = 'Terjadi kesalahan dalam upload file';
        }
        return $errors;
    }

    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'Ukuran file melebihi batas maksimum';
    }

    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = 'Tipe file tidak diizinkan';
    }

    return $errors;
}

// Function to generate secure filename
function generateSecureFilename($original_filename) {
    $info = pathinfo($original_filename);
    return uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $info['extension'];
}

// Function to check if user has completed a lesson
function hasCompletedLesson($pdo, $lesson_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT phase_completed 
            FROM progress 
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $lesson_id]);
        $result = $stmt->fetch();
        
        return $result && $result['phase_completed'] == 1;
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Function to get user progress statistics
function getUserProgress($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT l.id) as total_lessons,
                COUNT(DISTINCT CASE WHEN p.phase_completed = 1 THEN l.id END) as completed_lessons,
                COALESCE(AVG(qr.score), 0) as avg_quiz_score
            FROM lessons l
            LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
            LEFT JOIN quiz_results qr ON qr.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

// Function to check if user has access to a specific BAB
function canAccessBAB($pdo, $bab) {
    // First BAB is always accessible
    if ($bab == 1) return true;

    try {
        // Check if previous BAB is completed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_lessons,
                   (SELECT COUNT(*) FROM lessons WHERE bab = ?) as total_lessons
            FROM progress p
            JOIN lessons l ON p.lesson_id = l.id
            WHERE p.user_id = ? AND l.bab = ? AND p.phase_completed = 1
        ");
        $prev_bab = $bab - 1;
        $stmt->execute([$prev_bab, $_SESSION['user_id'], $prev_bab]);
        $result = $stmt->fetch();
        
        return $result && $result['completed_lessons'] >= $result['total_lessons'];
    } catch(PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}
?>
