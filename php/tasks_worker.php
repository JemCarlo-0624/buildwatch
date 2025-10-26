<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Only workers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch tasks assigned to this worker
$stmt = $pdo->prepare("SELECT t.*, p.name as project_name FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? ORDER BY t.due_date ASC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, function($task) { return $task['progress'] == 100; }));
$inProgressTasks = count(array_filter($tasks, function($task) { return $task['progress'] > 0 && $task['progress'] < 100; }));
$notStartedTasks = count(array_filter($tasks, function($task) { return $task['progress'] == 0; }));

// Calculate overdue tasks
$overdueTasks = 0;
foreach ($tasks as $task) {
    if (!empty($task['due_date']) && strtotime($task['due_date']) < time() && $task['progress'] < 100) {
        $overdueTasks++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $progress = min(100, max(0, (int)$_POST['progress'])); // clamp 0–100

    $stmt = $pdo->prepare("SELECT project_id, progress FROM tasks WHERE id=? AND assigned_to=?");
    $stmt->execute([$task_id, $user_id]);
    $taskData = $stmt->fetch();
    
    if ($taskData) {
        $project_id = $taskData['project_id'];
        $previousProgress = $taskData['progress'];
        
        $completedAt = null;
        if ($progress == 100 && $previousProgress != 100) {
            $completedAt = date('Y-m-d H:i:s');
        }
        
        // Update task progress and completed_at if applicable
        if ($completedAt) {
            $stmt = $pdo->prepare("UPDATE tasks SET progress=?, completed_at=? WHERE id=? AND assigned_to=?");
            $stmt->execute([$progress, $completedAt, $task_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tasks SET progress=? WHERE id=? AND assigned_to=?");
            $stmt->execute([$progress, $task_id, $user_id]);
        }
        
        // Only update last_activity_at timestamp
        $stmt = $pdo->prepare("
            UPDATE projects 
            SET last_activity_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$project_id]);
    }

    header("Location: tasks_worker.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* HCI-focused improvements: better visual hierarchy, accessibility, feedback, and error prevention */
        
        /* Filter controls for better task scanning and cognitive load reduction */
        .filter-controls {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-2xl);
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all var(--transition-normal);
            color: var(--gray);
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .search-box {
            position: relative;
            margin-bottom: var(--space-lg);
            max-width: 400px;
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: border-color var(--transition-normal);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 99, 165, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        /* New card grid layout replacing timeline */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-2xl);
        }

        .task-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: var(--radius-md);
            padding: var(--space-lg);
            transition: all var(--transition-normal);
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .task-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(10, 99, 165, 0.15);
            transform: translateY(-2px);
        }

        .task-card.completed {
            background: #f0f8f5;
            opacity: 0.9;
        }

        .task-card.overdue {
            border-left: 4px solid var(--accent);
            background: #fff5f5;
        }

        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .task-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            word-break: break-word;
            flex: 1;
        }

        .task-card-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .status-active {
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
        }

        .status-pending {
            background: rgba(173, 181, 189, 0.1);
            color: #6c757d;
        }

        .task-card-meta {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
            font-size: 0.85rem;
            color: var(--gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .task-card-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: var(--space-md);
            flex: 1;
        }

        .task-card-progress {
            margin-bottom: var(--space-md);
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .progress-bar-wrapper {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .task-card-alerts {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
        }

        .alert-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .alert-overdue {
            background: rgba(212, 47, 19, 0.1);
            color: var(--accent);
        }

        .alert-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .task-card-actions {
            display: flex;
            gap: var(--space-sm);
            margin-top: auto;
        }

        .btn-update-progress {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            height: 36px;
            box-shadow: 0 2px 6px rgba(10, 99, 165, 0.25);
            white-space: nowrap;
            flex: 1;
        }

        .btn-update-progress:hover {
            background: #0a5a8c;
            box-shadow: 0 4px 12px rgba(10, 99, 165, 0.35);
            transform: translateY(-1px);
        }

        .btn-update-progress:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(10, 99, 165, 0.25);
        }

        .btn-update-progress:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .btn-update-progress i {
            font-size: 14px;
        }

        .task-completed-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(46, 204, 113, 0.2);
            flex: 1;
            text-align: center;
        }

        .task-completed-badge i {
            font-size: 14px;
        }

        /* Progress Modal Styling */
        .progress-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .progress-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--space-2xl);
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
        }

        .modal-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: var(--space-lg);
        }

        .modal-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: flex-end;
            margin-top: var(--space-lg);
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            min-width: 120px;
        }

        .btn-modal-cancel {
            background: #e0e0e0;
            color: var(--dark);
        }

        .btn-modal-cancel:hover {
            background: #d0d0d0;
        }

        .btn-modal-confirm {
            background: var(--primary);
            color: white;
            box-shadow: 0 2px 6px rgba(10, 99, 165, 0.25);
        }

        .btn-modal-confirm:hover {
            background: #0a5a8c;
            box-shadow: 0 4px 12px rgba(10, 99, 165, 0.35);
        }

        /* Progress Tracker Styles */
        .progress-tracker {
            margin: var(--space-lg) 0;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: var(--space-md);
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background: #e0e0e0;
            transform: translateY(-50%);
            z-index: 1;
        }

        .progress-bar-active {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            background: var(--primary);
            transform: translateY(-50%);
            z-index: 2;
            transition: width 0.3s ease;
        }

        .progress-step {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
        }

        .step-indicator {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            color: #999;
            transition: all 0.3s ease;
        }

        .step-label {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            color: #999;
            text-align: center;
            transition: color 0.3s ease;
        }

        .progress-step.active .step-indicator {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .progress-step.active .step-label {
            color: var(--primary);
        }

        .progress-step.completed .step-indicator {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .progress-step.completed .step-label {
            color: var(--success);
        }

        .progress-step.completed .step-indicator::after {
            content: '✓';
        }

        .current-progress-display {
            text-align: center;
            margin-top: var(--space-md);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }

        .progress-slider-container {
            margin: var(--space-lg) 0;
        }

        .progress-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e0e0e0;
            outline: none;
            -webkit-appearance: none;
        }

        .progress-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .progress-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Improved accessibility and responsive design */
        @media (max-width: 768px) {
            .timeline-container {
                padding: var(--space-lg);
            }

            /* Responsive card grid for mobile */
            .tasks-grid {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }

            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .filter-btn {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                padding: var(--space-lg);
            }

            .progress-steps {
                padding: 0 10px;
            }

            .step-label {
                font-size: 10px;
            }
        }

        /* Keyboard focus indicators for accessibility */
        .filter-btn:focus,
        .btn-update-progress:focus,
        .progress-step:focus .step-indicator {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Reduced motion support for accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="sidebar-main-layout">

    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Worker Panel</div>
            <a href="dashboard_worker.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="tasks_worker.php" class="nav-item active">
                <i class="fas fa-tasks"></i> My Tasks
            </a>
            <a href="projects_worker.php" class="nav-item">
                <i class="fas fa-project-diagram"></i> My Projects
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'W', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Worker'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
 
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">My Tasks</h1>
                <p class="page-description">View and update progress on your assigned tasks</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard_worker.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-tasks"></i></div>
                <div class="stat-value"><?php echo number_format($totalTasks); ?></div>
                <div class="stat-label">TOTAL TASKS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($completedTasks); ?></div>
                <div class="stat-label">COMPLETED</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-secondary"><i class="fas fa-spinner"></i></div>
                <div class="stat-value"><?php echo number_format($inProgressTasks); ?></div>
                <div class="stat-label">IN PROGRESS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-value"><?php echo number_format($overdueTasks); ?></div>
                <div class="stat-label">OVERDUE</div>
            </div>
        </div>

        <!-- Card grid layout -->
        <div class="tasks-grid" id="tasksTimeline">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <?php 
                    // Determine task status for card
                    $progress = (int)$task['progress'];
                    $isCompleted = $progress == 100;
                    $isActive = $progress > 0 && $progress < 100;
                    $isPending = $progress == 0;
                    
                    // Check if overdue
                    $isOverdue = false;
                    if (!empty($task['due_date']) && strtotime($task['due_date']) < time() && $progress < 100) {
                        $isOverdue = true;
                    }
                    
                    $statusClass = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ($isActive ? 'active' : 'pending'));
                    $filterClass = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ($isActive ? 'active' : 'pending'));
                    $statusBadgeClass = $isCompleted ? 'status-completed' : ($isActive ? 'status-active' : 'status-pending');
                    $statusText = $isCompleted ? 'Completed' : ($isActive ? 'In Progress' : 'Pending');
                    
                    // Format time display
                    $timeDisplay = '';
                    if (!empty($task['due_date'])) {
                        $dueDate = strtotime($task['due_date']);
                        $today = strtotime('today');
                        $daysUntilDue = floor(($dueDate - $today) / 86400);
                        
                        if ($isCompleted) {
                            $timeDisplay = 'Completed - ' . date('M j, Y', $dueDate);
                        } elseif ($isOverdue) {
                            $timeDisplay = 'Overdue - ' . date('M j, Y', $dueDate);
                        } elseif ($daysUntilDue == 0) {
                            $timeDisplay = 'Due Today';
                        } else {
                            $timeDisplay = 'Due ' . date('M j, Y', $dueDate);
                        }
                    } else {
                        $timeDisplay = 'No due date';
                    }
                    
                    $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                    ?>
                    <div class="task-card <?php echo $statusClass; ?>" data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'])); ?>" data-filter="<?php echo $filterClass; ?>">
                        <div class="task-card-header">
                            <div class="task-card-title"><?php echo htmlspecialchars($task['title']); ?></div>
                            <span class="task-card-status-badge <?php echo $statusBadgeClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="task-card-meta">
                            <div class="meta-item">
                                <i class="fas fa-folder"></i>
                                <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $timeDisplay; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($task['description'])): ?>
                        <div class="task-card-description">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?><?php echo strlen($task['description']) > 100 ? '...' : ''; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="task-card-alerts">
                            <?php if ($isOverdue): ?>
                            <div class="alert-badge alert-overdue">
                                <i class="fas fa-clock"></i>
                                Overdue
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($isCompleted && !empty($task['completed_at'])): ?>
                            <div class="alert-badge alert-completed">
                                <i class="fas fa-check-circle"></i>
                                Completed on <?php echo date('M j, Y', strtotime($task['completed_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-card-progress">
                            <div class="progress-label">
                                <span>Progress</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;"></div>
                            </div>
                        </div>
                        
                        <div class="task-card-actions">
                            <?php if (!$isCompleted): ?>
                            <button type="button" class="btn-update-progress" data-task-id="<?php echo $task['id']; ?>" data-current-progress="<?php echo $progress; ?>">
                                <i class="fas fa-sync-alt"></i> Update Progress
                            </button>
                            <?php else: ?>
                            <div class="task-completed-badge">
                                <i class="fas fa-check-circle"></i> Completed
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <p>You have no tasks assigned yet</p>
                <a href="dashboard_worker.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Progress Update Modal -->
    <div class="progress-modal" id="progressModal">
        <div class="modal-content">
            <div class="modal-title">
                <i class="fas fa-sync-alt" style="color: var(--primary); margin-right: 8px;"></i>
                Update Task Progress
            </div>
            <div class="modal-description" id="modalTaskTitle">
                <!-- Task title will be inserted here -->
            </div>
            
            <div class="progress-tracker">
                <div class="progress-steps">
                    <div class="progress-bar-active" id="progressBarActive"></div>
                    <div class="progress-step" data-progress="0">
                        <div class="step-indicator">0%</div>
                        <div class="step-label">Not Started</div>
                    </div>
                    <div class="progress-step" data-progress="25">
                        <div class="step-indicator">25%</div>
                        <div class="step-label">Started</div>
                    </div>
                    <div class="progress-step" data-progress="50">
                        <div class="step-indicator">50%</div>
                        <div class="step-label">Halfway</div>
                    </div>
                    <div class="progress-step" data-progress="75">
                        <div class="step-indicator">75%</div>
                        <div class="step-label">Almost Done</div>
                    </div>
                    <div class="progress-step" data-progress="100">
                        <div class="step-indicator">100%</div>
                        <div class="step-label">Complete</div>
                    </div>
                </div>
                
                <div class="current-progress-display" id="currentProgressDisplay">
                    Current Progress: 0%
                </div>
                
                <div class="progress-slider-container">
                    <input type="range" min="0" max="100" step="5" value="0" class="progress-slider" id="progressSlider">
                    <div class="slider-labels">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>
            
            <form method="post" id="progressForm">
                <input type="hidden" name="task_id" id="taskIdInput">
                <input type="hidden" name="progress" id="progressInput" value="0">
            </form>
            
            <div class="modal-actions">
                <button class="btn-modal btn-modal-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-modal btn-modal-confirm" id="confirmBtn">Update Progress</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Progress update modal functionality
        const progressModal = document.getElementById('progressModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const progressForm = document.getElementById('progressForm');
        const taskIdInput = document.getElementById('taskIdInput');
        const progressInput = document.getElementById('progressInput');
        const progressSlider = document.getElementById('progressSlider');
        const currentProgressDisplay = document.getElementById('currentProgressDisplay');
        const progressBarActive = document.getElementById('progressBarActive');
        const modalTaskTitle = document.getElementById('modalTaskTitle');
        
        let currentProgress = 0;
        
        // Update progress steps and slider
        function updateProgressUI(progress) {
            currentProgress = progress;
            progressInput.value = progress;
            progressSlider.value = progress;
            currentProgressDisplay.textContent = `Current Progress: ${progress}%`;
            
            // Update progress bar
            progressBarActive.style.width = `${progress}%`;
            
            // Update step indicators
            document.querySelectorAll('.progress-step').forEach(step => {
                const stepProgress = parseInt(step.getAttribute('data-progress'));
                
                // Remove all classes
                step.classList.remove('active', 'completed');
                
                // Add appropriate classes
                if (stepProgress <= progress) {
                    step.classList.add('completed');
                }
                
                if (stepProgress === progress) {
                    step.classList.add('active');
                }
            });
        }
        
        // Open modal when update progress button is clicked
        document.querySelectorAll('.btn-update-progress').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-task-id');
                const currentProgress = parseInt(this.getAttribute('data-current-progress'));
                const taskTitle = this.closest('.task-card').querySelector('.task-card-title').textContent;
                
                // Set modal content
                taskIdInput.value = taskId;
                modalTaskTitle.textContent = `Update progress for: "${taskTitle}"`;
                
                // Initialize progress UI
                updateProgressUI(currentProgress);
                
                // Show modal
                progressModal.classList.add('active');
            });
        });
        
        // Progress step click handler
        document.querySelectorAll('.progress-step').forEach(step => {
            step.addEventListener('click', function() {
                const progress = parseInt(this.getAttribute('data-progress'));
                updateProgressUI(progress);
            });
        });
        
        // Slider change handler
        progressSlider.addEventListener('input', function() {
            updateProgressUI(parseInt(this.value));
        });
        
        // Cancel button handler
        cancelBtn.addEventListener('click', function() {
            progressModal.classList.remove('active');
        });
        
        // Confirm button handler
        confirmBtn.addEventListener('click', function() {
            progressForm.submit();
        });
        
        // Close modal when clicking outside
        progressModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
        
        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && progressModal.classList.contains('active')) {
                progressModal.classList.remove('active');
            }
        });

        // Filter and search functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const timelineItems = document.querySelectorAll('.task-card');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active state
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');

                // Filter items
                timelineItems.forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-filter') === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tasksGrid = document.getElementById('tasksTimeline');

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');

            tasksGrid.querySelectorAll('.task-card').forEach(card => {
                const searchData = card.getAttribute('data-search');
                const itemFilter = card.getAttribute('data-filter');
                
                const matchesSearch = !searchTerm || searchData.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || itemFilter === activeFilter;
                
                card.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);
    </script>
</body>
</html>