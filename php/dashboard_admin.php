<?php
require_once("auth_check.php");
requireRole("admin");
require_once("../config/db.php");

$pageTitle = "Admin Dashboard";
$pageDescription = "Welcome back! Here's an overview of your construction management system.";
$pageActions = '<a href="dashboard_admin.php" class="btn btn-primary" title="Refresh dashboard data"><i class="fas fa-sync-alt"></i> Refresh</a>';

// Add error handling and debugging
try {
    // Fetch dashboard statistics
    $totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $tasksCompleted = $pdo->query("SELECT COUNT(*) FROM tasks WHERE progress=100")->fetchColumn();
    $pendingProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals WHERE status='pending'")->fetchColumn();
    $activeProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status='ongoing'")->fetchColumn();


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
    $proposals = $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
            outline: 2px solid transparent;
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
        .status-unknown { background-color: #e2e3e5; color: #495057; }

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
        .btn-outline {
            border: 1px solid #d0d0d0;
            color: var(--dark);
            background: var(--white);
        }

        .btn-outline:hover {
            background: #f5f5f5;
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-outline:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Responsive adjustments using CSS variables */
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
    </style>
</head>
<body class="sidebar-main-layout">
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Admin Panel</div>
            <a href="dashboard_admin.php" class="nav-item active" aria-current="page"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
            <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
            <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
            <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;" aria-label="User avatar">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <small class="text-white-50">Administrator</small>
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
                <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="page-description"><?php echo htmlspecialchars($pageDescription); ?></p>
            </div>
            <div class="d-flex gap-2">
                <?php echo $pageActions; ?>
            </div>
        </div>
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
        </div>
        <div class="dashboard-grid">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightbulb" style="color: var(--accent);"></i> Recent Proposals</h3>
                    <a href="proposals_review.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($proposals)): ?>
                        <?php foreach ($proposals as $p): ?>
                            <div class="project-item" role="article" tabindex="0">
                                <div class="project-color" style="background-color: 
                                    <?php 
                                    switch($p['status']) {
                                        case 'pending': echo 'var(--warning)'; break;
                                        case 'approved': echo 'var(--success)'; break;
                                        case 'rejected': echo 'var(--accent)'; break;
                                        default: echo 'var(--gray)';
                                    }
                                    ?>" aria-label="Status indicator"></div>
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
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                            <div class="empty-state-text">No proposals found</div>
                            <div class="empty-state-action"><a href="proposals_review.php">Create a new proposal →</a></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tasks" style="color: var(--success);"></i> Recent Tasks</h3>
                    <a href="tasks_list.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $t): ?>
                            <div class="task-item" role="article" tabindex="0">
                                <input type="checkbox" class="task-checkbox form-check-input" <?php echo ($t['progress'] ?? 0) == 100 ? 'checked' : ''; ?> disabled aria-label="Task completion status">
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
                                             style="width:<?php echo $progress; ?>%;background:<?php echo $progressColor; ?>;"
                                             role="progressbar" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                        <span class="progress-text"><?php echo $progress; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
                            <div class="empty-state-text">No tasks found</div>
                            <div class="empty-state-action"><a href="tasks_list.php">Create a new task →</a></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
