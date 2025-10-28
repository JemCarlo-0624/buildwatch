<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// ✅ Validate session (client or admin)
if (!isset($_SESSION['client_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ✅ Validate input
$budget_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$budget_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid budget ID']);
    exit;
}

try {
    // ✅ Fetch details including evaluated timeline from project_proposals
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("
            SELECT 
                pb.*,
                pp.budget AS proposed_budget,
                pp.client_id,
                pp.evaluated_start_date,
                pp.evaluated_end_date,
                pp.evaluation_notes
            FROM project_budgets pb
            JOIN project_proposals pp ON pb.proposal_id = pp.id
            WHERE pb.id = ?
        ");
        $stmt->execute([$budget_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                pb.*,
                pp.budget AS proposed_budget,
                pp.client_id,
                pp.evaluated_start_date,
                pp.evaluated_end_date,
                pp.evaluation_notes
            FROM project_budgets pb
            JOIN project_proposals pp ON pb.proposal_id = pp.id
            WHERE pb.id = ? AND pp.client_id = ?
        ");
        $stmt->execute([$budget_id, $_SESSION['client_id']]);
    }

    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Budget not found']);
        exit;
    }

    // ✅ Fallback handling
    $proposed = $budget['proposed_budget'] ?? $budget['proposed_amount'] ?? 0;
    $evaluated_amount = (float)($budget['evaluated_amount'] ?? 0);
    $difference = (float)($evaluated_amount - $proposed);

    // ✅ Return everything to JS modal
    echo json_encode([
        'success' => true,
        'proposed_budget' => (float)$proposed,
        'admin_evaluation' => $evaluated_amount,
        'difference' => $difference,
        'admin_comment' => $budget['admin_comment'] ?? '',
        'evaluated_start_date' => $budget['evaluated_start_date'] ?? null,
        'evaluated_end_date' => $budget['evaluated_end_date'] ?? null,
        'evaluation_notes' => $budget['evaluation_notes'] ?? ''
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
