<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Ensure client is logged in
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Fetch unread notifications first, then read ones
    $stmt = $pdo->prepare("
        SELECT 
            n.id,
            n.message,
            n.link,
            n.is_read,
            n.created_at
        FROM notifications n
        WHERE n.user_id = ?
        ORDER BY n.is_read ASC, n.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$_SESSION['client_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unread count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $countStmt->execute([$_SESSION['client_id']]);
    $unreadCount = $countStmt->fetchColumn();

    // Format dates for display
    foreach ($notifications as &$notif) {
        $notif['formatted_date'] = date('M j, g:i a', strtotime($notif['created_at']));
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
