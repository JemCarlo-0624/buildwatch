<?php
require_once(__DIR__ . '/../../config/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array($_SESSION['role'], ['pm','admin'])) { header('Location: /login'); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { die("Invalid project ID"); }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];
    $priority = $_POST['priority'] ?? 'medium';
    $timeline = trim($_POST['timeline'] ?? '');
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $category = $_POST['category'] ?? '';

    $taskStmt = $pdo->prepare("SELECT AVG(progress) as avg_progress FROM tasks WHERE project_id = ?");
    $taskStmt->execute([$id]);
    $taskResult = $taskStmt->fetch();
    $completion_percentage = $taskResult['avg_progress'] ? round($taskResult['avg_progress']) : 0;

    $stmt = $pdo->prepare("
        UPDATE projects 
        SET name=?, description=?, status=?, priority=?, 
            timeline=?, start_date=?, end_date=?, category=?, 
            completion_percentage=?, last_activity_at=NOW()
        WHERE id=?
    ");
    $stmt->execute([
        $name, $desc, $status, $priority, $timeline, 
        $start_date, $end_date, $category, 
        $completion_percentage, $id
    ]);

    header("Location: projects_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-control, .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-group-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        @media (max-width: 768px) {
            .btn-group-actions {
                flex-direction: column;
            }

            .btn-group-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="sidebar-main-layout">

    <!-- Added sidebar navigation matching projects_list design -->
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
            <?php else: ?>
                <a href="dashboard_worker.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item active"><i class="fas fa-project-diagram"></i> My Projects</a>
                <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> My Tasks</a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?= strtoupper(substr($_SESSION['name'] ?? $_SESSION['username'] ?? 'U', 0, 2)) ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                    <small class="text-white-50"><?= htmlspecialchars($_SESSION['email'] ?? ucfirst($_SESSION['role'])) ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Redesigned main content area with modern card-based form -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Edit Project</h1>
                <p class="page-description">Update project information and status.</p>
            </div>
            <a href="projects_list.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>

        <div class="form-container">
            <div class="form-section-title">
                <i class="fas fa-edit"></i> Project Details
            </div>

            <form method="POST">
                <div class="mb-4">
                    <label for="name" class="form-label">
                        <i class="fas fa-project-diagram text-primary"></i> Project Name
                    </label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($project['name']) ?>" 
                        required
                        placeholder="Enter project name"
                    >
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left text-primary"></i> Description
                    </label>
                    <textarea 
                        class="form-control" 
                        id="description" 
                        name="description"
                        placeholder="Enter project description"
                    ><?= htmlspecialchars($project['description']) ?></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">
                            <i class="fas fa-calendar-alt text-primary"></i> Start Date
                        </label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($project['start_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">
                            <i class="fas fa-calendar-check text-primary"></i> End Date
                        </label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($project['end_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="timeline" class="form-label">
                            <i class="fas fa-hourglass-half text-primary"></i> Timeline Description
                        </label>
                        <input type="text" class="form-control" id="timeline" name="timeline" 
                               value="<?= htmlspecialchars($project['timeline'] ?? '') ?>" 
                               placeholder="e.g. 6 months, Q1 2024">
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label">
                            <i class="fas fa-tag text-primary"></i> Category
                        </label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Select category</option>
                            <option value="residential" <?= ($project['category'] ?? '') == 'residential' ? 'selected' : '' ?>>Residential</option>
                            <option value="commercial" <?= ($project['category'] ?? '') == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                            <option value="infrastructure" <?= ($project['category'] ?? '') == 'infrastructure' ? 'selected' : '' ?>>Infrastructure</option>
                            <option value="industrial" <?= ($project['category'] ?? '') == 'industrial' ? 'selected' : '' ?>>Industrial</option>
                            <option value="maintenance" <?= ($project['category'] ?? '') == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="priority" class="form-label">
                            <i class="fas fa-exclamation-circle text-primary"></i> Priority
                        </label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low" <?= ($project['priority'] ?? 'medium') == 'low' ? 'selected' : '' ?>>Low</option>
                            <option value="medium" <?= ($project['priority'] ?? 'medium') == 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="high" <?= ($project['priority'] ?? 'medium') == 'high' ? 'selected' : '' ?>>High</option>
                            <option value="urgent" <?= ($project['priority'] ?? 'medium') == 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            <i class="fas fa-info-circle text-primary"></i> Project Status
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="planning" <?= $project['status']=='planning'?'selected':'' ?>>Planning</option>
                            <option value="ongoing" <?= $project['status']=='ongoing'?'selected':'' ?>>Ongoing</option>
                            <option value="completed" <?= $project['status']=='completed'?'selected':'' ?>>Completed</option>
                            <option value="on-hold" <?= $project['status']=='on-hold'?'selected':'' ?>>On Hold</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4 p-3 bg-light rounded">
                    <label class="form-label">
                        <i class="fas fa-chart-line text-primary"></i> Project Completion
                    </label>
                    <p class="text-muted mb-0">
                        <small>Project completion is automatically calculated based on the progress of all assigned tasks. Update task progress to reflect project completion.</small>
                    </p>
                </div>

                <div class="btn-group-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Project
                    </button>
                    <a href="projects_list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
