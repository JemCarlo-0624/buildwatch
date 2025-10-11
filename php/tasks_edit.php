<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Authentication check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Get task ID from URL
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header("Location: tasks_list.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.*, p.name AS project_name, u.name AS worker_name
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        header("Location: tasks_list.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Task fetch error: " . $e->getMessage());
    die("Error loading task details.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id  = $_POST['project_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'];
    $due_date    = $_POST['due_date'];
    $progress    = min(100, max(0, (int)($_POST['progress'] ?? 0)));

    // Server-side validation
    if (empty($title)) {
        $error = "Task title is required.";
    } elseif (empty($project_id)) {
        $error = "Please select a project.";
    } elseif (empty($assigned_to)) {
        $error = "Please assign the task to a worker.";
    } elseif (empty($due_date)) {
        $error = "Due date is required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET project_id = ?, title = ?, description = ?, assigned_to = ?, 
                    due_date = ?, progress = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $project_id, $title, $description, $assigned_to, 
                $due_date, $progress, $task_id
            ]);

            $success = "Task updated successfully!";
            
            // Refresh task data
            $stmt = $pdo->prepare("
                SELECT t.*, p.name AS project_name, u.name AS worker_name
                FROM tasks t
                JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.id = ?
            ");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch();
            
            // Redirect after 1 second
            header("refresh:1;url=tasks_list.php");
        } catch (PDOException $e) {
            error_log("Task update error: " . $e->getMessage());
            $error = "Error updating task. Please try again.";
        }
    }
}

// Fetch projects for dropdown
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

// Fetch workers for dropdown
$workers = $pdo->query("SELECT * FROM users WHERE role='worker'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - BuildWatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .progress-slider-container {
            margin-top: 10px;
        }
        
        .progress-value-display {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary);
            margin-top: 8px;
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
            <?php endif; ?>
            <a href="proposals_review.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Proposals
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <small class="text-white-50"><?php echo ucfirst($_SESSION['role']); ?></small>
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
                <h1 class="page-title">Edit Task</h1>
                <p class="page-description">Update task information and track progress</p>
            </div>
            <div class="d-flex gap-2">
                <a href="tasks_list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Tasks
                </a>
                <a href="tasks_details.php?id=<?php echo $task_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eye"></i> View Details
                </a>
            </div>
        </div>

        <div class="form-container">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="project_id" class="form-label">
                        Project <span style="color: var(--accent);">*</span>
                    </label>
                    <select name="project_id" id="project_id" class="form-input" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($task['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">
                        Task Title <span style="color: var(--accent);">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        id="title" 
                        class="form-input"
                        placeholder="Enter task title" 
                        value="<?= htmlspecialchars($task['title']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        Description
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        class="form-input"
                        placeholder="Enter task description"
                        rows="4"
                    ><?= htmlspecialchars($task['description']) ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="assigned_to" class="form-label">
                            Assign To <span style="color: var(--accent);">*</span>
                        </label>
                        <select name="assigned_to" id="assigned_to" class="form-input" required>
                            <option value="">Select a worker</option>
                            <?php foreach ($workers as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= ($task['assigned_to'] == $w['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($w['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="due_date" class="form-label">
                            Due Date <span style="color: var(--accent);">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="due_date" 
                            id="due_date" 
                            class="form-input"
                            value="<?= htmlspecialchars($task['due_date']) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="progress" class="form-label">
                        Progress <span style="color: var(--accent);">*</span>
                    </label>
                    <div class="progress-slider-container">
                        <input 
                            type="range" 
                            name="progress" 
                            id="progress" 
                            class="form-range"
                            min="0" 
                            max="100" 
                            step="5"
                            value="<?= (int)($task['progress'] ?? 0) ?>"
                            oninput="updateProgressDisplay(this.value)"
                        >
                        <div class="progress-value-display" id="progressDisplay">
                            <?= (int)($task['progress'] ?? 0) ?>%
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Task
                    </button>
                    <a href="tasks_list.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateProgressDisplay(value) {
            document.getElementById('progressDisplay').textContent = value + '%';
        }
    </script>
</body>
</html>
