<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Log the start of the process
error_log("Starting client decision process");

// Check if user is logged in as client
if (!isset($_SESSION['client_id'])) {
    header("Location: client_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: client_dashboard.php");
    exit;
}

$budget_id = $_POST['budget_id'] ?? null;
$decision = $_POST['decision'] ?? null;

if (!$budget_id || !$decision || !in_array($decision, ['accepted', 'rejected'])) {
    $_SESSION['error'] = "Invalid request parameters";
    header("Location: client_dashboard.php");
    exit;
}

try {
    $pdo->beginTransaction();
    error_log("Transaction started");

    // Fetch budget + proposal details with detailed logging
    $stmt = $pdo->prepare("
        SELECT pb.*, pp.id AS proposal_id, pp.client_id, pp.admin_id,
               pp.title, pp.description, pp.start_date, pp.end_date
        FROM project_budgets pb
        JOIN project_proposals pp ON pb.proposal_id = pp.id
        WHERE pb.id = ? AND pp.client_id = ?
    ");
    
    error_log("Executing budget fetch query for budget_id: {$budget_id} and client_id: {$_SESSION['client_id']}");
    $stmt->execute([$budget_id, $_SESSION['client_id']]);
    $budget = $stmt->fetch(PDO::FETCH_ASSOC);

    // Log the fetched budget data
    error_log("Fetched budget data: " . print_r($budget, true));

    if (!$budget) {
        throw new Exception("Budget data not found");
    }

    // Update budget status
    $status = ($decision === 'accepted') ? 'approved' : 'cancelled';
    $stmt = $pdo->prepare("
        UPDATE project_budgets 
        SET client_decision = ?, status = ?, decision_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$decision, $status, $budget_id]);
    error_log("Updated budget status to: {$status}");

    if ($decision === 'accepted') {
        error_log("Client accepted the budget - creating project");

        // Create project with exact matching columns
        $insert = $pdo->prepare("
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
                end_date,
                category,
                created_at,
                last_activity_at
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
                :end_date,
                'construction',
                NOW(),
                NOW()
            )
        ");

        $projectData = [
            ':name' => $budget['title'],
            ':description' => $budget['description'],
            ':created_by' => $budget['admin_id'],
            ':client_id' => $budget['client_id'],
            ':start_date' => $budget['start_date'],
            ':end_date' => $budget['end_date']
        ];

        error_log("Attempting to insert project with data: " . print_r($projectData, true));

        try {
            if (!$insert->execute($projectData)) {
                $error = $insert->errorInfo();
                error_log("Project creation failed: " . print_r($error, true));
                throw new Exception("Failed to create project: " . $error[2]);
            }

            $project_id = $pdo->lastInsertId();
            error_log("Project created successfully with ID: {$project_id}");

            // Update proposal status
            $updateProposal = $pdo->prepare("
                UPDATE project_proposals 
                SET status = 'approved' 
                WHERE id = :proposal_id
            ");
            $updateProposal->execute([':proposal_id' => $budget['proposal_id']]);

        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    // Notify admin
    $message = ($decision === 'accepted')
        ? "Client has accepted your evaluated budget. A project has been created automatically."
        : "Client has rejected the budget and cancelled the proposal.";

    $notify = $pdo->prepare("
        INSERT INTO notifications (user_id, message, link)
        VALUES (?, ?, 'proposals_review.php')
    ");
    $notify->execute([$budget['admin_id'], $message]);

    $pdo->commit();
    error_log("Transaction committed successfully");

    $_SESSION['success'] = ($decision === 'accepted')
        ? "You have accepted the budget. The project is now created and active."
        : "You have rejected the budget evaluation.";

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in process_client_decision: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: client_dashboard.php");
exit;
