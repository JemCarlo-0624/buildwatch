<?php
require_once("auth_check.php");
requireRole("worker");
require_once("../config/db.php");

$user_id = $_SESSION['user_id'];

// Fetch projects assigned to this worker with additional details
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as creator_name,
           c.name as client_name,
           COUNT(DISTINCT t.id) as total_tasks,
           SUM(CASE WHEN t.progress = 100 THEN 1 ELSE 0 END) as completed_tasks,
           AVG(t.progress) as avg_progress
    FROM project_assignments pa 
    JOIN projects p ON pa.project_id = p.id
    JOIN users u ON p.created_by = u.id
    LEFT JOIN clients c ON p.client_id = c.id
    LEFT JOIN tasks t ON t.project_id = p.id
    WHERE pa.user_id = ?
    GROUP BY p.id, u.name, c.name
    ORDER BY p.last_activity_at DESC
");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();

function determineProjectStatus($project) {
    $status = strtolower($project['status'] ?? 'unknown');
    return $status;
}

// Update project statuses based on database status
foreach ($projects as &$project) {
    $project['display_status'] = determineProjectStatus($project);
    $project['db_status'] = strtolower($project['status'] ?? 'unknown');
}

// Calculate statistics based on database status
$totalProjects = count($projects);
$activeProjects = count(array_filter($projects, function($p) { return $p['db_status'] === 'ongoing'; }));
$completedProjects = count(array_filter($projects, function($p) { return $p['db_status'] === 'completed'; }));
$onHoldProjects = count(array_filter($projects, function($p) { return $p['db_status'] === 'on-hold'; }));

// Get overdue projects
$overdue_projects = 0;
$today = date('Y-m-d');
foreach ($projects as $p) {
    if ($p['end_date'] && $p['end_date'] < $today && $p['db_status'] !== 'completed') {
        $overdue_projects++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --navy-blue: #1e3a5f;
            --navy-dark: #152d47;
            --primary-teal: #0d9488;
            --primary-teal-dark: #0f766e;
            --primary-teal-light: #14b8a6;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --border-color: #e0e6ed;
        }

        body {
            background: var(--light-bg);
            color: var(--text-primary);
        }

        .page-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 24px 32px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .page-header-content h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--text-primary);
        }

        .page-header-content p {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
        }

        .page-header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-header {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-header-primary {
            background: var(--primary-teal);
            color: var(--white);
            border: 1px solid var(--primary-teal);
        }

        .btn-header-primary:hover {
            background: var(--primary-teal-dark);
            border-color: var(--primary-teal-dark);
            color: var(--white);
        }

        .btn-header-outline {
            background: var(--white);
            color: var(--primary-teal);
            border: 1px solid var(--border-color);
        }

        .btn-header-outline:hover {
            background: var(--light-bg);
            border-color: var(--primary-teal);
        }

        .filter-section {
            background: var(--white);
            padding: 20px 32px;
            margin-bottom: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            margin: 0;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--text-primary);
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            border-color: var(--primary-teal);
            color: var(--primary-teal);
        }

        .filter-btn.active {
            background: var(--primary-teal);
            color: var(--white);
            border-color: var(--primary-teal);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
            padding: 0 32px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon-primary {
            background: rgba(13, 148, 136, 0.1);
            color: var(--primary-teal);
        }

        .stat-icon-success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .stat-icon-warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .stat-icon-danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .projects-container {
            padding: 0 32px;
            margin-bottom: 32px;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }

        .project-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .project-card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .project-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            flex: 1;
        }

        .project-status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .status-ongoing {
            background: rgba(13, 148, 136, 0.1);
            color: var(--primary-teal);
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .status-on-hold {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .status-planning {
            background: rgba(108, 117, 125, 0.1);
            color: var(--text-secondary);
        }

        .project-card-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .project-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }

        .meta-item i {
            width: 16px;
            text-align: center;
            color: var(--primary-teal);
        }

        .progress-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }

        .progress-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .progress-value {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-teal), var(--primary-teal-light));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .deadline-section {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .deadline-on-track {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .deadline-at-risk {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }

        .deadline-overdue {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }

        .project-card-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 8px;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--text-primary);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-small:hover {
            background: var(--primary-teal);
            color: var(--white);
            border-color: var(--primary-teal);
        }

        .empty-state {
            text-align: center;
            padding: 60px 32px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
            color: var(--text-secondary);
        }

        .empty-state-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .empty-state-text {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .auto-refresh-info {
            text-align: center;
            padding: 12px;
            background: rgba(13, 148, 136, 0.05);
            border-radius: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 24px;
            margin-left: 32px;
            margin-right: 32px;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 16px;
                padding-left: 16px;
                padding-right: 16px;
            }

            .page-header-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn-header {
                width: 100%;
                justify-content: center;
            }

            .filter-section,
            .projects-container,
            .stats-grid,
            .auto-refresh-info {
                padding-left: 16px;
                padding-right: 16px;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .filter-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
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
            <a href="dashboard_worker.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="tasks_worker.php" class="nav-item"><i class="fas fa-tasks"></i> My Tasks</a>
            <a href="projects_worker.php" class="nav-item active"><i class="fas fa-project-diagram"></i> My Projects</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Updated page header with new design matching projects_list.php -->
        <div class="page-header">
            <div class="page-header-content">
                <h1><i class="fas fa-project-diagram"></i> My Projects</h1>
                <p>View and manage your assigned construction projects</p>
            </div>
            <div class="page-header-actions">
                <a href="dashboard_worker.php" class="btn-header btn-header-outline">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="tasks_worker.php" class="btn-header btn-header-primary">
                    <i class="fas fa-tasks"></i> View My Tasks
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-spinner"></i></div>
                <div class="stat-value"><?php echo number_format($activeProjects); ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-warning"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($completedProjects); ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-danger"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-value"><?php echo number_format($overdue_projects); ?></div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <!-- Updated filter section with new styling -->
        <div class="filter-section">
            <span class="filter-label">Filter by Status:</span>
            <button class="filter-btn active" onclick="filterProjects('all')">All Projects (<?php echo $totalProjects; ?>)</button>
            <button class="filter-btn" onclick="filterProjects('ongoing')">Ongoing (<?php echo $activeProjects; ?>)</button>
            <button class="filter-btn" onclick="filterProjects('completed')">Completed (<?php echo $completedProjects; ?>)</button>
            <button class="filter-btn" onclick="filterProjects('on-hold')">On Hold (<?php echo $onHoldProjects; ?>)</button>
        </div>

        <!-- Projects Grid -->
        <div class="projects-container">
            <?php if (!empty($projects)): ?>
                <div class="projects-grid" id="projectsGrid">
                    <?php foreach ($projects as $p): ?>
                        <?php 
                        // Calculate progress
                        if ($p['total_tasks'] > 0) {
                            $progress = round(($p['completed_tasks'] / $p['total_tasks']) * 100);
                        } else {
                            $progress = (int)($p['completion_percentage'] ?? 0);
                        }

                        // Determine deadline status
                        $deadline_status = 'on-track';
                        $deadline_text = 'On Track';
                        $deadline_icon = 'fa-check-circle';
                        
                        if ($p['end_date']) {
                            $end_date = new DateTime($p['end_date']);
                            $today = new DateTime();
                            $days_remaining = $today->diff($end_date)->days;
                            $is_overdue = $today > $end_date;

                            if ($is_overdue && $p['db_status'] !== 'completed') {
                                $deadline_status = 'overdue';
                                $deadline_text = 'Overdue by ' . $days_remaining . ' days';
                                $deadline_icon = 'fa-exclamation-circle';
                            } elseif ($days_remaining <= 7 && $p['db_status'] === 'ongoing') {
                                $deadline_status = 'at-risk';
                                $deadline_text = 'Due in ' . $days_remaining . ' days';
                                $deadline_icon = 'fa-clock';
                            } else {
                                $deadline_text = 'Due ' . $end_date->format('M d, Y');
                            }
                        } else {
                            $deadline_text = 'No deadline set';
                        }
                        ?>
                        <!-- Updated project card design with new layout and styling -->
                        <div class="project-card" data-status="<?php echo $p['db_status']; ?>">
                            <div class="project-card-header">
                                <h3 class="project-card-title"><?php echo htmlspecialchars($p['name'] ?? 'Untitled Project'); ?></h3>
                                <span class="project-status-badge status-<?php echo htmlspecialchars($p['db_status']); ?>">
                                    <?php echo ucfirst(str_replace('-', ' ', $p['db_status'])); ?>
                                </span>
                            </div>

                            <div class="project-card-body">
                                <div class="project-meta">
                                    <?php if ($p['client_name']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-building"></i>
                                            <span><?php echo htmlspecialchars($p['client_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($p['creator_name'] ?? 'Unknown'); ?></span>
                                    </div>
                                    <?php if ($p['priority']): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-flag"></i>
                                            <span><?php echo ucfirst($p['priority']); ?> Priority</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($p['total_tasks'] > 0): ?>
                                <div class="progress-section">
                                    <div class="progress-header">
                                        <span class="progress-label">Progress</span>
                                        <span class="progress-value"><?php echo $progress; ?>%</span>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                        <?php echo $p['completed_tasks']; ?> of <?php echo $p['total_tasks']; ?> tasks completed
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="deadline-section deadline-<?php echo $deadline_status; ?>">
                                    <i class="fas <?php echo $deadline_icon; ?>"></i>
                                    <span><?php echo $deadline_text; ?></span>
                                </div>
                            </div>

                            <div class="project-card-footer">
                                <a href="tasks_worker.php?project=<?php echo $p['id']; ?>" class="btn-small">
                                    <i class="fas fa-tasks"></i> View Tasks
                                </a>
                                <a href="projects_details.php?id=<?php echo $p['id']; ?>" class="btn-small">
                                    <i class="fas fa-eye"></i> Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <div class="empty-state-title">No Projects Assigned</div>
                    <div class="empty-state-text">You don't have any projects assigned to you yet. Check back later or contact your project manager.</div>
                    <a href="dashboard_worker.php" class="btn-small" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="auto-refresh-info">
            <i class="fas fa-sync-alt"></i> This page auto-refreshes every 5 minutes
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        function filterProjects(status) {
            const cards = document.querySelectorAll('.project-card');
            const buttons = document.querySelectorAll('.filter-btn');

            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Filter cards based on database status
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 5 * 60 * 1000);

        // Initial load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[v0] Worker Projects Dashboard loaded');
        });
    </script>
</body>
</html>
