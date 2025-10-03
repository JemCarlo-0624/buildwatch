<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array($_SESSION['role'], ['pm','admin'])) { header("Location: login.php"); exit; }

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id=?");
    $stmt->execute([$id]);
}
header("Location: projects_list.php");
exit;
