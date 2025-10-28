<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// ✅ Verify client session
if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ✅ Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// ✅ Validate inputs
$budget_id = filter_input(INPUT_POST, 'budget_id', FILTER_VALIDATE_INT);
$decision  = filter_input(INPUT_POST, 'decision', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: $_POST['decision'];

if (!$budget_id || !in_array($decision, ['accept', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    error_log("Starting budget decision process for budget_id: " . $budget_id);
    $pdo->beginTransaction();

    // ✅ Fetch proposal + budget info
    $stmt = $pdo->prepare("
        SELECT 
            pb.id,
            pb.proposal_id,
            pb.status AS budget_status,
            pp.admin_id,
            pp.client_id,
            pp.title,
            pp.description,
            pp.start_date,
            pp.end_date,
            pp.evaluated_start_date,
            pp.evaluated_end_date
        FROM project_budgets pb
        JOIN project_proposals pp ON pb.proposal_id = pp.id
        WHERE pb.id = ? AND pp.client_id = ?
    ");
    $stmt->execute([$budget_id, $_SESSION['client_id']]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$budget) {
        throw new Exception("Budget not found or access denied");
    }

    // ✅ Validate required fields
    $requiredFields = ['title', 'description', 'client_id'];
    foreach ($requiredFields as $field) {
        if (empty($budget[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // ✅ Handle client decision
    if ($decision === 'accept') {

        // ✅ Determine final start and end dates (use evaluated if available)
        $startDate = $budget['evaluated_start_date'] ?: $budget['start_date'];
        $endDate   = $budget['evaluated_end_date'] ?: $budget['end_date'];

        // --- Create a new project record ---
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                name,
                description,
                status,
                completion_percentage,
                priority,
                created_by,
                client_id,
                timeline,
                start_date,
                end_date
            ) VALUES (
                :name,
                :description,
                'ongoing',
                0,
                'medium',
                :created_by,
                :client_id,
                NULL,
                :start_date,
                :end_date
            )
        ");

        $projectData = [
            ':name'        => $budget['title'],
            ':description' => $budget['description'],
            ':created_by'  => $budget['admin_id'] ?? 1,
            ':client_id'   => $budget['client_id'],
            ':start_date'  => $startDate,
            ':end_date'    => $endDate
        ];

        if (!$stmt->execute($projectData)) {
            $error = $stmt->errorInfo();
            throw new Exception("Failed to create project: " . $error[2]);
        }

        $project_id = $pdo->lastInsertId();
        error_log("✅ Project created with ID: " . $project_id);

        // --- Update proposal status ---
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'approved',
                client_decision = 'approved',
                decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);

        // --- Update budget record ---
        $stmt = $pdo->prepare("
            UPDATE project_budgets
            SET client_decision = 'approved',
                status = 'approved',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget_id]);

        $message = "Client has approved the budget and a project has been created for: " . htmlspecialchars($budget['title']);
        $success_msg = "Budget approved and project created successfully!";

    } else {
        // --- Client rejected the budget ---
        $stmt = $pdo->prepare("
            UPDATE project_proposals
            SET status = 'rejected',
                client_decision = 'rejected',
                decision_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget['proposal_id']]);

        $stmt = $pdo->prepare("
            UPDATE project_budgets
            SET client_decision = 'rejected',
                status = 'rejected',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$budget_id]);

        $message = "Client has rejected the budget for proposal: " . htmlspecialchars($budget['title']);
        $success_msg = "Budget rejected successfully!";
    }

    // ✅ Send notification to admin
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (client_id, type, title, message, link, created_at)
            VALUES (?, 'budget_review', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            1, // Admin ID (system)
            "Budget Decision",
            $message,
            "proposals_review.php?proposal_id=" . $budget['proposal_id']
        ]);
    } catch (Exception $e) {
        error_log("⚠️ Notification creation failed: " . $e->getMessage());
    }

    // ✅ Commit all changes
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $success_msg
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("❌ Budget decision error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
