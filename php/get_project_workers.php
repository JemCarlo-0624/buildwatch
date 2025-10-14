<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get project_id from request
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid project ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.name, u.email 
        FROM users u
        INNER JOIN project_assignments pa ON pa.user_id = u.id
        WHERE u.role = 'worker' 
        AND pa.project_id = ?
        ORDER BY u.name ASC
    ");
    $stmt->execute([$project_id]);
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'workers' => $workers]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
