<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Allow only admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role  = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $pass, $role]);

    header("Location: users_list.php");
    exit;
}
?>
