<?php
/**
 * Database configuration and connection
 * Establishes PDO connection with error handling
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'error_logs');
define('DB_USER', 'your_username'); // Change to your MySQL username
define('DB_PASS', 'your_password'); // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

/**
 * Establish database connection using PDO
 * @return PDO Database connection object
 * @throws Exception If connection fails
 */
function get_database_connection() {
    try {
        // Create DSN (Data Source Name)
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // PDO options for better security and performance
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        // Create PDO connection
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
    } catch (PDOException $e) {
        // Log error and throw exception
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Initialize EasyDB (commented out for testing)
// require_once __DIR__ . '/../vendor/autoload.php';
// $easydb = EasyDB\Factory::fromArray([
//     'driver' => 'mysql',
//     'host' => DB_HOST,
//     'port' => 3306,
//     'database' => DB_NAME,
//     'username' => DB_USER,
//     'password' => DB_PASS,
//     'charset' => 'utf8mb4'
// ]);
?>