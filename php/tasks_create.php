<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id  = $_POST['project_id'];
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'];
    $due_date    = $_POST['due_date'];

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
                INSERT INTO tasks (project_id, title, description, assigned_to, due_date) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$project_id, $title, $description, $assigned_to, $due_date]);

            header("Location: tasks_list.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error creating task. Please try again.";
        }
    }
}

// Fetch projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

// Fetch workers
$workers = $pdo->query("SELECT * FROM users WHERE role='worker'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Task - BuildWatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <h1 class="page-title">Create New Task</h1>
                <p class="page-description">Fill in the details to create a new task</p>
            </div>
            <div>
                <a href="tasks_list.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Tasks
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
                            <option value="<?= $p['id'] ?>" <?= (isset($_POST['project_id']) && $_POST['project_id'] == $p['id']) ? 'selected' : '' ?>>
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
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                    </label>
                    <textarea 
                        name="description" 
                        id="description" 
                        class="form-input"
                        placeholder="Enter task description"
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="assigned_to" class="form-label">
                            Assign To <span style="color: var(--accent);">*</span>
                        </label>
                        <select name="assigned_to" id="assigned_to" class="form-input" required>
                            <option value="">Select a worker</option>
                            <?php foreach ($workers as $w): ?>
                                <option value="<?= $w['id'] ?>" <?= (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $w['id']) ? 'selected' : '' ?>>
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
                            value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Task
                    </button>
                    <a href="tasks_list.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
