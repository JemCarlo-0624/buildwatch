<?php
require_once("auth_check.php");
// Ensure requireRole is defined in auth_check.php
requireRole("admin");
require_once("../config/db.php");

// Add error handling and debugging
try {
    // Fetch dashboard statistics
    $totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $tasksCompleted = $pdo->query("SELECT COUNT(*) FROM tasks WHERE progress=100")->fetchColumn();
    $pendingProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals WHERE status='pending'")->fetchColumn();
    $activeProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='ongoing'")->fetchColumn();

    // Fetch upcoming schedule with better error handling
    $scheduleQuery = "
        SELECT 
            s.id, s.project_id, s.task_id, s.start_date, s.end_date,
            COALESCE(t.title, CONCAT('Task #', s.task_id)) AS task_title,
            COALESCE(p.name, CONCAT('Project #', s.project_id)) AS project_name
        FROM schedule s
        LEFT JOIN tasks t ON s.task_id = t.id
        LEFT JOIN projects p ON s.project_id = p.id
        WHERE s.start_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ORDER BY s.start_date ASC
        LIMIT 10
    ";
    
    try {
        $upcomingSchedule = $pdo->query($scheduleQuery)->fetchAll(PDO::FETCH_ASSOC);
        
        $debugScheduleQuery = "
            SELECT 
                COUNT(*) as total_count,
                COUNT(CASE WHEN s.start_date >= CURDATE() THEN 1 END) as future_count,
                COUNT(CASE WHEN s.start_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_count,
                MIN(s.start_date) as earliest_date,
                MAX(s.start_date) as latest_date
            FROM schedule s
        ";
        $scheduleDebugInfo = $pdo->query($debugScheduleQuery)->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $scheduleError) {
        error_log("Schedule query error: " . $scheduleError->getMessage());
        $upcomingSchedule = [];
        $scheduleDebugInfo = ['total_count' => 0, 'future_count' => 0, 'week_count' => 0, 'earliest_date' => null, 'latest_date' => null];
    }

    // Fetch recent proposals
    $proposals = $pdo->query("SELECT * FROM project_proposals ORDER BY submitted_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent tasks with project names
    $tasksQuery = "
        SELECT 
            t.*,
            COALESCE(p.name, CONCAT('Project #', t.project_id)) AS project_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        ORDER BY t.created_at DESC 
        LIMIT 5
    ";
    $tasks = $pdo->query($tasksQuery)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors gracefully
    error_log("Database error in dashboard: " . $e->getMessage());
    $totalProjects = $tasksCompleted = $pendingProposals = $activeProjects = 0;
    $upcomingSchedule = $proposals = $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BuildWatch</title>
    <!-- Added Bootstrap 5 CDN -->
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

        /* Debug info styling */
        .debug-info {
            font-size: 11px;
            color: var(--gray);
            line-height: 1.4;
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .debug-info br {
            margin-bottom: 3px;
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

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Admin Panel</div>
            <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
            <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
            <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
            <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
        </div>

        <!-- Simplified sidebar footer using Bootstrap utilities -->
        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    AD
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@example.com'); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Using Bootstrap grid and utilities -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-description">Welcome back! Here's an overview of your construction management system.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard_admin.php" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</a>
                <a href="reports.php" class="btn btn-outline-primary"><i class="fas fa-download"></i> Export Report</a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-label">TOTAL PROJECTS</div>
                <div class="stat-change positive"><?php echo number_format($activeProjects); ?> active</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($tasksCompleted); ?></div>
                <div class="stat-label">TASKS COMPLETED</div>
                <div class="stat-change positive">This month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-lightbulb"></i></div>
                <div class="stat-value"><?php echo number_format($pendingProposals); ?></div>
                <div class="stat-label">PENDING PROPOSALS</div>
                <div class="stat-change negative">Needs review</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-secondary"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?php echo count($upcomingSchedule); ?></div>
                <div class="stat-label">UPCOMING EVENTS</div>
                <div class="stat-change positive">Next 7 days</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">

            <!-- Upcoming Schedule Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar" style="color: var(--primary);"></i> Upcoming Schedule</h3>
                    <a href="schedule.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingSchedule)): ?>
                        <?php foreach ($upcomingSchedule as $event): ?>
                            <div class="project-item">
                                <div class="project-color" style="background-color: var(--primary);"></div>
                                <div class="project-info">
                                    <div class="project-item-title">
                                        <?php echo htmlspecialchars($event['task_title'] ?? 'Untitled Event'); ?>
                                    </div>
                                    <div class="project-item-details">
                                        <?php echo htmlspecialchars($event['project_name'] ?? 'No Project'); ?> •
                                        <?php 
                                        try {
                                            if (!empty($event['start_date'])) {
                                                $startDate = new DateTime($event['start_date']);
                                                echo $startDate->format('M j');
                                            } else {
                                                echo 'No start';
                                            }
                                            echo ' - ';
                                            if (!empty($event['end_date'])) {
                                                $endDate = new DateTime($event['end_date']);
                                                echo $endDate->format('M j');
                                            } else {
                                                echo 'No end';
                                            }
                                        } catch (Exception $e) {
                                            echo 'Invalid date';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-calendar-times fs-1 text-muted mb-3"></i>
                            <p>No upcoming schedule events</p>
                            <?php if (isset($scheduleDebugInfo)): ?>
                                <div class="alert alert-info small text-start mt-3">
                                    <strong>Debug Info:</strong><br>
                                    Total schedule entries: <?php echo $scheduleDebugInfo['total_count'] ?? 0; ?><br>
                                    Future events: <?php echo $scheduleDebugInfo['future_count'] ?? 0; ?><br>
                                    Events in last week: <?php echo $scheduleDebugInfo['week_count'] ?? 0; ?><br>
                                    <?php if ($scheduleDebugInfo['earliest_date']): ?>
                                        Earliest: <?php echo date('Y-m-d', strtotime($scheduleDebugInfo['earliest_date'])); ?><br>
                                    <?php endif; ?>
                                    <?php if ($scheduleDebugInfo['latest_date']): ?>
                                        Latest: <?php echo date('Y-m-d', strtotime($scheduleDebugInfo['latest_date'])); ?><br>
                                    <?php endif; ?>
                                    Current date: <?php echo date('Y-m-d'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Proposals Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightbulb" style="color: var(--accent);"></i> Recent Proposals</h3>
                    <a href="proposals_review.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($proposals)): ?>
                        <?php foreach ($proposals as $p): ?>
                            <div class="project-item">
                                <div class="project-color" style="background-color: 
                                    <?php 
                                    switch($p['status']) {
                                        case 'pending': echo 'var(--warning)'; break;
                                        case 'approved': echo 'var(--success)'; break;
                                        case 'rejected': echo 'var(--accent)'; break;
                                        default: echo 'var(--gray)';
                                    }
                                    ?>">
                                </div>
                                <div class="project-info">
                                    <div class="project-item-title"><?php echo htmlspecialchars($p['title'] ?? 'Untitled Proposal'); ?></div>
                                    <div class="project-item-details">
                                        <?php echo htmlspecialchars($p['client_name'] ?? 'Unknown Client'); ?> •
                                        <span class="status-badge status-<?php echo htmlspecialchars($p['status'] ?? 'unknown'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($p['status'] ?? 'Unknown')); ?>
                                        </span> •
                                        <?php 
                                        try {
                                            if (!empty($p['submitted_at'])) {
                                                echo date('M j', strtotime($p['submitted_at']));
                                            } else {
                                                echo 'No date';
                                            }
                                        } catch (Exception $e) {
                                            echo 'Invalid date';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fs-1 text-muted mb-3"></i>
                            <p>No proposals found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Tasks Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tasks" style="color: var(--success);"></i> Recent Tasks</h3>
                    <a href="tasks_list.php" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-item">
                                <input type="checkbox" class="task-checkbox form-check-input" <?php echo ($t['progress'] ?? 0) == 100 ? 'checked' : ''; ?> disabled>
                                <div class="task-info">
                                    <div class="task-title"><?php echo htmlspecialchars($t['title'] ?? 'Untitled Task'); ?></div>
                                    <div class="project-item-details">
                                        <?php echo htmlspecialchars($t['project_name'] ?? 'No Project'); ?>
                                        <?php 
                                        try {
                                            if (!empty($t['created_at'])) {
                                                echo ' • ' . date('M j', strtotime($t['created_at']));
                                            }
                                        } catch (Exception $e) {
                                            // Ignore date formatting errors
                                        }
                                        ?>
                                    </div>
                                    <div class="task-progress">
                                        <?php 
                                        $progress = $t['progress'] ?? 0;
                                        $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                                        ?>
                                        <div class="progress-bar" 
                                             style="width:<?php echo $progress; ?>%;background:<?php echo $progressColor; ?>;">
                                        </div>
                                        <span class="progress-text"><?php echo $progress; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-clipboard-list fs-1 text-muted mb-3"></i>
                            <p>No tasks found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div> <!-- End Dashboard Grid -->

    </div> <!-- End Main Content -->

    <!-- Added Bootstrap 5 JS bundle -->
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
