<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) die("❌ Project ID missing");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
    $stmt->execute([$project_id, $user_id]);
}

$projectStmt = $pdo->prepare("SELECT p.*, u.name as creator FROM projects p JOIN users u ON p.created_by = u.id WHERE p.id = ?");
$projectStmt->execute([$project_id]);
$project = $projectStmt->fetch();

if (!$project) {
    die("❌ Project not found");
}

// Get all workers
$workers = $pdo->query("SELECT * FROM users WHERE role='worker'")->fetchAll();

// Get assigned users
$stmt = $pdo->prepare("SELECT u.* FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id=?");
$stmt->execute([$project_id]);
$assigned = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Workers - <?= htmlspecialchars($project['name']) ?></title>
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

        .worker-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
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
                <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
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

            <div class="assignment-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-user-plus"></i>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Assign Worker
                    </button>
                </form>
            </div>
            <div class="assignment-section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-users-cog"></i>
                        Assigned Workers
                        <span class="badge bg-primary rounded-pill" style="font-size: 14px; margin-left: 10px;">
                            <?= count($assigned) ?>
                        </span>
                    </h3>
                </div>

                <?php if (count($assigned) > 0): ?>
                    <div class="assigned-workers-grid">
                        <?php foreach ($assigned as $a): ?>
                            <div class="worker-card">
                                <div class="worker-avatar">
                                    <?= strtoupper(substr($a['name'], 0, 2)) ?>
                                </div>
                                <div class="worker-info">
                                    <div class="worker-name"><?= htmlspecialchars($a['name']) ?></div>
                                    <div class="worker-email">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($a['email']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
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
