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
        SELECT pp.id, pp.title, pp.description, pp.client_id, pp.start_date, pp.end_date, pb.id as budget_id
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

        error_log("[v0] Creating project for proposal_id: $proposal_id, client_id: " . $proposal['client_id']);
        
        // Check if project already exists for this proposal
        $checkProject = $pdo->prepare("
            SELECT id FROM projects WHERE client_id = ? AND name = ?
        ");
        $checkProject->execute([$proposal['client_id'], $proposal['title']]);
        $existingProject = $checkProject->fetch(PDO::FETCH_ASSOC);

        if (!$existingProject) {
            // Create new project from approved proposal
            $admin_id = 1; // Default admin user
            $insertProject = $pdo->prepare("
                INSERT INTO projects (name, description, status, created_by, client_id, start_date, end_date, last_activity_at) 
                VALUES (?, ?, 'ongoing', ?, ?, ?, ?, NOW())
            ");
            $result = $insertProject->execute([
                $proposal['title'],
                $proposal['description'],
                $admin_id,
                $proposal['client_id'],
                $proposal['start_date'],
                $proposal['end_date']
            ]);

            if (!$result) {
                error_log("[v0] Project insert failed: " . json_encode($insertProject->errorInfo()));
                throw new Exception("Failed to create project: " . $insertProject->errorInfo()[2]);
            }

            $projectId = $pdo->lastInsertId();
            error_log("[v0] Project created with ID: $projectId");

            // Assign project to admin as default
            $assignStmt = $pdo->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
            $assignResult = $assignStmt->execute([$projectId, $admin_id]);
            
            if (!$assignResult) {
                error_log("[v0] Project assignment failed: " . json_encode($assignStmt->errorInfo()));
                throw new Exception("Failed to assign project: " . $assignStmt->errorInfo()[2]);
            }
            
            error_log("[v0] Project assigned to user: $admin_id");
        } else {
            error_log("[v0] Project already exists for this proposal");
        }

        $message = "Client has approved the budget for proposal: " . htmlspecialchars($proposal['title']);
        $success_msg = "Budget approved successfully! Project has been created.";
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
