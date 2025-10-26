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
$stmt = $pdo->prepare("SELECT t.*, p.name as project_name FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.assigned_to = ? ORDER BY t.due_date ASC");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $progress = min(100, max(0, (int)$_POST['progress'])); // clamp 0–100

    $stmt = $pdo->prepare("SELECT project_id, progress FROM tasks WHERE id=? AND assigned_to=?");
    $stmt->execute([$task_id, $user_id]);
    $taskData = $stmt->fetch();
    
    if ($taskData) {
        $project_id = $taskData['project_id'];
        $previousProgress = $taskData['progress'];
        
        $completedAt = null;
        if ($progress == 100 && $previousProgress != 100) {
            $completedAt = date('Y-m-d H:i:s');
        }
        
        // Update task progress and completed_at if applicable
        if ($completedAt) {
            $stmt = $pdo->prepare("UPDATE tasks SET progress=?, completed_at=? WHERE id=? AND assigned_to=?");
            $stmt->execute([$progress, $completedAt, $task_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tasks SET progress=? WHERE id=? AND assigned_to=?");
            $stmt->execute([$progress, $task_id, $user_id]);
        }
        
        // Only update last_activity_at timestamp
        $stmt = $pdo->prepare("
            UPDATE projects 
            SET last_activity_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$project_id]);
    }

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
        /* HCI-focused improvements: better visual hierarchy, accessibility, feedback, and error prevention */
        
        /* Filter controls for better task scanning and cognitive load reduction */
        .filter-controls {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-2xl);
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all var(--transition-normal);
            color: var(--gray);
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .search-box {
            position: relative;
            margin-bottom: var(--space-lg);
            max-width: 400px;
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: border-color var(--transition-normal);
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

        /* New card grid layout replacing timeline */
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-2xl);
        }

        .task-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: var(--radius-md);
            padding: var(--space-lg);
            transition: all var(--transition-normal);
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .task-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(10, 99, 165, 0.15);
            transform: translateY(-2px);
        }

        .task-card.completed {
            background: #f0f8f5;
            opacity: 0.9;
        }

        .task-card.overdue {
            border-left: 4px solid var(--accent);
            background: #fff5f5;
        }

        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .task-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            word-break: break-word;
            flex: 1;
        }

        .task-card-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .status-active {
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
        }

        .status-pending {
            background: rgba(173, 181, 189, 0.1);
            color: #6c757d;
        }

        .task-card-meta {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
            font-size: 0.85rem;
            color: var(--gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .task-card-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: var(--space-md);
            flex: 1;
        }

        .task-card-progress {
            margin-bottom: var(--space-md);
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .progress-bar-wrapper {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .task-card-alerts {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
        }

        .alert-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .alert-overdue {
            background: rgba(212, 47, 19, 0.1);
            color: var(--accent);
        }

        .alert-completed {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .task-card-actions {
            display: flex;
            gap: var(--space-sm);
            margin-top: auto;
        }

        .btn-mark-done {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            height: 36px;
            box-shadow: 0 2px 6px rgba(46, 204, 113, 0.25);
            white-space: nowrap;
            flex: 1;
        }

        .btn-mark-done:hover {
            background: #27ae60;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.35);
            transform: translateY(-1px);
        }

        .btn-mark-done:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(46, 204, 113, 0.25);
        }

        .btn-mark-done:focus {
            outline: 2px solid var(--success);
            outline-offset: 2px;
        }

        .btn-mark-done i {
            font-size: 14px;
        }

        .task-completed-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(46, 204, 113, 0.2);
            flex: 1;
            text-align: center;
        }

        .task-completed-badge i {
            font-size: 14px;
        }

        /* Remove old timeline styles */
        .timeline {
            display: none;
        }

        .timeline-item {
            display: none;
        }

        .timeline-item::before {
            display: none;
        }

        .timeline-time {
            display: none;
        }

        .timeline-overdue-badge {
            display: none;
        }

        .timeline-completed-at {
            display: none;
        }

        .timeline-status {
            display: none;
        }

        .timeline-status-badge {
            display: none;
        }

        .timeline-desc {
            display: none;
        }

        .timeline-meta {
            display: none;
        }

        .timeline-progress {
            display: none;
        }

        .timeline-actions {
            display: none;
        }

        .progress-update-form {
            display: none;
        }

        .progress-input {
            display: none;
        }

        .progress-input::-webkit-slider-thumb {
            display: none;
        }

        .progress-input::-moz-range-thumb {
            display: none;
        }

        .progress-value-display {
            display: none;
        }

        .btn-update {
            display: none;
        }

        /* Added centered modal styling */
        .completion-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .completion-modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--space-2xl);
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
        }

        .modal-description {
            color: var(--gray);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: var(--space-lg);
        }

        .modal-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-normal);
            min-width: 120px;
        }

        .btn-modal-cancel {
            background: #e0e0e0;
            color: var(--dark);
        }

        .btn-modal-cancel:hover {
            background: #d0d0d0;
        }

        .btn-modal-confirm {
            background: var(--success);
            color: white;
            box-shadow: 0 2px 6px rgba(46, 204, 113, 0.25);
        }

        .btn-modal-confirm:hover {
            background: #27ae60;
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.35);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Improved accessibility and responsive design */
        @media (max-width: 768px) {
            .timeline-container {
                padding: var(--space-lg);
            }

            /* Responsive card grid for mobile */
            .tasks-grid {
                grid-template-columns: 1fr;
                gap: var(--space-md);
            }

            .filter-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .filter-btn {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                padding: var(--space-lg);
            }
        }

        /* Keyboard focus indicators for accessibility */
        .filter-btn:focus,
        .btn-mark-done:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Reduced motion support for accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
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
        <!-- Page Header -->
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

        <!-- Card grid layout -->
        <div class="tasks-grid" id="tasksTimeline">
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $task): ?>
                    <?php 
                    // Determine task status for card
                    $progress = (int)$task['progress'];
                    $isCompleted = $progress == 100;
                    $isActive = $progress > 0 && $progress < 100;
                    $isPending = $progress == 0;
                    
                    // Check if overdue
                    $isOverdue = false;
                    if (!empty($task['due_date']) && strtotime($task['due_date']) < time() && $progress < 100) {
                        $isOverdue = true;
                    }
                    
                    $statusClass = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ($isActive ? 'active' : 'pending'));
                    $filterClass = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : ($isActive ? 'active' : 'pending'));
                    $statusBadgeClass = $isCompleted ? 'status-completed' : ($isActive ? 'status-active' : 'status-pending');
                    $statusText = $isCompleted ? 'Completed' : ($isActive ? 'In Progress' : 'Pending');
                    
                    // Format time display
                    $timeDisplay = '';
                    if (!empty($task['due_date'])) {
                        $dueDate = strtotime($task['due_date']);
                        $today = strtotime('today');
                        $daysUntilDue = floor(($dueDate - $today) / 86400);
                        
                        if ($isCompleted) {
                            $timeDisplay = 'Completed - ' . date('M j, Y', $dueDate);
                        } elseif ($isOverdue) {
                            $timeDisplay = 'Overdue - ' . date('M j, Y', $dueDate);
                        } elseif ($daysUntilDue == 0) {
                            $timeDisplay = 'Due Today';
                        } else {
                            $timeDisplay = 'Due ' . date('M j, Y', $dueDate);
                        }
                    } else {
                        $timeDisplay = 'No due date';
                    }
                    
                    $progressColor = $progress == 100 ? 'var(--success)' : ($progress > 50 ? 'var(--primary)' : 'var(--warning)');
                    ?>
                    <div class="task-card <?php echo $statusClass; ?>" data-search="<?php echo htmlspecialchars(strtolower($task['title'] . ' ' . $task['project_name'])); ?>" data-filter="<?php echo $filterClass; ?>">
                        <div class="task-card-header">
                            <div class="task-card-title"><?php echo htmlspecialchars($task['title']); ?></div>
                            <span class="task-card-status-badge <?php echo $statusBadgeClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="task-card-meta">
                            <div class="meta-item">
                                <i class="fas fa-folder"></i>
                                <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $timeDisplay; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($task['description'])): ?>
                        <div class="task-card-description">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?><?php echo strlen($task['description']) > 100 ? '...' : ''; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="task-card-alerts">
                            <?php if ($isOverdue): ?>
                            <div class="alert-badge alert-overdue">
                                <i class="fas fa-clock"></i>
                                Overdue
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($isCompleted && !empty($task['completed_at'])): ?>
                            <div class="alert-badge alert-completed">
                                <i class="fas fa-check-circle"></i>
                                Completed on <?php echo date('M j, Y', strtotime($task['completed_at'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-card-progress">
                            <div class="progress-label">
                                <span>Progress</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>;"></div>
                            </div>
                        </div>
                        
                        <div class="task-card-actions">
                            <?php if (!$isCompleted): ?>
                            <form method="post" class="mark-done-form" style="width: 100%;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <input type="hidden" name="progress" value="100">
                                <button type="submit" class="btn-mark-done" aria-label="Mark task as done">
                                    <i class="fas fa-check"></i> Mark as Done
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="task-completed-badge">
                                <i class="fas fa-check-circle"></i> Completed
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <p>You have no tasks assigned yet</p>
                <a href="dashboard_worker.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Completion confirmation modal for error prevention -->
    <div class="completion-modal" id="completionModal">
        <div class="modal-content">
            <div class="modal-title">
                <i class="fas fa-check-circle" style="color: var(--success); margin-right: 8px;"></i>
                Mark Task as Complete?
            </div>
            <div class="modal-description">
                You're about to mark this task as 100% complete. This action will record a completion timestamp and the task will be locked from further edits.
            </div>
            <div class="modal-actions">
                <button class="btn-modal btn-modal-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-modal btn-modal-confirm" id="confirmBtn">Confirm Completion</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        let pendingForm = null;
        const completionModal = document.getElementById('completionModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmBtn');

        document.querySelectorAll('.mark-done-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                pendingForm = this;
                completionModal.classList.add('active');
            });
        });

        cancelBtn.addEventListener('click', function() {
            completionModal.classList.remove('active');
            pendingForm = null;
        });

        confirmBtn.addEventListener('click', function() {
            if (pendingForm) {
                completionModal.classList.remove('active');
                pendingForm.submit();
            }
        });

        // Close modal when clicking outside
        completionModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                pendingForm = null;
            }
        });

        const filterBtns = document.querySelectorAll('.filter-btn');
        const timelineItems = document.querySelectorAll('.task-card');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active state
                filterBtns.forEach(b => {
                    b.classList.remove('active');
                    b.setAttribute('aria-pressed', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');

                // Filter items
                timelineItems.forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-filter') === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const tasksGrid = document.getElementById('tasksTimeline');

        function filterTasks() {
            const searchTerm = searchInput.value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');

            tasksGrid.querySelectorAll('.task-card').forEach(card => {
                const searchData = card.getAttribute('data-search');
                const itemFilter = card.getAttribute('data-filter');
                
                const matchesSearch = !searchTerm || searchData.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || itemFilter === activeFilter;
                
                card.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTasks);

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && completionModal.classList.contains('active')) {
                completionModal.classList.remove('active');
                pendingForm = null;
            }
        });
    </script>
</body>
</html>
