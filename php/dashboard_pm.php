<?php
require_once("auth_check.php");
requireRole("pm");
require_once("../config/db.php");

$user_id = $_SESSION['user_id'];

// Fetch projects assigned to this PM
$stmt = $pdo->prepare("SELECT p.* FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE pa.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();

// Fetch tasks for PM's projects
$stmtTasks = $pdo->prepare("
    SELECT t.*, p.name AS project_name, u.name AS assigned_name 
    FROM tasks t 
    JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE p.id IN (SELECT project_id FROM project_assignments WHERE user_id = ?)
    ORDER BY p.name, t.due_date ASC
");
$stmtTasks->execute([$user_id]);
$tasks = $stmtTasks->fetchAll();

// Organize tasks by project
$schedule = [];
foreach ($tasks as $t) {
    $schedule[$t['project_name']][] = $t;
}

// Calculate statistics
$totalProjects = count($projects);
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, function($task) { return $task['progress'] == 100; }));
$pendingTasks = $totalTasks - $completedTasks;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Manager Dashboard - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional dashboard-specific styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            background: white;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 0;
        }

        .project-item, .task-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 20px;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
        }

        .project-item:hover, .task-item:hover {
            background-color: #f8f9fa;
        }

        .project-item:last-child, .task-item:last-child {
            border-bottom: none;
        }

        .project-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .project-info, .task-info {
            flex: 1;
        }

        .project-item-title, .task-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .project-item-details {
            font-size: 13px;
            color: var(--gray);
            line-height: 1.4;
        }

        .task-progress {
            position: relative;
            background-color: #f0f0f0;
            height: 6px;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            right: 0;
            top: -20px;
            font-size: 12px;
            color: var(--gray);
            font-weight: 500;
        }

        .task-checkbox {
            margin-right: 15px;
            margin-top: 3px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d1ecf1; color: #0c5460; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-active { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-on-hold { background-color: #fff3cd; color: #856404; }
        .status-unknown { background-color: #e2e3e5; color: #495057; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }

        .text-center {
            text-align: center;
        }

        .p-20 {
            padding: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card-header {
                padding: 15px;
            }
            
            .project-item, .task-item {
                padding: 12px 15px;
            }
        }

        /* Stats container improvements */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .stat-icon-primary { background: rgba(10, 99, 165, 0.1); color: var(--primary); }
        .stat-icon-success { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .stat-icon-accent { background: rgba(212, 47, 19, 0.1); color: var(--accent); }
        .stat-icon-secondary { background: rgba(203, 149, 1, 0.1); color: var(--secondary); }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
            color: var(--dark);
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-change {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 500;
        }

        .positive { color: var(--success); }
        .negative { color: var(--accent); }
    </style>
</head>
<body class="sidebar-main-layout">

    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">PM Panel</div>
            <a href="dashboard_pm.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> My Projects</a>
            <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    PM
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Project Manager'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? 'pm@example.com'); ?></small>
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
                <h1 class="page-title">Project Manager Dashboard</h1>
                <p class="page-description">Welcome back! Manage your projects and team effectively.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard_pm.php" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</a>
                <a href="reports.php" class="btn btn-outline-primary"><i class="fas fa-download"></i> Export Report</a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-label">MY PROJECTS</div>
                <div class="stat-change positive">Active projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-secondary"><i class="fas fa-tasks"></i></div>
                <div class="stat-value"><?php echo number_format($totalTasks); ?></div>
                <div class="stat-label">TOTAL TASKS</div>
                <div class="stat-change positive">All tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($completedTasks); ?></div>
                <div class="stat-label">COMPLETED</div>
                <div class="stat-change positive">Finished tasks</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo number_format($pendingTasks); ?></div>
                <div class="stat-label">PENDING</div>
                <div class="stat-change negative">In progress</div>
            </div>
        </div>

        <div class="dashboard-grid">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open" style="color: var(--primary);"></i> My Projects</h3>
                    <a href="projects_list.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $p): ?>
                            <div class="project-item">
                                <div class="project-color" style="background-color: 
                                    <?php 
                                    switch(strtolower($p['status'])) {
                                        case 'active': echo 'var(--success)'; break;
                                        case 'completed': echo 'var(--primary)'; break;
                                        case 'on-hold': echo 'var(--warning)'; break;
                                        default: echo 'var(--accent)';
                                    }
                                    ?>">
                                </div>
                                <div class="project-info">
                                    <div class="project-item-title"><?php echo htmlspecialchars($p['name'] ?? 'Untitled Project'); ?></div>
                                    <div class="project-item-details">
                                        <span class="status-badge status-<?php echo strtolower($p['status'] ?? 'unknown'); ?>">
                                            <?php echo htmlspecialchars($p['status'] ?? 'Unknown'); ?>
                                        </span> • 
                                        Project ID: #<?php echo (int)$p['id']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-folder-open fs-1 text-muted mb-3"></i>
                            <p>No projects assigned yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt" style="color: var(--success);"></i> Task Overview</h3>
                    <a href="tasks_list.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($schedule)): ?>
                        <?php foreach ($schedule as $projectName => $projectTasks): ?>
                            <?php foreach (array_slice($projectTasks, 0, 5) as $task): ?>
                                <div class="task-item">
                                    <input type="checkbox" class="task-checkbox form-check-input" <?php echo ($task['progress'] ?? 0) == 100 ? 'checked' : ''; ?> disabled>
                                    <div class="task-info">
                                        <div class="task-title"><?php echo htmlspecialchars($task['title'] ?? 'Untitled Task'); ?></div>
                                        <div class="project-item-details">
                                            <?php echo htmlspecialchars($projectName); ?> • 
                                            Assigned to: <?php echo htmlspecialchars($task['assigned_name'] ?? 'Unassigned'); ?>
                                        </div>
                                        <div class="task-progress">
                                            <?php 
                                            $progress = $task['progress'] ?? 0;
                                            $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                                            ?>
                                            <div class="progress-bar" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;"></div>
                                            <span class="progress-text"><?php echo $progress; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-calendar-alt fs-1 text-muted mb-3"></i>
                            <p>No tasks found for your projects</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle task checkbox interactions
        const checkboxes = document.querySelectorAll('.task-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const taskItem = this.closest('.task-item');
                if (taskItem) {
                    taskItem.style.opacity = this.checked ? '0.7' : '1';
                }
            });
        });
    });
    </script>
</body>
</html>
