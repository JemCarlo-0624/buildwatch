<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin','worker'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    die("❌ Project ID missing");
}

// Fetch project details with creator information
$stmt = $pdo->prepare("
    SELECT p.*, u.name as creator_name, u.email as creator_email 
    FROM projects p 
    JOIN users u ON p.created_by = u.id 
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("❌ Project not found");
}

// Check if user has access to this project
if ($_SESSION['role'] === 'worker') {
    $accessCheck = $pdo->prepare("SELECT 1 FROM project_assignments WHERE project_id = ? AND user_id = ?");
    $accessCheck->execute([$project_id, $_SESSION['user_id']]);
    if (!$accessCheck->fetch()) {
        die("❌ Access denied. You are not assigned to this project.");
    }
}

// Fetch assigned team members
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, u.role 
    FROM project_assignments pa 
    JOIN users u ON pa.user_id = u.id 
    WHERE pa.project_id = ? 
    ORDER BY u.role, u.name
");
$stmt->execute([$project_id]);
$teamMembers = $stmt->fetchAll();

// Fetch all tasks for this project
$stmt = $pdo->prepare("
    SELECT t.*, u.name as assigned_name, u.email as assigned_email 
    FROM tasks t 
    LEFT JOIN users u ON t.assigned_to = u.id 
    WHERE t.project_id = ? 
    ORDER BY t.due_date ASC, t.created_at DESC
");
$stmt->execute([$project_id]);
$tasks = $stmt->fetchAll();

// Calculate project statistics
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, function($task) { return $task['progress'] == 100; }));
$inProgressTasks = count(array_filter($tasks, function($task) { return $task['progress'] > 0 && $task['progress'] < 100; }));
$notStartedTasks = count(array_filter($tasks, function($task) { return $task['progress'] == 0; }));

// Calculate overall project progress
$overallProgress = $totalTasks > 0 ? round(array_sum(array_column($tasks, 'progress')) / $totalTasks) : 0;

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
    <title><?php echo htmlspecialchars($project['name']); ?> - Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .project-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .project-title-section {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .project-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 10px 0;
        }

        .project-id {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }

        .project-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-active { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-on-hold { background-color: #f8d7da; color: #721c24; }
        .status-ongoing { background-color: #d1ecf1; color: #0c5460; }

        .project-description {
            color: var(--gray);
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .project-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .meta-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
        }

        .meta-content {
            flex: 1;
        }

        .meta-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .meta-value {
            font-size: 15px;
            color: var(--dark);
            font-weight: 600;
            margin-top: 2px;
        }

        .progress-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .progress-percentage {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-bar-large {
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
            background: linear-gradient(90deg, var(--primary), var(--success));
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .progress-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .progress-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }

        .progress-stat-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            margin-top: 5px;
            font-weight: 600;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .team-member-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .team-member-card:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .team-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .team-avatar.role-pm {
            background: var(--secondary);
        }

        .team-avatar.role-worker {
            background: var(--success);
        }

        .team-info {
            flex: 1;
            min-width: 0;
        }

        .team-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .team-role {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
        }

        .tasks-table {
            width: 100%;
            margin-top: 15px;
        }

        .tasks-table thead {
            background: #f8f9fa;
        }

        .tasks-table th {
            padding: 12px 15px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark);
            border-bottom: 2px solid #e9ecef;
        }

        .tasks-table td {
            padding: 15px;
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
        }

        .task-description-small {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        .worker-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .worker-avatar-small {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--success);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 11px;
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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .project-title {
                font-size: 24px;
            }

            .project-meta-grid {
                grid-template-columns: 1fr;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }

            .tasks-table {
                display: block;
                overflow-x: auto;
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
            <div class="nav-section-title">
                <?php 
                if ($_SESSION['role'] === 'admin') echo 'Admin Panel';
                elseif ($_SESSION['role'] === 'pm') echo 'PM Panel';
                else echo 'Worker Panel';
                ?>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboard_admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item active"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
                <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
                <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
            <?php elseif ($_SESSION['role'] === 'pm'): ?>
                <a href="dashboard_pm.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item active"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
            <?php else: ?>
                <a href="dashboard_worker.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_worker.php" class="nav-item active"><i class="fas fa-project-diagram"></i> My Projects</a>
                <a href="tasks_worker.php" class="nav-item"><i class="fas fa-tasks"></i> My Tasks</a>
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="<?php echo $_SESSION['role'] === 'worker' ? 'projects_worker.php' : 'projects_list.php'; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>

        <!-- Project Header -->
        <div class="project-header">
            <div class="project-title-section">
                <div>
                    <h1 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h1>
                    <div class="project-id">Project ID: #<?php echo (int)$project['id']; ?></div>
                </div>
                <div class="project-actions">
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $project['status'])); ?>">
                        <?php echo htmlspecialchars($project['status']); ?>
                    </span>
                    <?php if ($_SESSION['role'] !== 'worker'): ?>
                        <a href="project_edit.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Project
                        </a>
                        <a href="projects_assign.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus"></i> Assign Team
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($project['description'])): ?>
            <div class="project-description">
                <?php echo nl2br(htmlspecialchars($project['description'])); ?>
            </div>
            <?php endif; ?>

            <div class="project-meta-grid">
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Project Manager</div>
                        <div class="meta-value"><?php echo htmlspecialchars($project['creator_name']); ?></div>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Created Date</div>
                        <div class="meta-value"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></div>
                    </div>
                </div>
                <?php if (!empty($project['location'])): ?>
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Location</div>
                        <div class="meta-value"><?php echo htmlspecialchars($project['location']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Team Size</div>
                        <div class="meta-value"><?php echo count($teamMembers); ?> Members</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Section -->
        <div class="progress-section">
            <div class="progress-header">
                <h2 class="progress-title">Overall Project Progress</h2>
                <div class="progress-percentage"><?php echo $overallProgress; ?>%</div>
            </div>
            <div class="progress-bar-large">
                <div class="progress-bar-fill" style="width: <?php echo $overallProgress; ?>%;"></div>
            </div>
            <div class="progress-stats">
                <div class="progress-stat">
                    <div class="progress-stat-value"><?php echo $totalTasks; ?></div>
                    <div class="progress-stat-label">Total Tasks</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-value" style="color: var(--success);"><?php echo $completedTasks; ?></div>
                    <div class="progress-stat-label">Completed</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-value" style="color: var(--primary);"><?php echo $inProgressTasks; ?></div>
                    <div class="progress-stat-label">In Progress</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-value" style="color: var(--accent);"><?php echo $overdueTasks; ?></div>
                    <div class="progress-stat-label">Overdue</div>
                </div>
            </div>
        </div>

        <!-- Team Members Section -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-users"></i>
                    Team Members
                </h2>
                <?php if ($_SESSION['role'] !== 'worker'): ?>
                <a href="projects_assign.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-user-plus"></i> Add Member
                </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($teamMembers)): ?>
            <div class="team-grid">
                <?php foreach ($teamMembers as $member): ?>
                <div class="team-member-card">
                    <div class="team-avatar role-<?php echo strtolower($member['role']); ?>">
                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                    </div>
                    <div class="team-info">
                        <div class="team-name"><?php echo htmlspecialchars($member['name']); ?></div>
                        <div class="team-role"><?php echo htmlspecialchars($member['role']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No team members assigned yet</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tasks Section -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Project Tasks
                </h2>
                <?php if ($_SESSION['role'] !== 'worker'): ?>
                <a href="tasks_create.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Task
                </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($tasks)): ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td class="task-title-cell">
                            <div><?php echo htmlspecialchars($task['title']); ?></div>
                            <?php if (!empty($task['description'])): ?>
                            <div class="task-description-small">
                                <?php echo htmlspecialchars(substr($task['description'], 0, 100)) . (strlen($task['description']) > 100 ? '...' : ''); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($task['assigned_name'])): ?>
                            <div class="worker-badge">
                                <div class="worker-avatar-small">
                                    <?php echo strtoupper(substr($task['assigned_name'], 0, 1)); ?>
                                </div>
                                <?php echo htmlspecialchars($task['assigned_name']); ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <p>No tasks created for this project yet</p>
                <?php if ($_SESSION['role'] !== 'worker'): ?>
                <a href="tasks_create.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create First Task
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
