<?php
// Start session early and set JSON header
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// Optional: capture/clean any accidental output (helps when display_errors is on)
ob_start();

// Toggle this to false on production
$debug = true;

require_once("../config/db.php"); // ensure DB connection before queries

// Ensure $pdo exists
if (!isset($pdo)) {
    ob_clean();
    http_response_code(500);
    $msg = 'Database connection not initialized ($pdo is missing). Check config/db.php';
    $payload = ['success' => false, 'error' => 'server_error'];
    if ($debug) $payload['message'] = $msg;
    echo json_encode($payload);
    error_log($msg);
    exit;
}

// Validate client session
if (!isset($_SESSION['client_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

// Validate input
$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_project_id']);
    exit;
}

$client_id = $_SESSION['client_id'];

try {
    // Ensure project belongs to the client
    $stmt = $pdo->prepare("SELECT p.* FROM projects p WHERE p.id = ? AND p.client_id = ?");
    $stmt->execute([$project_id, $client_id]);
    $project = $stmt->fetch();

    if (!$project) {
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'access_denied_or_not_found']);
        exit;
    }

    // Get available columns for 'tasks'
    $colStmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'tasks'
    ");
    $colStmt->execute();
    $taskCols = $colStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // Determine completion column
    $completionColumn = null;
    $candidates = ['status','is_completed','completed','done','is_done','progress'];
    foreach ($candidates as $c) {
        if (in_array($c, $taskCols, true)) {
            $completionColumn = $c;
            break;
        }
    }

    // Build expression to detect completed tasks
    if ($completionColumn === 'status') {
        $doneValues = ["'completed'","'done'","'finished'","'closed'","'resolved'"];
        $doneList = implode(',', $doneValues);
        $completedExpr = "CASE WHEN LOWER(TRIM(t.status)) IN ($doneList) THEN 1 ELSE 0 END";
    } elseif ($completionColumn === 'progress') {
        $completedExpr = "CASE WHEN COALESCE(t.progress,0) >= 100 THEN 1 ELSE 0 END";
    } elseif ($completionColumn !== null) {
        $col = $completionColumn;
        $completedExpr = "CASE WHEN (t.`$col` IN (1,'1',TRUE,'true','yes') OR LOWER(TRIM(CAST(t.`$col` AS CHAR))) IN ('completed','done','finished','closed','resolved')) THEN 1 ELSE 0 END";
    } else {
        $completedExpr = "0";
    }

    // Fetch all tasks
    $orderBy = in_array('created_at', $taskCols, true) ? 't.created_at DESC' : 't.id DESC';
    $taskStmt = $pdo->prepare("SELECT t.* FROM tasks t WHERE t.project_id = ? ORDER BY $orderBy");
    $taskStmt->execute([$project_id]);
    $tasks = $taskStmt->fetchAll();

    // Fetch project team
    $teamStmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role
        FROM project_assignments pa
        JOIN users u ON pa.user_id = u.id
        WHERE pa.project_id = ?
    ");
    $teamStmt->execute([$project_id]);
    $team = $teamStmt->fetchAll();

    // Task summary
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

    // Normalize to integers
    $tasks_summary = [
        'total_tasks' => isset($tasks_summary['total_tasks']) ? (int)$tasks_summary['total_tasks'] : 0,
        'completed_tasks' => isset($tasks_summary['completed_tasks']) ? (int)$tasks_summary['completed_tasks'] : 0
    ];

    // --- Compute completion percentage ---
    $total = $tasks_summary['total_tasks'];
    $completed = $tasks_summary['completed_tasks'];
    $avgProgress = 0;

    if (in_array('progress', $taskCols, true) && $total > 0) {
        $avgStmt = $pdo->prepare("SELECT ROUND(AVG(t.progress), 0) AS avg_progress FROM tasks t WHERE t.project_id = ?");
        $avgStmt->execute([$project_id]);
        $avgProgress = (float)($avgStmt->fetchColumn() ?? 0);
    }

    if ($total > 0) {
        $completion_percentage = round(($completed / $total) * 100);
    } elseif ($avgProgress > 0) {
        $completion_percentage = round($avgProgress);
    } else {
        $completion_percentage = 0;
    }

    // Add computed percentage to project data
    $project['completion_percentage'] = $completion_percentage;

    // Clean buffer and output
    ob_clean();

    echo json_encode([
        'success' => true,
        'last_updated' => date('c'),
        'project' => $project,
        'tasks' => $tasks,
        'team' => $team,
        'tasks_summary' => $tasks_summary
    ]);
    exit;

} catch (Throwable $e) {
    // Handle and log error
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
