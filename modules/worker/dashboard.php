<?php
require_once(__DIR__ . '/../../core/auth.php');
requireRole("worker");
require_once(__DIR__ . '/../../config/db.php');

$pageTitle = "Worker Dashboard";
$pageDescription = "Welcome back! Manage your assigned projects and tasks.";
$pageActions = '<a href="dashboard_worker.php" class="btn btn-primary" title="Refresh dashboard data"><i class="fas fa-sync-alt"></i> Refresh</a>';

$user_id = $_SESSION['user_id'];

// Fetch projects assigned to this worker
$stmt = $pdo->prepare("SELECT p.* FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE pa.user_id = ? ORDER BY p.created_at DESC");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();

// Fetch tasks assigned to this worker, grouped by project
$stmtTasks = $pdo->prepare("SELECT t.*, p.name AS project_name FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? ORDER BY p.name, t.due_date ASC");
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
    <title>Worker Dashboard - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced HCI principles: improved visual hierarchy, feedback, and accessibility */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--space-xl);
            margin-top: var(--space-lg);
        }

        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 0;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #e0e0e0;
        }

        .card:focus-within {
            box-shadow: 0 0 0 3px rgba(10, 99, 165, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-lg);
            border-bottom: 2px solid #f5f5f5;
            background: linear-gradient(135deg, #fafbfc 0%, #f5f7fa 100%);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .card-body {
            padding: 0;
        }

        .project-item, .task-item {
            display: flex;
            align-items: flex-start;
            padding: var(--space-md) var(--space-lg);
            border-bottom: 1px solid #f8f9fa;
            transition: background-color var(--transition-fast);
            cursor: pointer;
        }

        .project-item:hover, .task-item:hover {
            background-color: #f8f9fa;
        }

        .project-item:focus-within, .task-item:focus-within {
            background-color: rgba(10, 99, 165, 0.05);
        }

        .project-item:last-child, .task-item:last-child {
            border-bottom: none;
        }

        .project-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: var(--space-md);
            margin-top: 5px;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            border-radius: var(--radius-sm);
            margin-top: var(--space-sm);
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: var(--radius-sm);
            transition: width var(--transition-normal);
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
            margin-right: var(--space-md);
            margin-top: 3px;
            cursor: pointer;
        }

        .task-checkbox:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
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

        .empty-state {
            padding: var(--space-2xl) var(--space-lg);
            text-align: center;
            color: var(--gray);
        }

        .empty-state-icon {
            font-size: 48px;
            color: #d0d0d0;
            margin-bottom: var(--space-md);
        }

        .empty-state-text {
            font-size: 14px;
            margin-bottom: var(--space-md);
        }

        .empty-state-action {
            font-size: 12px;
            color: var(--primary);
        }

        /* Improved button styling for better visual feedback */
        .btn-outline-primary {
            border: 1px solid #d0d0d0;
            color: var(--dark);
            background: var(--white);
        }

        .btn-outline-primary:hover {
            background: #f5f5f5;
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: var(--space-lg);
            }
            
            .card-header {
                padding: var(--space-md);
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-sm);
            }
            
            .project-item, .task-item {
                padding: var(--space-sm) var(--space-md);
            }
        }

        /* Stats container improvements */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-2xl);
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
            display: flex;
            flex-direction: column;
            transition: transform var(--transition-normal);
            border: 1px solid #f0f0f0;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--space-md);
            font-size: 20px;
        }

        .stat-icon-primary { background: rgba(10, 99, 165, 0.1); color: var(--primary); }
        .stat-icon-success { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .stat-icon-accent { background: rgba(212, 47, 19, 0.1); color: var(--accent); }
        .stat-icon-secondary { background: rgba(13, 148, 136, 0.1); color: var(--secondary); }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: var(--space-xs) 0;
            color: var(--dark);
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-change {
            font-size: 12px;
            margin-top: var(--space-xs);
            font-weight: 500;
        }

        .positive { color: var(--success); }
        .negative { color: var(--accent); }
    </style>
</head>
<body class="sidebar-main-layout">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Worker Panel</div>
            <a href="dashboard_worker.php" class="nav-item active" aria-current="page"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="tasks_worker.php" class="nav-item"><i class="fas fa-tasks"></i> My Tasks</a>
            <a href="projects_worker.php" class="nav-item"><i class="fas fa-project-diagram"></i> My Projects</a>
        </div>

        <!-- Updated sidebar footer to match admin dashboard -->
        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;" aria-label="User avatar">
                    W
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Worker'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? 'worker@example.com'); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Worker Dashboard</h1>
                <p class="page-description">Welcome back! Manage your assigned projects and tasks.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard_worker.php" class="btn btn-primary" title="Refresh dashboard data"><i class="fas fa-sync-alt"></i> Refresh</a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-label">ASSIGNED PROJECTS</div>
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
                <div class="stat-label">COMPLETED TASKS</div>
                <div class="stat-change positive">Finished</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo number_format($pendingTasks); ?></div>
                <div class="stat-label">PENDING TASKS</div>
                <div class="stat-change negative">In progress</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- My Assigned Projects Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open" style="color: var(--primary);"></i> My Assigned Projects</h3>
                    <a href="projects_worker.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $p): ?>
                            <div class="project-item" role="article" tabindex="0">
                                <div class="project-color" style="background-color: 
                                    <?php 
                                    switch(strtolower($p['status'])) {
                                        case 'active': echo 'var(--success)'; break;
                                        case 'completed': echo 'var(--primary)'; break;
                                        case 'on-hold': echo 'var(--warning)'; break;
                                        default: echo 'var(--accent)';
                                    }
                                    ?>" aria-label="Status indicator"></div>
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
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                            <div class="empty-state-text">You are not assigned to any projects yet</div>
                            <div class="empty-state-action"><a href="projects_worker.php">View available projects →</a></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Task Schedule Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt" style="color: var(--success);"></i> My Task Schedule</h3>
                    <a href="tasks_worker.php" class="btn btn-outline-primary btn-sm">View All Tasks</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($schedule)): ?>
                        <?php foreach ($schedule as $projectName => $projectTasks): ?>
                            <?php foreach (array_slice($projectTasks, 0, 5) as $task): ?>
                                <div class="task-item" role="article" tabindex="0">
                                    <input type="checkbox" class="task-checkbox form-check-input" <?php echo ($task['progress'] ?? 0) == 100 ? 'checked' : ''; ?> disabled aria-label="Task completion status">
                                    <div class="task-info">
                                        <div class="task-title"><?php echo htmlspecialchars($task['title'] ?? 'Untitled Task'); ?></div>
                                        <div class="project-item-details">
                                            <?php echo htmlspecialchars($projectName); ?>
                                            <?php 
                                            try {
                                                if (!empty($task['due_date'])) {
                                                    echo ' • Due: ' . date('M j, Y', strtotime($task['due_date']));
                                                }
                                            } catch (Exception $e) {
                                                // Ignore date formatting errors
                                            }
                                            ?>
                                        </div>
                                        <div class="task-progress">
                                            <?php 
                                            $progress = $task['progress'] ?? 0;
                                            $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                                            ?>
                                            <div class="progress-bar" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;" role="progressbar" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            <span class="progress-text"><?php echo $progress; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="empty-state-text">No tasks scheduled for your assigned projects</div>
                            <div class="empty-state-action"><a href="tasks_worker.php">View all tasks →</a></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- End Dashboard Grid -->

    </div> <!-- End Main Content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.task-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const taskItem = this.closest('.task-item');
                if (taskItem) {
                    taskItem.style.opacity = this.checked ? '0.7' : '1';
                }
            });
        });

        const items = document.querySelectorAll('.project-item, .task-item');
        items.forEach(item => {
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    const link = this.querySelector('a');
                    if (link) link.click();
                }
            });
        });
    });
    </script>
</body>
</html>
