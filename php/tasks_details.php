<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin','worker'])) {
    header("Location: login.php");
    exit;
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) {
    die("❌ Task ID missing");
}

$stmt = $pdo->prepare("
    SELECT t.*, 
           p.name as project_name, p.status as project_status,
           u.name as worker_name, u.email as worker_email, u.role as worker_role,
           creator.name as creator_name, creator.email as creator_email
    FROM tasks t 
    JOIN projects p ON t.project_id = p.id 
    LEFT JOIN users u ON t.assigned_to = u.id 
    LEFT JOIN users creator ON p.created_by = creator.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    die("❌ Task not found");
}

// Check if worker has access to this task
if ($_SESSION['role'] === 'worker') {
    if ($task['assigned_to'] != $_SESSION['user_id']) {
        die("❌ Access denied. This task is not assigned to you.");
    }
}

// Calculate task status indicators
$progress = (int)$task['progress'];
$isOverdue = false;
$daysUntilDue = null;

if (!empty($task['due_date'])) {
    $dueDate = strtotime($task['due_date']);
    $today = strtotime('today');
    $daysUntilDue = floor(($dueDate - $today) / 86400);
    
    if ($daysUntilDue < 0 && $progress < 100) {
        $isOverdue = true;
    }
}

$currentStatus = 'pending';
if ($progress == 100) {
    $currentStatus = 'completed';
} elseif ($progress > 0) {
    $currentStatus = 'in-progress';
}

$statusConfig = [
    'pending' => ['color' => '#6c757d', 'icon' => 'fa-clock', 'bg' => '#f5f5f5'],
    'in-progress' => ['color' => '#0a63a5', 'icon' => 'fa-spinner', 'bg' => '#e3f2fd'],
    'completed' => ['color' => '#2ecc71', 'icon' => 'fa-check-circle', 'bg' => '#e8f5e9'],
    'on-hold' => ['color' => '#f39c12', 'icon' => 'fa-pause-circle', 'bg' => '#fff3e0']
];

$statusInfo = $statusConfig[$currentStatus] ?? $statusConfig['pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['title']); ?> - Task Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .task-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .task-title-section {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .task-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 10px 0;
        }

        .task-id {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }

        .task-actions {
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .task-description {
            color: var(--gray);
            line-height: 1.8;
            font-size: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .task-meta-grid {
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

        .progress-stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .progress-stat-value {
            font-size: 20px;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 16px;
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .worker-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .worker-card:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .worker-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--success);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 24px;
            flex-shrink: 0;
        }

        .worker-info {
            flex: 1;
        }

        .worker-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 18px;
            margin-bottom: 4px;
        }

        .worker-email {
            font-size: 14px;
            color: var(--gray);
        }

        .alert-overdue {
            background: #ffebee;
            border-left: 4px solid #d32f2f;
            color: #d32f2f;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .alert-overdue i {
            font-size: 24px;
        }

        @media (max-width: 768px) {
            .task-title {
                font-size: 24px;
            }

            .task-meta-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
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
            <div class="nav-section-title">
                <?php 
                if ($_SESSION['role'] === 'admin') echo 'Admin Panel';
                elseif ($_SESSION['role'] === 'pm') echo 'PM Panel';
                else echo 'Worker Panel';
                ?>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboard_admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item active"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
                <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
                <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
            <?php elseif ($_SESSION['role'] === 'pm'): ?>
                <a href="dashboard_pm.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item active"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
            <?php else: ?>
                <a href="dashboard_worker.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_worker.php" class="nav-item"><i class="fas fa-project-diagram"></i> My Projects</a>
                <a href="tasks_worker.php" class="nav-item active"><i class="fas fa-tasks"></i> My Tasks</a>
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

        <div class="mb-3">
            <a href="<?php echo $_SESSION['role'] === 'worker' ? 'tasks_worker.php' : 'tasks_list.php'; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Tasks
            </a>
        </div>

        <?php if ($isOverdue): ?>
        <div class="alert-overdue">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Task Overdue!</strong> This task is <?php echo abs($daysUntilDue); ?> day(s) past the due date.
            </div>
        </div>
        <?php endif; ?>

        <div class="task-header">
            <div class="task-title-section">
                <div>
                    <h1 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h1>
                    <div class="task-id">Task ID: #<?php echo (int)$task['id']; ?></div>
                </div>
                <div class="task-actions">
                    <span class="status-badge" style="background: <?php echo $statusInfo['bg']; ?>; color: <?php echo $statusInfo['color']; ?>;">
                        <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                        <?php echo htmlspecialchars(ucfirst($currentStatus)); ?>
                    </span>
                    <?php if ($_SESSION['role'] !== 'worker'): ?>
                        <a href="tasks_edit.php?id=<?php echo $task['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Task
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($task['description'])): ?>
            <div class="task-description">
                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
            </div>
            <?php endif; ?>

            <div class="task-meta-grid">
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Project</div>
                        <div class="meta-value">
                            <a href="projects_details.php?id=<?php echo $task['project_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                <?php echo htmlspecialchars($task['project_name']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Created Date</div>
                        <div class="meta-value"><?php echo date('M j, Y', strtotime($task['created_at'])); ?></div>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-icon" style="<?php echo $isOverdue ? 'background: rgba(211, 47, 47, 0.1); color: #d32f2f;' : ''; ?>">
                        <i class="fas fa-<?php echo $isOverdue ? 'exclamation-triangle' : 'calendar-check'; ?>"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Due Date</div>
                        <div class="meta-value" style="<?php echo $isOverdue ? 'color: #d32f2f;' : ''; ?>">
                            <?php echo !empty($task['due_date']) ? date('M j, Y', strtotime($task['due_date'])) : 'Not set'; ?>
                        </div>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="meta-content">
                        <div class="meta-label">Project Manager</div>
                        <div class="meta-value"><?php echo htmlspecialchars($task['creator_name']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="progress-section">
            <div class="progress-header">
                <h2 class="progress-title">Task Progress</h2>
                <div class="progress-percentage"><?php echo $progress; ?>%</div>
            </div>
            <div class="progress-bar-large">
                <?php 
                $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                ?>
                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;"></div>
            </div>
            <div class="progress-stats">
                <div class="progress-stat">
                    <div class="progress-stat-icon" style="color: <?php echo $statusInfo['color']; ?>;">
                        <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                    </div>
                    <div class="progress-stat-value"><?php echo ucfirst($currentStatus); ?></div>
                    <div class="progress-stat-label">Current Status</div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-icon" style="color: <?php echo $isOverdue ? '#d32f2f' : ($daysUntilDue !== null && $daysUntilDue <= 3 ? '#f57c00' : 'var(--primary)'); ?>;">
                        <i class="fas fa-<?php echo $isOverdue ? 'exclamation-circle' : 'clock'; ?>"></i>
                    </div>
                    <div class="progress-stat-value">
                        <?php 
                        if ($daysUntilDue !== null) {
                            if ($isOverdue) {
                                echo abs($daysUntilDue) . ' days';
                            } elseif ($daysUntilDue == 0) {
                                echo 'Today';
                            } else {
                                echo $daysUntilDue . ' days';
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="progress-stat-label"><?php echo $isOverdue ? 'Overdue' : 'Time Remaining'; ?></div>
                </div>
                <div class="progress-stat">
                    <div class="progress-stat-icon" style="color: <?php echo $progress == 100 ? 'var(--success)' : 'var(--primary)'; ?>;">
                        <i class="fas fa-<?php echo $progress == 100 ? 'check-circle' : 'tasks'; ?>"></i>
                    </div>
                    <div class="progress-stat-value"><?php echo $progress; ?>%</div>
                    <div class="progress-stat-label">Completion</div>
                </div>
            </div>
        </div>


        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-user"></i>

                </h2>
            </div>
            <?php if (!empty($task['worker_name'])): ?>
            <div class="worker-card">
                <div class="worker-avatar">
                    <?php echo strtoupper(substr($task['worker_name'], 0, 1)); ?>
                </div>
                <div class="worker-info">
                    <div class="worker-name"><?php echo htmlspecialchars($task['worker_name']); ?></div>
                    <div class="worker-email">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($task['worker_email']); ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-slash fa-3x mb-3"></i>
                <p>No worker assigned to this task</p>
            </div>
            <?php endif; ?>
        </div>


        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>

                </h2>
                <a href="projects_details.php?id=<?php echo $task['project_id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> View Project
                </a>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Project Name</div>
                    <div class="info-value">
                        <i class="fas fa-folder" style="color: var(--primary);"></i>
                        <?php echo htmlspecialchars($task['project_name']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Project Status</div>
                    <div class="info-value">
                        <i class="fas fa-circle" style="color: var(--success);"></i>
                        <?php echo htmlspecialchars(ucfirst($task['project_status'])); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Project Manager</div>
                    <div class="info-value">
                        <i class="fas fa-user-tie" style="color: var(--secondary);"></i>
                        <?php echo htmlspecialchars($task['creator_name']); ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
