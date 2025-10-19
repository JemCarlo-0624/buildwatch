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
        
        /* Timeline-based task layout with improved HCI principles */
        .timeline-container {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            padding: var(--space-2xl);
        }

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

        .timeline {
            position: relative;
            margin-left: 25px;
            border-left: 3px solid #e0e0e0;
            padding-left: 20px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding: var(--space-lg);
            background: #f8f9fa;
            border-radius: var(--radius-md);
            transition: all var(--transition-normal);
            border: 1px solid transparent;
        }

        .timeline-item:hover {
            background: #f0f1f3;
            transform: translateX(5px);
            border-color: #ddd;
        }

        .timeline-item.completed {
            opacity: 0.85;
            background: #f0f8f5;
        }

        .timeline-item.completed:hover {
            transform: none;
        }

        /* Enhanced visual urgency for overdue tasks */
        .timeline-item.overdue {
            background: #fff5f5;
            border-left: 4px solid var(--accent);
        }

        .timeline-item.overdue:hover {
            background: #ffe8e8;
        }

        /* Timeline dot indicators */
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -12px;
            top: 20px;
            width: 14px;
            height: 14px;
            background-color: #ccc;
            border-radius: 50%;
            border: 3px solid #fff;
            transition: 0.3s ease;
            box-shadow: 0 0 0 2px white;
        }

        /* Completed task - green dot */
        .timeline-item.completed::before {
            background-color: var(--success);
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.4);
        }

        /* Active/In Progress task - blue dot */
        .timeline-item.active::before {
            background-color: var(--primary);
            box-shadow: 0 0 8px rgba(10, 99, 165, 0.6);
        }

        /* Pending/Not started task - gray dot */
        .timeline-item.pending::before {
            background-color: #adb5bd;
            box-shadow: 0 0 0 2px white;
        }

        /* Overdue task - red dot for high visibility */
        .timeline-item.overdue::before {
            background-color: var(--accent);
            box-shadow: 0 0 8px rgba(212, 47, 19, 0.6);
        }

        .timeline-time {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 8px;
            font-weight: 500;
        }

        /* Improved overdue indicator with better visibility */
        .timeline-overdue-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: rgba(212, 47, 19, 0.1);
            color: var(--accent);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .timeline-completed-at {
            font-size: 0.8rem;
            color: var(--success);
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timeline-completed-at i {
            color: var(--success);
        }

        .timeline-status {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            word-break: break-word;
        }

        .timeline-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
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

        .timeline-desc {
            margin-top: 8px;
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .timeline-meta {
            display: flex;
            gap: 15px;
            margin-top: 12px;
            flex-wrap: wrap;
            font-size: 0.85rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
        }

        .meta-item i {
            color: var(--primary);
        }

        .timeline-progress {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar-wrapper {
            flex: 1;
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

        .progress-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark);
            min-width: 40px;
        }

        .timeline-actions {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-update-form {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .progress-input {
            height: 8px;
            -webkit-appearance: none;
            appearance: none;
            background: linear-gradient(to right, #e0e0e0 0%, #e0e0e0 100%);
            border-radius: 4px;
            outline: none;
            cursor: pointer;
            flex: 1;
            min-width: 100px;
            transition: background 0.2s;
        }

        .progress-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(10, 99, 165, 0.3);
        }

        .progress-input::-webkit-slider-thumb:hover {
            background: #084a7d;
            box-shadow: 0 4px 8px rgba(10, 99, 165, 0.4);
            transform: scale(1.1);
        }

        .progress-input::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: var(--primary);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(10, 99, 165, 0.3);
        }

        .progress-input::-moz-range-thumb:hover {
            background: #084a7d;
            box-shadow: 0 4px 8px rgba(10, 99, 165, 0.4);
            transform: scale(1.1);
        }

        .progress-input:disabled {
            background: #e0e0e0;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .progress-input:disabled::-webkit-slider-thumb {
            background: #adb5bd;
            cursor: not-allowed;
            box-shadow: none;
        }

        .progress-input:disabled::-moz-range-thumb {
            background: #adb5bd;
            cursor: not-allowed;
            box-shadow: none;
        }

        .progress-value-display {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            min-width: 45px;
            text-align: center;
            background: rgba(10, 99, 165, 0.08);
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(10, 99, 165, 0.15);
            white-space: nowrap;
        }

        /* Compact button styling for single-line layout */
        .btn-update {
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
            min-width: 100px;
            height: 36px;
            box-shadow: 0 2px 6px rgba(10, 99, 165, 0.25);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-update:hover:not(:disabled) {
            background: #084a7d;
            box-shadow: 0 4px 12px rgba(10, 99, 165, 0.35);
            transform: translateY(-1px);
        }

        .btn-update:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(10, 99, 165, 0.25);
        }

        .btn-update:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            opacity: 0.65;
            box-shadow: none;
        }

        .btn-update:disabled:hover {
            background: #adb5bd;
            transform: none;
            box-shadow: none;
        }

        .btn-update:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .btn-update i {
            font-size: 14px;
        }

        /* Completion confirmation modal for error prevention */
        .completion-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .completion-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--space-2xl);
            max-width: 400px;
            box-shadow: var(--shadow-xl);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: var(--space-md);
        }

        .modal-description {
            color: var(--gray);
            margin-bottom: var(--space-lg);
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-normal);
        }

        .btn-modal-cancel {
            background: #e0e0e0;
            color: var(--dark);
        }

        .btn-modal-cancel:hover {
            background: #d0d0d0;
        }

        .btn-modal-confirm {
            background: var(--success);
            color: white;
        }

        .btn-modal-confirm:hover {
            background: #27ae60;
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

            .timeline {
                margin-left: 15px;
                padding-left: 15px;
            }

            .timeline-item::before {
                left: -8px;
            }

            /* Stack actions vertically on mobile */
            .timeline-actions {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .progress-update-form {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .btn-update {
                width: 100%;
                min-width: unset;
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
        }

        /* Keyboard focus indicators for accessibility */
        .filter-btn:focus,
        .progress-input:focus,
        .btn-update:focus {
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

        <!-- Timeline-based task display -->
        <div class="timeline-container">
            <!-- Filter controls for better task scanning and cognitive load reduction -->
            <div class="filter-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search tasks..." aria-label="Search tasks">
                </div>
                <button class="filter-btn active" data-filter="all" aria-pressed="true">All Tasks</button>
                <button class="filter-btn" data-filter="pending" aria-pressed="false">Pending</button>
                <button class="filter-btn" data-filter="active" aria-pressed="false">In Progress</button>
                <button class="filter-btn" data-filter="completed" aria-pressed="false">Completed</button>
                <?php if ($overdueTasks > 0): ?>
                <button class="filter-btn" data-filter="overdue" aria-pressed="false">Overdue</button>
                <?php endif; ?>
            </div>

            <?php if (!empty($tasks)): ?>
            <div class="timeline" id="tasksTimeline">
                <?php foreach ($tasks as $task): ?>
                    <?php 
                    // Determine task status for timeline
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
                    <div class="timeline-item <?php echo $statusClass; ?>" data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'])); ?>" data-filter="<?php echo $filterClass; ?>">
                        <div class="timeline-time"><?php echo $timeDisplay; ?></div>
                        
                        <!-- Enhanced overdue indicator for better visibility -->
                        <?php if ($isOverdue): ?>
                        <div class="timeline-overdue-badge">
                            <i class="fas fa-clock"></i>
                            Overdue
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($isCompleted && !empty($task['completed_at'])): ?>
                        <div class="timeline-completed-at">
                            <i class="fas fa-check-circle"></i>
                            Completed on <?php echo date('M j, Y \a\t g:i A', strtotime($task['completed_at'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-status">
                            <?php echo htmlspecialchars($task['title']); ?>
                            <span class="timeline-status-badge <?php echo $statusBadgeClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($task['description'])): ?>
                        <div class="timeline-desc">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 150)); ?><?php echo strlen($task['description']) > 150 ? '...' : ''; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-meta">
                            <div class="meta-item">
                                <i class="fas fa-folder"></i>
                                <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-chart-pie"></i>
                                <span><?php echo $progress; ?>% Complete</span>
                            </div>
                        </div>
                        
                        <div class="timeline-actions">
                            <form method="post" class="progress-update-form">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input 
                                    type="range" 
                                    name="progress" 
                                    value="<?php echo $progress; ?>" 
                                    min="0" 
                                    max="100" 
                                    step="5"
                                    class="progress-input" 
                                    data-task-id="<?php echo $task['id']; ?>"
                                    <?php echo $isCompleted ? 'disabled' : ''; ?>
                                    aria-label="Task progress slider"
                                    required
                                >
                                <span class="progress-value-display" id="progress-display-<?php echo $task['id']; ?>">
                                    <?php echo $progress; ?>%
                                </span>
                                <button type="submit" class="btn-update" <?php echo $isCompleted ? 'disabled' : ''; ?> aria-label="Update task progress">
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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

    <!-- Completion confirmation modal for error prevention -->
    <div class="completion-modal" id="completionModal">
        <div class="modal-content">
            <div class="modal-title">
                <i class="fas fa-check-circle" style="color: var(--success); margin-right: 8px;"></i>
                Mark Task as Complete?
            </div>
            <div class="modal-description">
                You're about to mark this task as 100% complete. This action will record a completion timestamp and the task will be locked from further edits.
            </div>
            <div class="modal-actions">
                <button class="btn-modal btn-modal-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-modal btn-modal-confirm" id="confirmBtn">Confirm Completion</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        // Update progress display when slider moves
        document.querySelectorAll('.progress-input').forEach(slider => {
            slider.addEventListener('input', function() {
                const taskId = this.getAttribute('data-task-id');
                const display = document.getElementById('progress-display-' + taskId);
                if (display) {
                    display.textContent = this.value + '%';
                }
            });
        });

        let pendingForm = null;
        const completionModal = document.getElementById('completionModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');

        document.querySelectorAll('.progress-update-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const progressInput = this.querySelector('.progress-input');
                const newProgress = parseInt(progressInput.value);
                const currentProgress = parseInt(progressInput.getAttribute('value'));

                // Show confirmation only when marking as complete (100%)
                if (newProgress === 100 && currentProgress !== 100) {
                    e.preventDefault();
                    pendingForm = this;
                    completionModal.classList.add('active');
                }
            });
        });

        cancelBtn.addEventListener('click', function() {
            completionModal.classList.remove('active');
            pendingForm = null;
        });

        confirmBtn.addEventListener('click', function() {
            if (pendingForm) {
                completionModal.classList.remove('active');
                pendingForm.submit();
            }
        });

        // Close modal when clicking outside
        completionModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                pendingForm = null;
            }
        });

        const filterBtns = document.querySelectorAll('.filter-btn');
        const timelineItems = document.querySelectorAll('.timeline-item');

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
        const timeline = document.getElementById('tasksTimeline');

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');

            timelineItems.forEach(item => {
                const searchData = item.getAttribute('data-search');
                const itemFilter = item.getAttribute('data-filter');
                
                const matchesSearch = !searchTerm || searchData.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || itemFilter === activeFilter;
                
                item.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && completionModal.classList.contains('active')) {
                completionModal.classList.remove('active');
                pendingForm = null;
            }
        });
    </script>
</body>
</html>
        