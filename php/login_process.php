<?php
session_start();
require_once("../config/db.php");

// Grab form data
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validate inputs
if (empty($email)) {
    $_SESSION['error'] = 'Email is required.';
    header("Location: login.php");
    exit;
}

if (empty($password)) {
    $_SESSION['error'] = 'Password is required.';
    header("Location: login.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header("Location: login.php");
    exit;
}
// </CHANGE>

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

$_SESSION['error'] = 'Invalid email or password. Please try again.';
header("Location: login.php");
exit;
// </CHANGE>
