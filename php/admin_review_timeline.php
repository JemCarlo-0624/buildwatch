<?php
require_once("auth_check.php");
requireRole(["admin"]);
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: proposals_review.php');
    exit;
}

$proposal_id = $_POST['proposal_id'] ?? null;
$start = $_POST['evaluated_start_date'] ?? null;
$end = $_POST['evaluated_end_date'] ?? null;
$notes = $_POST['evaluation_notes'] ?? null;

if (!$proposal_id || !$start || !$end) {
    $_SESSION['error_message'] = 'Please provide start and end dates.';
    header('Location: proposal_details.php?proposal_id=' . urlencode($proposal_id));
    exit;
}

if (strtotime($end) <= strtotime($start)) {
    $_SESSION['error_message'] = 'End date must be after start date.';
    header('Location: proposal_details.php?proposal_id=' . urlencode($proposal_id));
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE project_proposals SET evaluated_start_date = ?, evaluated_end_date = ?, evaluation_notes = ? WHERE id = ?");
    $stmt->execute([$start, $end, $notes, $proposal_id]);
    $_SESSION['success_message'] = 'Evaluated timeline saved.';
} catch (PDOException $e) {
    error_log('Failed to save evaluated timeline: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error while saving evaluated timeline.';
}

header('Location: proposal_details.php?proposal_id=' . urlencode($proposal_id));
exit;
?>


