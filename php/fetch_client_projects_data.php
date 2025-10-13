<?php
session_start();
require_once("../config/db.php");

header('Content-Type: application/json');

error_log("[v0] fetch_client_projects_data.php called");
error_log("[v0] Session client_id: " . ($_SESSION['client_id'] ?? 'NOT SET'));

if (!isset($_SESSION['client_id'])) {
    error_log("[v0] Unauthorized access - no client_id in session");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$client_id = $_SESSION['client_id'];
error_log("[v0] Fetching projects for client_id: " . $client_id);

try {
    $checkStmt = $pdo->query("SELECT COUNT(*) as total, GROUP_CONCAT(DISTINCT client_id) as client_ids FROM projects");
    $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
    error_log("[v0] Total projects in database: " . $checkResult['total']);
    error_log("[v0] Client IDs in projects table: " . ($checkResult['client_ids'] ?? 'NONE'));
    
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.name as created_by_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND progress = 100) as completed_tasks,
               (SELECT AVG(progress) FROM tasks WHERE project_id = p.id) as avg_task_progress
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.client_id = ?
        ORDER BY p.last_activity_at DESC
    ");
    
    error_log("[v0] Executing query with client_id parameter: " . $client_id);
    
    $stmt->execute([$client_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("[v0] Query executed successfully");
    error_log("[v0] Found " . count($projects) . " projects for client_id: " . $client_id);
    
    if (count($projects) === 0) {
        $clientCheck = $pdo->prepare("SELECT id, name, email FROM clients WHERE id = ?");
        $clientCheck->execute([$client_id]);
        $clientData = $clientCheck->fetch(PDO::FETCH_ASSOC);
        error_log("[v0] Client data: " . json_encode($clientData));
        
        $proposalCheck = $pdo->prepare("SELECT id, title, status FROM project_proposals WHERE client_id = ?");
        $proposalCheck->execute([$client_id]);
        $proposals = $proposalCheck->fetchAll(PDO::FETCH_ASSOC);
        error_log("[v0] Client has " . count($proposals) . " proposals: " . json_encode($proposals));
    }

    foreach ($projects as &$project) {
        $project['total_tasks'] = (int)$project['total_tasks'];
        $project['completed_tasks'] = (int)$project['completed_tasks'];
        
        // Calculate progress from tasks
        if ($project['total_tasks'] > 0) {
            $project['progress'] = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
        } else {
            $project['progress'] = (int)($project['completion_percentage'] ?? 0);
        }
        
        $project['avg_task_progress'] = $project['avg_task_progress'] ? round($project['avg_task_progress'], 2) : 0;
        
        // Format dates
        if (isset($project['start_date']) && $project['start_date']) {
            $project['start_date_formatted'] = date('M d, Y', strtotime($project['start_date']));
        }
        if (isset($project['end_date']) && $project['end_date']) {
            $project['end_date_formatted'] = date('M d, Y', strtotime($project['end_date']));
        }
        
        // Format budget
        if (isset($project['budget']) && $project['budget']) {
            $project['budget_formatted'] = '$' . number_format($project['budget'], 2);
        }
    }

    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'count' => count($projects),
        'timestamp' => time()
    ]);

} catch (PDOException $e) {
    error_log("[v0] Fetch projects error: " . $e->getMessage());
    error_log("[v0] Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?>
