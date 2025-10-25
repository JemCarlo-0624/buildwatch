<?php
require_once("auth_check.php");
requireRole(["admin"]);
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: proposals_review.php");
    exit;
}

$budget_id = $_POST['budget_id'] ?? null;
$proposal_id = $_POST['proposal_id'] ?? null;
$evaluated_amount = $_POST['evaluated_amount'] ?? null;
$remarks = $_POST['remarks'] ?? '';
$item_names = $_POST['item_name'] ?? [];
$costs = $_POST['cost'] ?? [];
$categories = $_POST['category'] ?? [];

if (!$budget_id || !$proposal_id || !$evaluated_amount) {
    $_SESSION['error_message'] = "Missing required fields.";
    header("Location: admin_review_budget.php?proposal_id=$proposal_id");
    exit;
}

try {
    $pdo->beginTransaction();

    // === 1. Fetch current budget data to determine status ===
    $budgetCheck = $pdo->prepare("
        SELECT pb.proposed_amount, p.client_id, p.title, p.description, p.start_date, p.end_date, p.status as proposal_status
        FROM project_budgets pb
        JOIN project_proposals p ON pb.proposal_id = p.id
        WHERE pb.id = ?
    ");
    $budgetCheck->execute([$budget_id]);
    $budgetData = $budgetCheck->fetch(PDO::FETCH_ASSOC);

    $status = ($evaluated_amount > $budgetData['proposed_amount']) ? 'pending_client' : 'approved';

    // === 2. Update project_budgets ===
    $stmt = $pdo->prepare("
        UPDATE project_budgets 
        SET evaluated_amount = ?, status = ?, remarks = ?, admin_comment = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$evaluated_amount, $status, $remarks, $remarks, $budget_id]);

    // === 3. Replace all budget_breakdowns ===
    $deleteStmt = $pdo->prepare("DELETE FROM budget_breakdowns WHERE budget_id = ?");
    $deleteStmt->execute([$budget_id]);

    for ($i = 0; $i < count($item_names); $i++) {
        $item = trim($item_names[$i] ?? '');
        $cost = floatval($costs[$i] ?? 0);
        $cat = $categories[$i] ?? 'misc';

        if (!empty($item) && $cost > 0) {
            $stmt2 = $pdo->prepare("
                INSERT INTO budget_breakdowns (budget_id, item_name, category, estimated_cost)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([$budget_id, $item, $cat, $cost]);
        }
    }

    // === 4. Record admin evaluation in budget_reviews ===
    $admin_id = $_SESSION['user_id'] ?? 24;
    $reviewStmt = $pdo->prepare("
        INSERT INTO budget_reviews (proposal_id, admin_id, evaluated_amount, status, remarks, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $reviewStmt->execute([$proposal_id, $admin_id, $evaluated_amount, $status, $remarks]);

    if ($status === 'approved') {
        error_log("[v0] Creating project for proposal_id: $proposal_id, client_id: " . $budgetData['client_id']);
        
        // Check if project already exists for this proposal
        $checkProject = $pdo->prepare("
            SELECT id FROM projects WHERE client_id = ? AND name = ?
        ");
        $checkProject->execute([$budgetData['client_id'], $budgetData['title']]);
        $existingProject = $checkProject->fetch(PDO::FETCH_ASSOC);

        if (!$existingProject) {
            // Create new project from approved proposal
            $insertProject = $pdo->prepare("
                INSERT INTO projects (name, description, status, created_by, client_id, start_date, end_date, last_activity_at) 
                VALUES (?, ?, 'active', ?, ?, ?, ?, NOW())
            ");
            $result = $insertProject->execute([
                $budgetData['title'],
                $budgetData['description'],
                $admin_id,
                $budgetData['client_id'],
                $budgetData['start_date'],
                $budgetData['end_date']
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

        // Update proposal status to approved
        $updateProposal = $pdo->prepare("UPDATE project_proposals SET status = 'approved' WHERE id = ?");
        $updateProposal->execute([$proposal_id]);
    }

    // === 5. Check if evaluated amount > proposed and notify client ===
    if ($evaluated_amount > $budgetData['proposed_amount']) {
        $client_id = $budgetData['client_id'];

        // Update budget status to pending_client
        $updateStatus = $pdo->prepare("
            UPDATE project_budgets 
            SET status = 'pending_client', client_decision = 'pending'
            WHERE id = ?
        ");
        $updateStatus->execute([$budget_id]);

        // === 6. Notify client ===
        $notify = $pdo->prepare("
            INSERT INTO notifications (client_id, type, title, message, link) 
            VALUES (?, 'budget_review', 'Budget Re-evaluation', ?, ?)
        ");
        $msg = 'Your proposal budget has been re-evaluated to ₱' . number_format($evaluated_amount, 2) .
               '. Please review and accept or cancel.';
        $link = 'client_budget_review.php?budget_id=' . $budget_id;
        $notify->execute([$client_id, $msg, $link]);
    }

    $pdo->commit();

    $_SESSION['success_message'] = "Budget review submitted successfully! Status: " . ucfirst($status);
    if ($status === 'approved') {
        $_SESSION['success_message'] .= " Project created and activated for client.";
    }
    header("Location: proposals_review.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Budget approval error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error submitting budget review: " . $e->getMessage();
    header("Location: admin_review_budget.php?proposal_id=$proposal_id");
    exit;
}
?>
