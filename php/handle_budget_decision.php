<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Verify client session
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Validate inputs
$budget_id = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
$decision = filter_input(INPUT_POST, 'decision', FILTER_SANITIZE_STRING);

if (!$budget_id || !in_array($decision, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Log which database PHP is connected to (for debugging)
    error_log("Connected to DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn());

    $pdo->beginTransaction();

    // Get budget and proposal details
    $stmt = $pdo->prepare("
        SELECT pb.id, pb.proposal_id, pp.client_id, pp.title
        FROM project_budgets pb
        JOIN project_proposals pp ON pb.proposal_id = pp.id
        WHERE pb.id = ? AND pp.client_id = ?
    ");
    $stmt->execute([$budget_id, $_SESSION['client_id']]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        throw new Exception("Budget not found or access denied");
    }

    // ✅ Check if columns exist before updating
    $columns = $pdo->query("SHOW COLUMNS FROM project_budgets")->fetchAll(PDO::FETCH_COLUMN);
    $hasUpdatedAt = in_array('updated_at', $columns);

    // Decision handling
    if ($decision === 'accept') {
        // Update proposal
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'approved',
                client_decision = 'approved',
                decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);

        // Update budget (conditionally handle missing column)
        $updateQuery = "
            UPDATE project_budgets
            SET client_decision = 'approved',
                status = 'approved'" . ($hasUpdatedAt ? ", updated_at = NOW()" : "") . "
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([$budget_id]);

        $message = "Client has approved the budget for proposal: " . htmlspecialchars($budget['title']);
        $success_msg = "Budget approved successfully!";
    } else {
        // Update proposal
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'rejected',
                client_decision = 'rejected',
                decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);

        // Update budget (conditionally handle missing column)
        $updateQuery = "
            UPDATE project_budgets
            SET client_decision = 'rejected',
                status = 'rejected'" . ($hasUpdatedAt ? ", updated_at = NOW()" : "") . "
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([$budget_id]);

        $message = "Client has rejected the budget for proposal: " . htmlspecialchars($budget['title']);
        $success_msg = "Budget rejected successfully!";
    }

    // Add notification for admin (client_id = 1)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (client_id, type, title, message, link, created_at)
            VALUES (?, 'budget_review', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            1,
            "Budget Decision",
            $message,
            "proposals_review.php?proposal_id=" . $budget['proposal_id']
        ]);
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $success_msg
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Budget decision error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
