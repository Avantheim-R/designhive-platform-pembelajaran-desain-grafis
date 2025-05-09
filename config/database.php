<?php
// Only define constants if they haven't been defined yet
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'designhive_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Initialize database connection status
$db_connection_status = [
    'connected' => false,
    'error' => null,
    'error_details' => null
];

try {
    // Try to connect without specifying database first
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Attempt initial connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $database_exists = $stmt->fetch();
    
    if (!$database_exists) {
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    // Connect to the specific database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Set connection status
    $db_connection_status['connected'] = true;
    
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $db_connection_status['error'] = "Database connection error";
    $db_connection_status['error_details'] = $e->getMessage();
    
    // Set PDO to null so we can check if connection failed
    $pdo = null;
}

// Function to check if database is connected
function isDatabaseConnected() {
    global $db_connection_status;
    return $db_connection_status['connected'];
}

// Function to get database error message
function getDatabaseError() {
    global $db_connection_status;
    return [
        'message' => $db_connection_status['error'],
        'details' => $db_connection_status['error_details']
    ];
}

// Function to initialize database schema
function initializeDatabaseSchema() {
    global $pdo;
    
    if (!isDatabaseConnected()) {
        return false;
    }
    
    try {
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $table_exists = $stmt->fetch();
        
        if (!$table_exists) {
            // Read and execute schema file
            $schema_file = __DIR__ . '/../database_schema.sql';
            if (file_exists($schema_file)) {
                $sql = file_get_contents($schema_file);
                $pdo->exec($sql);
                return true;
            }
        }
        return true;
    } catch(PDOException $e) {
        error_log("Schema initialization error: " . $e->getMessage());
        return false;
    }
}
?>
