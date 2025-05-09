<?php
// Application configuration
define('APP_ENV', 'development'); // 'development' or 'production'
define('APP_URL', 'http://localhost:8000');
define('APP_TIMEZONE', 'Asia/Jakarta');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'designhive_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security configuration
define('HASH_COST', 10); // Password hashing cost
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('CSRF_TIMEOUT', 7200); // 2 hours in seconds

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../public/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('ALLOWED_EXTENSIONS', [
    'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'
]);
define('ALLOWED_MIMES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

// Learning configuration
define('LESSONS_PER_BAB', 5);
define('MIN_PASS_SCORE', 70);
define('QUIZ_ATTEMPT_LIMIT', 3);
define('ASSIGNMENT_TYPES', [
    'praktik' => 'Tugas Praktik',
    'project' => 'Project',
    'ujian' => 'Ujian'
]);

// Forum configuration
define('THREADS_PER_PAGE', 10);
define('MAX_THREAD_TITLE_LENGTH', 100);
define('MIN_THREAD_CONTENT_LENGTH', 20);

// Email configuration (for future use)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', '');
define('SMTP_FROM_NAME', 'DesignHIve');

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Logging configuration
define('LOG_DIR', __DIR__ . '/../logs');
define('ERROR_LOG', LOG_DIR . '/error.log');
define('ACCESS_LOG', LOG_DIR . '/access.log');
define('DEBUG_LOG', LOG_DIR . '/debug.log');

// Certificate configuration
define('CERT_TEMPLATE_PATH', __DIR__ . '/../resources/certificate_template.jpg');
define('CERT_FONT_PATH', __DIR__ . '/../resources/certificate_font.ttf');
define('CERT_OUTPUT_DIR', UPLOAD_DIR . '/certificates');

// Cache configuration (for future use)
define('CACHE_ENABLED', false);
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_LIFETIME', 3600); // 1 hour in seconds

// API configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_TOKEN_LIFETIME', 86400); // 24 hours in seconds

// Content configuration
define('CONTENT_TYPES', [
    'text' => 'Text Content',
    'video' => 'Video Tutorial',
    'quiz' => 'Interactive Quiz',
    'assignment' => 'Assignment'
]);

// Progress tracking configuration
define('PROGRESS_STATUSES', [
    0 => 'Not Started',
    1 => 'In Progress',
    2 => 'Completed'
]);

// Grade scale configuration
define('GRADE_SCALE', [
    'A' => ['min' => 90, 'max' => 100, 'description' => 'Sangat Baik'],
    'B' => ['min' => 80, 'max' => 89, 'description' => 'Baik'],
    'C' => ['min' => 70, 'max' => 79, 'description' => 'Cukup'],
    'D' => ['min' => 60, 'max' => 69, 'description' => 'Kurang'],
    'E' => ['min' => 0, 'max' => 59, 'description' => 'Sangat Kurang']
]);

// User roles and permissions
define('USER_ROLES', [
    'student' => [
        'can_view_lessons' => true,
        'can_take_quizzes' => true,
        'can_submit_assignments' => true,
        'can_participate_forum' => true
    ],
    'teacher' => [
        'can_view_lessons' => true,
        'can_manage_content' => true,
        'can_grade_assignments' => true,
        'can_manage_users' => true,
        'can_moderate_forum' => true
    ]
]);

// Create required directories if they don't exist
$required_dirs = [
    LOG_DIR,
    UPLOAD_DIR,
    UPLOAD_DIR . '/assignments',
    UPLOAD_DIR . '/certificates',
    CACHE_DIR
];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Load environment-specific configuration if exists
$env_config = __DIR__ . '/config.' . APP_ENV . '.php';
if (file_exists($env_config)) {
    require_once $env_config;
}

// Initialize error logging
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Session configuration
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}
?>
