<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Application constants
define('APP_NAME', 'DesignHIve');
define('APP_VERSION', '1.0.0');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

// Time zone setting
date_default_timezone_set('Asia/Jakarta');

/**
 * Format date to Indonesian format
 * @param string $date Date string
 * @param bool $withTime Include time in output
 * @return string Formatted date
 */
function formatDate($date, $withTime = false) {
    $months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    if ($withTime) {
        $time = date('H:i', $timestamp);
        return "$day $month $year, $time WIB";
    }
    
    return "$day $month $year";
}

/**
 * Format file size to human readable format
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Generate random string
 * @param int $length Length of string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Calculate progress percentage
 * @param int $completed Number of completed items
 * @param int $total Total number of items
 * @return int Progress percentage
 */
function calculateProgress($completed, $total) {
    if ($total == 0) return 0;
    return round(($completed / $total) * 100);
}

/**
 * Get grade letter based on score
 * @param float $score Numeric score
 * @return string Grade letter
 */
function getGradeLetter($score) {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'E';
}

/**
 * Validate email address
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Clean file name
 * @param string $filename Original filename
 * @return string Cleaned filename
 */
function cleanFileName($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove any non-alphanumeric characters except dots and underscores
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename);
    
    // Ensure the filename is unique by adding timestamp
    $info = pathinfo($filename);
    return $info['filename'] . '_' . time() . '.' . $info['extension'];
}

/**
 * Format time duration
 * @param int $minutes Duration in minutes
 * @return string Formatted duration
 */
function formatDuration($minutes) {
    if ($minutes < 60) {
        return "$minutes menit";
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($mins == 0) {
        return "$hours jam";
    }
    
    return "$hours jam $mins menit";
}

/**
 * Generate breadcrumb navigation
 * @param array $items Array of breadcrumb items
 * @return string HTML breadcrumb navigation
 */
function generateBreadcrumbs($items) {
    $html = '<nav class="flex" aria-label="Breadcrumb">';
    $html .= '<ol class="inline-flex items-center space-x-1 md:space-x-3">';
    
    foreach ($items as $index => $item) {
        $isLast = $index === array_key_last($items);
        
        $html .= '<li class="inline-flex items-center">';
        if ($index > 0) {
            $html .= '<svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>';
        }
        
        if ($isLast) {
            $html .= '<span class="text-gray-500">' . h($item['text']) . '</span>';
        } else {
            $html .= '<a href="' . h($item['url']) . '" class="text-primary hover:text-blue-700">' . h($item['text']) . '</a>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Log application events
 * @param string $message Log message
 * @param string $type Log type (info, error, warning)
 */
function logEvent($message, $type = 'info') {
    $log_file = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$type] $message" . PHP_EOL;
    
    error_log($log_message, 3, $log_file);
}

/**
 * Create directory if it doesn't exist
 * @param string $path Directory path
 * @return bool True if directory exists or was created
 */
function ensureDirectoryExists($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0777, true);
    }
    return true;
}

/**
 * Get file extension from MIME type
 * @param string $mime_type MIME type
 * @return string File extension
 */
function getExtensionFromMime($mime_type) {
    $mime_map = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];
    
    return isset($mime_map[$mime_type]) ? $mime_map[$mime_type] : '';
}

/**
 * Format NIS (Student ID)
 * @param string $nis Raw NIS
 * @return string Formatted NIS
 */
function formatNIS($nis) {
    // Ensure NIS is 10 digits
    $nis = str_pad($nis, 10, '0', STR_PAD_LEFT);
    return substr($nis, 0, 4) . '.' . substr($nis, 4, 3) . '.' . substr($nis, 7);
}

// Create required directories if they don't exist
$required_dirs = [
    __DIR__ . '/../logs',
    __DIR__ . '/../public/uploads',
    __DIR__ . '/../public/uploads/assignments',
    __DIR__ . '/../public/uploads/certificates'
];

foreach ($required_dirs as $dir) {
    ensureDirectoryExists($dir);
}
?>
