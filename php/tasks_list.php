<?php
require_once("auth_check.php");
requireRole(["pm", "admin"]);
require_once("../config/db.php");

// Fetch all tasks with project and worker information
$stmt = $pdo->query("
    SELECT t.*, p.name AS project_name, u.name AS worker_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN users u ON t.assigned_to = u.id
    ORDER BY t.created_at DESC
");
$tasks = $stmt->fetchAll();

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
        .tasks-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
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
        }

        .btn-icon:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users_list.php" class="nav-item">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="proposals_review.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Proposals
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
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>


    <div class="main-content">
         Page Header 
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Tasks Management</h1>
                <p class="page-description">View and manage all project tasks across your organization</p>
            </div>
            <div class="d-flex gap-2">
                <a href="tasks_create.php" class="btn btn-primary">
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

 
        <div class="tasks-table-container">
            <div class="table-header">
                <div class="search-filter-group">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search tasks, projects, or workers...">
                    </div>
                    <select class="filter-select" id="projectFilter">
                        <option value="">All Projects</option>
                        <?php
                        $projects = $pdo->query("SELECT DISTINCT p.id, p.name FROM projects p JOIN tasks t ON p.id = t.project_id ORDER BY p.name")->fetchAll();
                        foreach ($projects as $project):
                        ?>
                            <option value="<?php echo htmlspecialchars($project['name']); ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" id="progressFilter">
                        <option value="">All Progress</option>
                        <option value="0">Not Started</option>
                        <option value="1-99">In Progress</option>
                        <option value="100">Completed</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($tasks)): ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Project</th>
                        <th>Assigned To</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody">
                    <?php foreach ($tasks as $task): ?>
                    <tr data-project="<?php echo htmlspecialchars($task['project_name']); ?>" 
                        data-progress="<?php echo (int)$task['progress']; ?>"
                        data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'] . ' ' . $task['worker_name'])); ?>">
                        <td class="task-title-cell">
                            <div><?php echo htmlspecialchars($task['title']); ?></div>
                            <?php if (!empty($task['description'])): ?>
                            <div class="task-description"><?php echo htmlspecialchars($task['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="project-badge">
                                <i class="fas fa-folder"></i>
                                <?php echo htmlspecialchars($task['project_name']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="worker-info">
                                <div class="worker-avatar">
                                    <?php echo strtoupper(substr($task['worker_name'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($task['worker_name']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar-wrapper">
                                    <?php 
                                    $progress = (int)$task['progress'];
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
                                
                                if ($task['progress'] < 100) {
                                    if ($daysUntilDue < 0) {
                                        $dateClass = 'due-date-overdue';
                                        $dateIcon = 'fa-exclamation-triangle';
                                    } elseif ($daysUntilDue <= 3) {
                                        $dateClass = 'due-date-soon';
                                        $dateIcon = 'fa-clock';
                                    }
                                }
                                
                                echo '<div class="due-date-cell ' . $dateClass . '">';
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
                                <button class="btn-icon" title="View Details" onclick="viewTask(<?php echo $task['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon" title="Edit Task" onclick="editTask(<?php echo $task['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const projectFilter = document.getElementById('projectFilter');
        const progressFilter = document.getElementById('progressFilter');
        const tableBody = document.getElementById('tasksTableBody');
        const rows = tableBody ? tableBody.getElementsByTagName('tr') : [];

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedProject = projectFilter.value;
            const selectedProgress = progressFilter.value;

            for (let row of rows) {
                const searchData = row.getAttribute('data-search');
                const projectData = row.getAttribute('data-project');
                const progressData = parseInt(row.getAttribute('data-progress'));

                let showRow = true;

                // Search filter
                if (searchTerm && !searchData.includes(searchTerm)) {
                    showRow = false;
                }

                // Project filter
                if (selectedProject && projectData !== selectedProject) {
                    showRow = false;
                }

                // Progress filter
                if (selectedProgress) {
                    if (selectedProgress === '0' && progressData !== 0) {
                        showRow = false;
                    } else if (selectedProgress === '1-99' && (progressData === 0 || progressData === 100)) {
                        showRow = false;
                    } else if (selectedProgress === '100' && progressData !== 100) {
                        showRow = false;
                    }
                }

                row.style.display = showRow ? '' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);
        if (projectFilter) projectFilter.addEventListener('change', filterTasks);
        if (progressFilter) progressFilter.addEventListener('change', filterTasks);

        function viewTask(taskId) {
            window.location.href = 'tasks_details.php?id=' + taskId;
        }

        function editTask(taskId) {
            window.location.href = 'tasks_edit.php?id=' + taskId;
        }
    </script>
</body>
</html>
