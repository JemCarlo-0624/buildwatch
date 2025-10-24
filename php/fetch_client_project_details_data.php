<?php
// Start session early and set JSON header
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// Optional: capture/clean any accidental output (helps when display_errors is on)
ob_start();

// Toggle this to false on production
$debug = true;

require_once("../config/db.php"); // moved/ensured before queries

// Quick check: ensure $pdo from config/db.php is available
if (!isset($pdo)) {
    ob_clean();
    http_response_code(500);
    $msg = 'Database connection not initialized ($pdo is missing). Check config/db.php';
    // return helpful message when debugging locally
    $payload = ['success' => false, 'error' => 'server_error'];
    if ($debug) $payload['message'] = $msg;
    echo json_encode($payload);
    error_log($msg);
    exit;
}

// Basic auth check for client session (adjust as your app requires)
if (!isset($_SESSION['client_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

// validate input
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_project_id']);
    exit;
}

$client_id = $_SESSION['client_id'];
try {
    // Ensure the project belongs to the client (change this if you want different access rules)
    $stmt = $pdo->prepare("SELECT p.* FROM projects p WHERE p.id = ? AND p.client_id = ?");
    $stmt->execute([$project_id, $client_id]);
    $project = $stmt->fetch();

    if (!$project) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'access_denied_or_not_found']);
        exit;
    }

    // --- determine tasks table columns so we don't reference missing columns ---
    $colStmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'tasks'
    ");
    $colStmt->execute();
    $taskCols = $colStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // Choose a completion column/expression safely
    $completionColumn = null;
    // include 'progress' because your schema tracks completion via progress (0-100)
    $candidates = ['status','is_completed','completed','done','is_done','progress'];
    foreach ($candidates as $c) {
        if (in_array($c, $taskCols, true)) {
            $completionColumn = $c;
            break;
        }
    }

    // Build a robust expression to detect "completed" tasks:
    // - If there's a textual status column, check for common completed labels (case-insensitive)
    // - If it's a boolean/flag column, accept 1/true/'yes'
    // - If it's 'progress', treat progress >= 100 as completed
    if ($completionColumn === 'status') {
        $doneValues = ["'completed'","'done'","'finished'","'closed'","'resolved'"];
        $doneList = implode(',', $doneValues);
        $completedExpr = "CASE WHEN LOWER(TRIM(t.status)) IN ($doneList) THEN 1 ELSE 0 END";
    } elseif ($completionColumn === 'progress') {
        // numeric progress 0-100; completed when >= 100
        $completedExpr = "CASE WHEN COALESCE(t.progress,0) >= 100 THEN 1 ELSE 0 END";
    } elseif ($completionColumn !== null) {
        $col = $completionColumn;
        $completedExpr = "CASE WHEN (t.`$col` IN (1,'1',TRUE,'true','yes') OR LOWER(TRIM(CAST(t.`$col` AS CHAR))) IN ('completed','done','finished','closed','resolved')) THEN 1 ELSE 0 END";
    } else {
        // no completion indicator found — treat as no completed tasks
        $completedExpr = "0";
    }

    // Fetch tasks for this project — be tolerant if created_at doesn't exist
    $orderBy = in_array('created_at', $taskCols, true) ? 't.created_at DESC' : 't.id DESC';
    $taskStmt = $pdo->prepare("SELECT t.* FROM tasks t WHERE t.project_id = ? ORDER BY $orderBy");
    $taskStmt->execute([$project_id]);
    $tasks = $taskStmt->fetchAll();

    // Fetch project team (users assigned to this project)
    $teamStmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role
        FROM project_assignments pa
        JOIN users u ON pa.user_id = u.id
        WHERE pa.project_id = ?
    ");
    $teamStmt->execute([$project_id]);
    $team = $teamStmt->fetchAll();

    // Tasks summary using the safe expression; COALESCE ensures a numeric 0 result
    $summarySql = "
        SELECT 
            COUNT(*) AS total_tasks,
            COALESCE(SUM($completedExpr),0) AS completed_tasks
        FROM tasks t
        WHERE t.project_id = ?
    ";
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute([$project_id]);
    $tasks_summary = $summaryStmt->fetch();

    // ensure numeric types for JSON consumers (stat cards expect integers)
    $tasks_summary = [
        'total_tasks' => isset($tasks_summary['total_tasks']) ? (int)$tasks_summary['total_tasks'] : 0,
        'completed_tasks' => isset($tasks_summary['completed_tasks']) ? (int)$tasks_summary['completed_tasks'] : 0
    ];

    // Clear buffer just before output to avoid stray HTML/PHP notices
    ob_clean();

    echo json_encode([
        'success' => true,
        'last_updated' => date('c'),
        'project' => $project,
        'tasks' => $tasks,
        'team' => $team,
        'tasks_summary' => $tasks_summary ?: ['total_tasks' => 0, 'completed_tasks' => 0]
    ]);
    exit;
} catch (Throwable $e) {
    // Log full details to server log and return helpful JSON when debugging locally
    error_log("fetch_client_project_details_data error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    ob_clean();
    http_response_code(500);
    $payload = ['success' => false, 'error' => 'server_error'];
    if ($debug) {
        $payload['message'] = $e->getMessage();
        $payload['file'] = $e->getFile();
        $payload['line'] = $e->getLine();
        $payload['trace'] = $e->getTraceAsString();
    }
    echo json_encode($payload);
    exit;
}
