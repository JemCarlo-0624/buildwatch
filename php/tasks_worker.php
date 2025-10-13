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
$stmt = $pdo->prepare("
    SELECT t.*, p.name as project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.assigned_to = ?
    ORDER BY t.due_date ASC
");
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

// Handle progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $progress = min(100, max(0, (int)$_POST['progress'])); // clamp 0–100

    $stmt = $pdo->prepare("UPDATE tasks SET progress=? WHERE id=? AND assigned_to=?");
    $stmt->execute([$progress, $task_id, $user_id]);

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
        /* Added formal table styling matching the existing design system */
        .tasks-table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
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
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
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
            color: var(--dark);
            border-bottom: 1px solid #ddd;
        }

        .tasks-table td {
            padding: 15px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .tasks-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .task-title-cell {
            font-weight: 600;
            color: var(--dark);
        }

        .task-description {
            font-size: 13px;
            color: var(--gray);
            margin-top: 4px;
        }

        .project-badge {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
            border-radius: 4px;
            font-size: 13px;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar-wrapper {
            flex: 1;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            overflow: hidden;
            min-width: 80px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
        }

        .progress-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            min-width: 40px;
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

        .progress-update-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-input {
            width: 120px;
            height: 6px;
            -webkit-appearance: none;
            appearance: none;
            background: #ddd;
            border-radius: 3px;
            outline: none;
            cursor: pointer;
        }

        .progress-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--primary);
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s;
        }

        .progress-input::-webkit-slider-thumb:hover {
            background: #084a7d;
        }

        .progress-input::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: var(--primary);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.2s;
        }

        .progress-input::-moz-range-thumb:hover {
            background: #084a7d;
        }

        .progress-value-display {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            min-width: 35px;
        }

        .btn-update {
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-update:hover {
            background: #084a7d;
        }

        @media (max-width: 768px) {
            .tasks-table-container {
                overflow-x: auto;
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
         Page Header 
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

        <div class="tasks-table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search tasks...">
                </div>
            </div>

            <?php if (!empty($tasks)): ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Project</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>Update Progress</th>
                    </tr>
                </thead>
                <tbody id="tasksTableBody">
                    <?php foreach ($tasks as $task): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'])); ?>">
                        <td class="task-title-cell">
                            <div><?php echo htmlspecialchars($task['title']); ?></div>
                            <?php if (!empty($task['description'])): ?>
                            <div class="task-description"><?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?><?php echo strlen($task['description']) > 100 ? '...' : ''; ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="project-badge">
                                <?php echo htmlspecialchars($task['project_name']); ?>
                            </span>
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
                                
                                if ($task['progress'] < 100) {
                                    if ($daysUntilDue < 0) {
                                        $dateClass = 'due-date-overdue';
                                    } elseif ($daysUntilDue <= 3) {
                                        $dateClass = 'due-date-soon';
                                    }
                                }
                                
                                echo '<span class="' . $dateClass . '">';
                                echo date('M j, Y', $dueDate);
                                echo '</span>';
                            } else {
                                echo '<span class="text-muted">No due date</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="post" class="progress-update-form">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <!-- Changed from number input to range slider with value display -->
                                <input 
                                    type="range" 
                                    name="progress" 
                                    value="<?php echo $task['progress']; ?>" 
                                    min="0" 
                                    max="100" 
                                    step="5"
                                    class="progress-input" 
                                    data-task-id="<?php echo $task['id']; ?>"
                                    required
                                >
                                <span class="progress-value-display" id="progress-display-<?php echo $task['id']; ?>">
                                    <?php echo $task['progress']; ?>%
                                </span>
                                <button type="submit" class="btn-update">
                                    <i class="fas fa-save"></i> Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center p-4">
                <i class="fas fa-tasks fs-1 text-muted mb-3"></i>
                <p>You have no tasks assigned yet</p>
                <a href="dashboard_worker.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <?php endif; ?>
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

        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tasksTableBody');
        const rows = tableBody ? tableBody.getElementsByTagName('tr') : [];

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let row of rows) {
                const searchData = row.getAttribute('data-search');
                const showRow = !searchTerm || searchData.includes(searchTerm);
                row.style.display = showRow ? '' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);
    </script>
</body>
</html>
