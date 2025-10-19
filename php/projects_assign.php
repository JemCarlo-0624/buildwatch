<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) die("❌ Project ID missing");

$projectStmt = $pdo->prepare("SELECT p.*, u.name as creator FROM projects p JOIN users u ON p.created_by = u.id WHERE p.id = ?");
$projectStmt->execute([$project_id]);
$project = $projectStmt->fetch();

if (!$project) {
    die("❌ Project not found");
}

if ($_SESSION['role'] === 'pm') {
    $pmCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM project_assignments WHERE project_id = ? AND user_id = ?");
    $pmCheckStmt->execute([$project_id, $_SESSION['user_id']]);
    $pmAssignment = $pmCheckStmt->fetch();
    
    if ($pmAssignment['count'] == 0) {
        die("❌ You don't have permission to assign workers to this project. You must be assigned to the project first.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];

    // Check if user is already assigned to an active (not completed) project
    $checkStmt = $pdo->prepare("SELECT pa.project_id FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE pa.user_id = ? AND p.status != 'completed'");
    $checkStmt->execute([$user_id]);
    if ($checkStmt->fetch()) {
        echo "<script>alert('This user is already assigned to an active project.');window.location.href=window.location.href;</script>";
        exit;
    }

    // Assign user to project
    $stmt = $pdo->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
    $stmt->execute([$project_id, $user_id]);
    // Redirect to avoid form resubmission
    header("Location: projects_assign.php?id=" . urlencode($project_id));
    exit;
}

// Get available Project Managers (not assigned to any active project)
$pms = $pdo->query("SELECT * FROM users u WHERE u.role = 'pm' AND u.id NOT IN (SELECT pa.user_id FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE p.status != 'completed')")->fetchAll();

// Get available Workers (not assigned to any active project)
$workers = $pdo->query("SELECT * FROM users u WHERE u.role = 'worker' AND u.id NOT IN (SELECT pa.user_id FROM project_assignments pa JOIN projects p ON pa.project_id = p.id WHERE p.status != 'completed')")->fetchAll();

$stmt = $pdo->prepare("SELECT u.* FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id=? AND u.role='pm'");
$stmt->execute([$project_id]);
$assignedPMs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT u.* FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id=? AND u.role='worker'");
$stmt->execute([$project_id]);
$assignedWorkers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Team - <?= htmlspecialchars($project['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .assignment-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .project-info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--secondary);
        }

        .project-info-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .project-info-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .project-info-meta {
            display: flex;
            gap: 20px;
            color: var(--gray);
            font-size: 14px;
            margin-top: 10px;
        }

        .project-info-meta i {
            color: var(--primary);
            margin-right: 5px;
        }

        .assignment-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
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
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Updated section title colors for PM and Worker sections */
        .section-title.pm-section i {
            color: var(--secondary);
        }

        .section-title.worker-section i {
            color: var(--success);
        }

        .worker-select-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .form-group-inline {
            flex: 1;
        }

        .form-group-inline label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group-inline select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group-inline select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 99, 165, 0.1);
        }

        .assigned-workers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .worker-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .worker-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        /* Different avatar colors for PMs and Workers */
        .worker-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
        }

        .worker-avatar.pm {
            background: linear-gradient(135deg, var(--secondary), #1976d2);
        }

        .worker-avatar.worker {
            background: linear-gradient(135deg, var(--success), #27ae60);
        }

        .worker-info {
            flex: 1;
        }

        .worker-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
            font-size: 15px;
        }

        .worker-email {
            color: var(--gray);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .worker-email i {
            font-size: 11px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 64px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            font-size: 14px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-link:hover {
            gap: 12px;
            color: #084980;
        }

        @media (max-width: 768px) {
            .worker-select-form {
                flex-direction: column;
                align-items: stretch;
            }

            .assigned-workers-grid {
                grid-template-columns: 1fr;
            }

            .project-info-header {
                flex-direction: column;
                gap: 15px;
            }

            .project-info-meta {
                flex-direction: column;
                gap: 10px;
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
    <div class="main-content">
        <a href="projects_list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Projects
        </a>

        <div class="assignment-container">
            <div class="project-info-card">
                <div class="project-info-header">
                    <div>
                        <h2 class="project-info-title"><?= htmlspecialchars($project['name']) ?></h2>
                        <div class="project-info-meta">
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($project['creator']) ?></span>
                            <span><i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($project['created_at'])) ?></span>
                            <?php if (!empty($project['location'])): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($project['location']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?= strtolower($project['status']) ?>">
                        <?= ucfirst($project['status']) ?>
                    </span>
                </div>
                <?php if (!empty($project['description'])): ?>
                    <p class="project-description" style="margin-top: 15px; margin-bottom: 0; color: var(--gray);">
                        <?= htmlspecialchars($project['description']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Separate section for assigning Workers -->
            <div class="assignment-section">
                <div class="section-header">
                    <h3 class="section-title worker-section">
                        <i class="fas fa-user-hard-hat"></i>
                        Assign Worker
                    </h3>
                </div>

                <form method="post" class="worker-select-form">
                    <div class="form-group-inline">
                        <label for="worker-select">
                            <i class="fas fa-users"></i> Select Worker
                        </label>
                        <select name="user_id" id="worker-select" required>
                            <option value="">Choose a worker...</option>
                            <?php foreach ($workers as $w): ?>
                                <option value="<?= $w['id'] ?>">
                                    <?= htmlspecialchars($w['name']) ?> - <?= htmlspecialchars($w['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Assign Worker
                    </button>
                </form>
            </div>

            <!-- Separate display section for assigned Project Managers -->
            <div class="assignment-section">
                <div class="section-header">
                    <h3 class="section-title pm-section">
                        <i class="fas fa-user-tie"></i>
                        Assigned Project Manager
                        <span class="badge bg-secondary rounded-pill" style="font-size: 14px; margin-left: 10px;">
                            <?= count($assignedPMs) ?>
                        </span>
                    </h3>
                </div>

                <?php if (count($assignedPMs) > 0): ?>
                    <div class="assigned-workers-grid">
                        <?php foreach ($assignedPMs as $pm): ?>
                            <div class="worker-card">
                                <div class="worker-avatar pm">
                                    <?= strtoupper(substr($pm['name'], 0, 2)) ?>
                                </div>
                                <div class="worker-info">
                                    <div class="worker-name"><?= htmlspecialchars($pm['name']) ?></div>
                                    <div class="worker-email">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($pm['email']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-tie"></i>
                        <h4>No Project Managers Assigned Yet</h4>
                        <p>Use the form above to assign project managers to this project.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Separate display section for assigned Workers -->
            <div class="assignment-section">
                <div class="section-header">
                    <h3 class="section-title worker-section">
                        <i class="fas fa-user-hard-hat"></i>
                        Assigned Workers
                        <span class="badge bg-success rounded-pill" style="font-size: 14px; margin-left: 10px;">
                            <?= count($assignedWorkers) ?>
                        </span>
                    </h3>
                </div>

                <?php if (count($assignedWorkers) > 0): ?>
                    <div class="assigned-workers-grid">
                        <?php foreach ($assignedWorkers as $worker): ?>
                            <div class="worker-card">
                                <div class="worker-avatar worker">
                                    <?= strtoupper(substr($worker['name'], 0, 2)) ?>
                                </div>
                                <div class="worker-info">
                                    <div class="worker-name"><?= htmlspecialchars($worker['name']) ?></div>
                                    <div class="worker-email">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($worker['email']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-hard-hat"></i>
                        <h4>No Workers Assigned Yet</h4>
                        <p>Use the form above to assign workers to this project.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
