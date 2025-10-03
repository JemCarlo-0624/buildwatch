<?php
session_start();
require_once("../config/db.php");

// Grab form data
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']   = $user['role'];
    $_SESSION['name']   = $user['name'];
    header("Location: dashboard_" . $user['role'] . ".php");
    exit;
}


// If failed
echo "❌ Invalid email or password. <a href='login.php'>Try again</a>";
