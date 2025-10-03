<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Only admin can delete users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Prevent admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        header("Location: users_list.php?error=cannot_delete_self");
        exit;
    }
    
    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
}

header("Location: users_list.php");
exit;
