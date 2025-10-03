<?php
$dsn = "mysql:host=localhost;dbname=buildwatch;charset=utf8mb4";
$username = "root"; // default in XAMPP/WAMP
$password = "";     // usually empty for local dev

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "✅ Database connection successful!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
