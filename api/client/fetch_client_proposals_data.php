<?php
session_start();
require_once(__DIR__ . '/../../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$client_id = $_SESSION['client_id'];

try {
    $stmt = $pdo->prepare("
        SELECT pp.id, pp.title, pp.description, pp.status, pp.submitted_at,
               c.name as client_name, c.email as client_email
        FROM project_proposals pp
        JOIN clients c ON pp.client_id = c.id
        WHERE pp.client_id = ? 
        ORDER BY pp.id DESC
    ");
    $stmt->execute([$client_id]);
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'proposals' => $proposals,
        'count' => count($proposals),
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Fetch proposals error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch proposals'
    ]);
}
?>
