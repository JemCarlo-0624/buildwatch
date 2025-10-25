<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in as client
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

header('Content-Type: application/json');

$proposal_id = $_POST['proposal_id'] ?? null;
$decision = $_POST['decision'] ?? null;

if (!$proposal_id || !$decision || !in_array($decision, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get proposal and budget details
    $stmt = $pdo->prepare("
        SELECT pp.id, pp.title, pb.id as budget_id
        FROM project_proposals pp
        LEFT JOIN project_budgets pb ON pp.id = pb.proposal_id
        WHERE pp.id = ? AND pp.client_id = ?
    ");
    $stmt->execute([$proposal_id, $_SESSION['client_id']]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        throw new Exception("Proposal not found or access denied");
    }

    if ($decision === 'accept') {
        // Update proposal status to client_approved
        $stmt = $pdo->prepare("
            UPDATE project_proposals 
            SET status = 'client_approved', client_decision = 'approved', decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$proposal_id]);

        // Update budget status
        if ($proposal['budget_id']) {
            $stmt = $pdo->prepare("
                UPDATE project_budgets 
                SET client_decision = 'approved', status = 'approved', decision_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$proposal['budget_id']]);
        }

        $message = "Client has approved the budget for proposal: " . htmlspecialchars($proposal['title']);
        $success_msg = "Budget approved successfully!";
    } else {
        // Update proposal status to rejected
        $stmt = $pdo->prepare("
            UPDATE project_proposals 
            SET status = 'rejected', client_decision = 'rejected', decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$proposal_id]);

        // Update budget status
        if ($proposal['budget_id']) {
            $stmt = $pdo->prepare("
                UPDATE project_budgets 
                SET client_decision = 'rejected', status = 'rejected', decision_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$proposal['budget_id']]);
        }

        $message = "Client has rejected the budget for proposal: " . htmlspecialchars($proposal['title']);
        $success_msg = "Budget rejected successfully!";
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (client_id, message, link, type, created_at)
            VALUES (?, ?, ?, 'budget_review', NOW())
        ");
        // Using client_id 1 (admin) - adjust if needed
        $stmt->execute([1, $message, "proposals_review.php?proposal_id=" . $proposal_id]);
    } catch (Exception $e) {
        // Notifications table might not exist, continue anyway
        error_log("Notification creation failed: " . $e->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $success_msg
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Process decision error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
