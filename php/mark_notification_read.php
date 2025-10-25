<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$notification_id = $_POST['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW()
        WHERE id = ? AND client_id = ?
    ");
    $stmt->execute([$notification_id, $_SESSION['client_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Mark notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
