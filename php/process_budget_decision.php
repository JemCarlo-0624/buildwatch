<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check client authentication
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate input parameters
$budget_id = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
$decision = filter_input(INPUT_POST, 'decision', FILTER_SANITIZE_STRING);

if (!$budget_id || !in_array($decision, ['accepted', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify budget belongs to client
    $stmt = $pdo->prepare("
        SELECT pb.*, pp.client_id, pp.id as proposal_id
        FROM project_budgets pb
        JOIN project_proposals pp ON pb.proposal_id = pp.id
        WHERE pb.id = ? AND pp.client_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$budget_id, $_SESSION['client_id']]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        throw new Exception('Budget not found or access denied');
    }

    // Update budget status
    $status = ($decision === 'accepted') ? 'approved' : 'cancelled';
    $stmt = $pdo->prepare("
        UPDATE project_budgets
        SET client_decision = ?, 
            status = ?, 
            decision_date = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$decision, $status, $budget_id]);

    // If decision is accepted, update the project proposal status
    if ($decision === 'accepted') {
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'approved'
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);
    } else {
        // If rejected, update proposal status to rejected
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'rejected'
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error processing decision: ' . $e->getMessage()
    ]);
}