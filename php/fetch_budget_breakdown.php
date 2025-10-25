<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate input
$budget_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$budget_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid budget ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            pb.*,
            pp.budget as proposed_budget,
            pp.title as proposal_title,
            pp.client_id
        FROM project_budgets pb
        JOIN project_proposals pp ON pb.proposal_id = pp.id
        WHERE pb.id = ? AND pp.client_id = ?
    ");
    $stmt->execute([$budget_id, $_SESSION['client_id']]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Budget not found']);
        exit;
    }

    $proposed = (float)($budget['proposed_budget'] ?? 0);
    $admin_eval = (float)($budget['admin_evaluation'] ?? 0);
    $difference = $admin_eval - $proposed;

    echo json_encode([
        'success' => true,
        'budget_id' => (int)$budget['id'],
        'proposal_title' => $budget['proposal_title'],
        'proposed_budget' => $proposed,
        'admin_evaluation' => $admin_eval,
        'difference' => $difference,
        'admin_comment' => $budget['admin_comment'] ?? '',
        'status' => $budget['status'] ?? 'pending'
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
