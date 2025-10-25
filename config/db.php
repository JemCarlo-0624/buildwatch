<?php
// Add this at the top of the file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');
error_reporting(E_ALL);

// Database configuration
$host = "localhost";
$db   = "buildwatch";
$user = "root";     // change if needed
$pass = "";         // change if needed
$charset = "utf8mb4";

define('DB_HOST', $host);
define('DB_NAME', $db);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_PASSWORD', $pass); // Alias for compatibility
define('DB_PORT', '3306');
define('DB_CHARSET', $charset);

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("❌ DB Connection Failed: " . $e->getMessage());
}
?>
