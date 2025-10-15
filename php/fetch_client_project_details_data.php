<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$client_id = $_SESSION['client_id'];

if (!$project_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM projects p
        WHERE p.id = ? AND p.client_id = ?
    ");
    $stmt->execute([$project_id, $client_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Access denied or project not found']);
        exit;
    }

    // Fetch tasks
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as assigned_to_name, u.email as assigned_to_email
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = ?
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate tasks summary
    $tasks_summary = [
        'total_tasks' => count($tasks),
        'completed_tasks' => 0,
        'in_progress_tasks' => 0,
        'pending_tasks' => 0
    ];

    foreach ($tasks as $task) {
        if ($task['progress'] == 100) {
            $tasks_summary['completed_tasks']++;
        } elseif ($task['progress'] > 0) {
            $tasks_summary['in_progress_tasks']++;
        } else {
            $tasks_summary['pending_tasks']++;
        }
    }

    $project['completion_percentage'] = isset($project['completion_percentage']) && $project['completion_percentage'] !== null 
        ? (int)$project['completion_percentage'] 
        : 0;

    // Fetch team members
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.role, pa.assigned_at
        FROM project_assignments pa 
        JOIN users u ON pa.user_id = u.id 
        WHERE pa.project_id = ?
        ORDER BY pa.assigned_at ASC
    ");
    $stmt->execute([$project_id]);
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch creator info
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$project['created_by']]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format dates and budget
    if ($project['start_date']) {
        $project['start_date_formatted'] = date('M d, Y', strtotime($project['start_date']));
    }
    if ($project['end_date']) {
        $project['end_date_formatted'] = date('M d, Y', strtotime($project['end_date']));
    }
    if ($project['budget']) {
        $project['budget_formatted'] = '$' . number_format($project['budget'], 2);
    }
    $project['created_at_formatted'] = date('M d, Y', strtotime($project['created_at']));
    if (isset($project['last_activity_at'])) {
        $project['last_activity_formatted'] = date('M d, Y g:i A', strtotime($project['last_activity_at']));
    }

    $response = [
        'success' => true,
        'project' => $project,
        'tasks' => $tasks,
        'tasks_summary' => $tasks_summary,
        'team' => $team,
        'creator' => $creator,
        'last_updated' => date('Y-m-d H:i:s'),
        'timestamp' => time()
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Fetch project details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
?>
