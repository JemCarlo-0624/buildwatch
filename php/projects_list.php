<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin','worker'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    // Admins see all projects
    $stmt = $pdo->query("
        SELECT p.*, u.name as creator 
        FROM projects p 
        JOIN users u ON p.created_by = u.id 
        ORDER BY p.created_at DESC
    ");
    $projects = $stmt->fetchAll();
} else {
    // PMs and Workers see only their assigned projects
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as creator 
        FROM projects p 
        JOIN users u ON p.created_by = u.id
        JOIN project_assignments pa ON pa.project_id = p.id
        WHERE pa.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $projects = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects List - Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .project-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .project-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            background: white;
        }

        .project-card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .project-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .project-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray);
        }

        .meta-item i {
            width: 16px;
            color: var(--primary);
        }

        .project-actions {
            display: flex;
            gap: 8px;
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-on-hold { background-color: #f8d7da; color: #721c24; }

        .btn-danger {
            background-color: var(--accent);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background-color: #b8321a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .project-actions {
                flex-wrap: wrap;
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


    <div class="main-content">

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title"><?= $_SESSION['role']==='worker' ? 'My Projects' : ($_SESSION['role']==='pm' ? 'My Projects' : 'All Projects') ?></h1>
                <p class="page-description">
                    <?= $_SESSION['role']==='worker' ? 'View and manage your assigned projects.' : ($_SESSION['role']==='pm' ? 'View and manage your assigned projects.' : 'Manage all projects in the system.') ?>
                    <?php if (count($projects) > 0): ?>
                        <span class="positive">(<?= count($projects) ?> project<?= count($projects) !== 1 ? 's' : '' ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
            </div>
        </div>


        <?php if (count($projects) > 0): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $p): ?>
                    <div class="project-card">
                        <div class="project-card-header">
                            <h3 class="project-card-title"><?= htmlspecialchars($p['name']) ?></h3>
                            <span class="status-badge status-<?= strtolower($p['status']) ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <p class="project-description">
                                <?= htmlspecialchars($p['description']) ?>
                            </p>
                            
                            <div class="project-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>Project Manager: <?= htmlspecialchars($p['creator']) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('M j, Y', strtotime($p['created_at'])) ?></span>
                                </div>
                                <?php if (!empty($p['location'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($p['location']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($_SESSION['role'] !== 'worker'): ?>
                            <div class="project-actions">
                                <a href="projects_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="projects_assign.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-users"></i> Assign
                                </a>
                                <a href="projects_delete.php?id=<?= $p['id'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this project?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="project-actions">
                                <a href="project_view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="tasks_list.php?project=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-tasks"></i> View Tasks
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-project-diagram"></i>
                <h3>No Projects Found</h3>
                <p><?= $_SESSION['role']==='worker' ? 'You have no assigned projects yet.' : ($_SESSION['role']==='pm' ? 'You have no assigned projects yet.' : 'No projects have been created yet.') ?></p>
                <?php if ($_SESSION['role'] !== 'worker'): ?>
                    <a href="projects_create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Your First Project
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
