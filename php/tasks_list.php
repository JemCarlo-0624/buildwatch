<?php
require_once("auth_check.php");
requireRole(["pm", "admin"]);
require_once("../config/db.php");

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$project_name = null;

// Fetch tasks - filter by project_id if provided
if ($project_id) {
    $stmt = $pdo->prepare("
        SELECT t.*, p.name AS project_name, u.name AS worker_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON t.assigned_to = u.id
        WHERE t.project_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$project_id]);
    
    // Get project name for display
    $project_stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch();
    $project_name = $project ? $project['name'] : null;
} else {
    // Fetch all tasks if no project_id specified
    $stmt = $pdo->query("
        SELECT t.*, p.name AS project_name, u.name AS worker_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        JOIN users u ON t.assigned_to = u.id
        ORDER BY p.name, t.created_at DESC
    ");
    $stmt->execute();
}

$tasks = $stmt->fetchAll();

$tasksByProject = [];
foreach ($tasks as $task) {
    $projId = $task['project_id'];
    if (!isset($tasksByProject[$projId])) {
        $tasksByProject[$projId] = [
            'name' => $task['project_name'],
            'id' => $projId,
            'tasks' => []
        ];
    }
    $tasksByProject[$projId]['tasks'][] = $task;
}

// Calculate statistics
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Management - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Improved HCI design with better visual hierarchy and accessibility */
        .tasks-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        /* Added project section styling */
        .project-section {
            margin-bottom: 30px;
        }

        .project-header {
            background: linear-gradient(135deg, var(--primary) 0%, rgba(10, 99, 165, 0.8) 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .project-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .project-task-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Added quick filter buttons for better task filtering */
        .quick-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .filter-btn {
            padding: 8px 14px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(10, 99, 165, 0.05);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .search-filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
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

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
            transition: border-color 0.2s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 99, 165, 0.1);
        }

        .tasks-table {
            width: 100%;
            margin: 0;
        }

        .tasks-table thead {
            background: #f8f9fa;
        }

        .tasks-table th {
            padding: 15px 20px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark);
            border-bottom: 2px solid #e9ecef;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s ease;
        }

        .tasks-table th:hover {
            background: #f0f1f3;
        }

        .tasks-table td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .tasks-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .tasks-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Added focus state for keyboard navigation */
        .tasks-table tbody tr:focus-within {
            outline: 2px solid var(--primary);
            outline-offset: -2px;
        }

        .task-title-cell {
            font-weight: 600;
            color: var(--dark);
            max-width: 300px;
        }

        .task-description {
            font-size: 13px;
            color: var(--gray);
            margin-top: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Added status badge for better visual feedback */
        .task-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.15);
            color: var(--success);
        }

        .status-in-progress {
            background: rgba(10, 99, 165, 0.15);
            color: var(--primary);
        }

        .status-not-started {
            background: rgba(149, 165, 166, 0.15);
            color: var(--gray);
        }

        .status-overdue {
            background: rgba(212, 47, 19, 0.15);
            color: var(--accent);
        }

        .project-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .worker-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .worker-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar-wrapper {
            flex: 1;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            min-width: 80px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            min-width: 40px;
        }

        .due-date-cell {
            font-size: 13px;
        }

        .due-date-overdue {
            color: var(--accent);
            font-weight: 600;
        }

        .due-date-soon {
            color: var(--warning);
            font-weight: 600;
        }

        .due-date-normal {
            color: var(--gray);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: white;
            color: var(--gray);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-icon:hover:not(.disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-icon:focus:not(.disabled) {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Added disabled state styling for completed tasks */
        .btn-icon.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f0f0f0;
            color: #ccc;
            border-color: #ddd;
        }

        .btn-icon.disabled:hover {
            background: #f0f0f0;
            color: #ccc;
            border-color: #ddd;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Added empty project section styling */
        .project-section.hidden {
            display: none;
        }

        /* Improved accessibility with better focus states */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media (max-width: 768px) {
            .tasks-table-container {
                overflow-x: auto;
            }

            .search-filter-group {
                width: 100%;
            }

            .search-box {
                max-width: 100%;
            }

            .quick-filters {
                width: 100%;
            }

            .filter-btn {
                flex: 1;
                min-width: 80px;
            }

            .project-header {
                flex-direction: column;
                align-items: flex-start;
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
            <div class="nav-section-title"><?php echo $_SESSION['role'] === 'admin' ? 'Admin Panel' : 'PM Panel'; ?></div>
            <a href="<?php echo $_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_pm.php'; ?>" class="nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="projects_list.php" class="nav-item">
                <i class="fas fa-project-diagram"></i> Projects
            </a>
            <a href="tasks_list.php" class="nav-item active">
                <i class="fas fa-tasks"></i> Tasks
            </a>
            <a href="proposals_review.php" class="nav-item">
            <i class="fas fa-lightbulb"></i> Proposals
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users_list.php" class="nav-item">
                <i class="fas fa-users"></i> Users
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <small class="text-white-50"><?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'User')); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">
                    <?php 
                    if ($project_id && $project_name) {
                        echo htmlspecialchars($project_name) . ' - Tasks';
                    } else {
                        echo 'Tasks Management';
                    }
                    ?>
                </h1>
                <p class="page-description">
                    <?php 
                    if ($project_id && $project_name) {
                        echo 'View and manage tasks for ' . htmlspecialchars($project_name);
                    } else {
                        echo 'View and manage all project tasks across your organization';
                    }
                    ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php 
                if ($project_id && $project_name):
                ?>
                <a href="projects_details.php?id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Project
                </a>
                <?php endif; ?>
                <a href="tasks_create.php<?php echo $project_id ? '?project_id=' . $project_id : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Task
                </a>
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-download"></i> Export
                </button>
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

        <div style="background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">
            <div class="quick-filters">
                <button class="filter-btn active" data-filter="all" aria-label="Show all tasks">
                    <i class="fas fa-list"></i> All
                </button>
                <button class="filter-btn" data-filter="completed" aria-label="Show completed tasks">
                    <i class="fas fa-check-circle"></i> Completed
                </button>
                <button class="filter-btn" data-filter="in-progress" aria-label="Show in-progress tasks">
                    <i class="fas fa-spinner"></i> In Progress
                </button>
                <button class="filter-btn" data-filter="not-started" aria-label="Show not started tasks">
                    <i class="fas fa-circle"></i> Not Started
                </button>
                <button class="filter-btn" data-filter="overdue" aria-label="Show overdue tasks">
                    <i class="fas fa-exclamation-circle"></i> Overdue
                </button>
            </div>

            <div class="search-filter-group">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search tasks, projects, or workers..." aria-label="Search tasks">
                </div>
            </div>
        </div>

        <?php if (!empty($tasksByProject)): ?>
            <?php foreach ($tasksByProject as $projId => $projectData): ?>
            <div class="project-section" data-project-id="<?php echo $projId; ?>">
                <div class="project-header">
                    <h3>
                        <i class="fas fa-folder"></i>
                        <?php echo htmlspecialchars($projectData['name']); ?>
                    </h3>
                    <span class="project-task-count">
                        <?php echo count($projectData['tasks']); ?> task<?php echo count($projectData['tasks']) !== 1 ? 's' : ''; ?>
                    </span>
                </div>

                <div class="tasks-table-container" style="border-radius: 0 0 12px 12px; box-shadow: none; border-top: 1px solid #f0f0f0;">
                    <table class="tasks-table" role="table">
                        <thead>
                            <tr>
                                <th role="columnheader">Task</th>
                                <th role="columnheader">Assigned To</th>
                                <th role="columnheader">Status</th>
                                <th role="columnheader">Progress</th>
                                <th role="columnheader">Due Date</th>
                                <th role="columnheader">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="project-tasks-body">
                            <?php foreach ($projectData['tasks'] as $task): ?>
                            <?php 
                            $progress = (int)$task['progress'];
                            $isCompleted = $progress == 100;
                            $isInProgress = $progress > 0 && $progress < 100;
                            $isNotStarted = $progress == 0;
                            
                            $isOverdue = false;
                            if (!empty($task['due_date']) && strtotime($task['due_date']) < time() && !$isCompleted) {
                                $isOverdue = true;
                            }
                            
                            $statusClass = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ($isInProgress ? 'in-progress' : 'not-started'));
                            $statusText = $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : ($isInProgress ? 'In Progress' : 'Not Started'));
                            $statusIcon = $isCompleted ? 'fa-check-circle' : ($isOverdue ? 'fa-exclamation-circle' : ($isInProgress ? 'fa-spinner' : 'fa-circle'));
                            ?>
                            <tr data-project="<?php echo htmlspecialchars($task['project_name']); ?>" 
                                data-progress="<?php echo $progress; ?>"
                                data-status="<?php echo $statusClass; ?>"
                                data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'] . ' ' . $task['worker_name'])); ?>"
                                tabindex="0">
                                <td class="task-title-cell">
                                    <div><?php echo htmlspecialchars($task['title']); ?></div>
                                    <?php if (!empty($task['description'])): ?>
                                    <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="worker-info">
                                        <div class="worker-avatar" title="<?php echo htmlspecialchars($task['worker_name']); ?>">
                                            <?php echo strtoupper(substr($task['worker_name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($task['worker_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="task-status-badge status-<?php echo $statusClass; ?>" title="<?php echo $statusText; ?>">
                                        <i class="fas <?php echo $statusIcon; ?>"></i>
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar-wrapper">
                                            <?php 
                                            $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                                            ?>
                                            <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $progress; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($task['due_date'])) {
                                        $dueDate = strtotime($task['due_date']);
                                        $today = strtotime('today');
                                        $daysUntilDue = floor(($dueDate - $today) / 86400);
                                        
                                        $dateClass = 'due-date-normal';
                                        $dateIcon = 'fa-calendar';
                                        
                                        if (!$isCompleted) {
                                            if ($daysUntilDue < 0) {
                                                $dateClass = 'due-date-overdue';
                                                $dateIcon = 'fa-exclamation-triangle';
                                            } elseif ($daysUntilDue <= 3) {
                                                $dateClass = 'due-date-soon';
                                                $dateIcon = 'fa-clock';
                                            }
                                        }
                                        
                                        echo '<div class="due-date-cell ' . $dateClass . '" title="Due: ' . date('M j, Y', $dueDate) . '">';
                                        echo '<i class="fas ' . $dateIcon . '"></i> ';
                                        echo date('M j, Y', $dueDate);
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-muted">No due date</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" title="View Details" onclick="viewTask(<?php echo $task['id']; ?>)" aria-label="View task details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon <?php echo $isCompleted ? 'disabled' : ''; ?>" 
                                                title="<?php echo $isCompleted ? 'Cannot edit completed tasks' : 'Edit Task'; ?>" 
                                                onclick="<?php echo !$isCompleted ? 'editTask(' . $task['id'] . ')' : 'return false;'; ?>" 
                                                aria-label="<?php echo $isCompleted ? 'Cannot edit completed tasks' : 'Edit task'; ?>"
                                                <?php echo $isCompleted ? 'disabled' : ''; ?>>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-tasks"></i>
            <h3>No Tasks Found</h3>
            <p>Get started by creating your first task</p>
            <a href="tasks_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Task
            </a>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const projectSections = document.querySelectorAll('.project-section');

        let activeStatusFilter = 'all';

        // Quick filter button functionality
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeStatusFilter = this.getAttribute('data-filter');
                filterTasks();
            });
        });

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();

            projectSections.forEach(section => {
                const rows = section.querySelectorAll('.project-tasks-body tr');
                let visibleRowCount = 0;

                rows.forEach(row => {
                    const searchData = row.getAttribute('data-search');
                    const statusData = row.getAttribute('data-status');

                    let showRow = true;

                    // Search filter
                    if (searchTerm && !searchData.includes(searchTerm)) {
                        showRow = false;
                    }

                    // Status filter
                    if (activeStatusFilter !== 'all' && statusData !== activeStatusFilter) {
                        showRow = false;
                    }

                    row.style.display = showRow ? '' : 'none';
                    if (showRow) visibleRowCount++;
                });

                // Hide project section if no tasks match
                section.style.display = visibleRowCount > 0 ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                activeStatusFilter = 'all';
                filterButtons.forEach(b => b.classList.remove('active'));
                filterButtons[0].classList.add('active');
                filterTasks();
            }
        });

        function viewTask(taskId) {
            window.location.href = 'tasks_details.php?id=' + taskId;
        }

        function editTask(taskId) {
            window.location.href = 'tasks_edit.php?id=' + taskId;
        }
    </script>
</body>
</html>
