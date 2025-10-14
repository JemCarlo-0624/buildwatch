<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Updated styles to match dashboard design */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 20px 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            background: white;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: #f8f9fa;
        }

        .users-table th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f0f0f0;
        }

        .users-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f8f9fa;
            color: var(--dark);
        }

        .users-table tbody tr {
            transition: background 0.2s;
        }

        .users-table tbody tr:hover {
            background: #f8f9fa;
        }

        .users-table tbody tr:last-child td {
            border-bottom: none;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-badge.admin {
            background: rgba(212, 47, 19, 0.1);
            color: var(--accent);
        }

        .role-badge.pm {
            background: rgba(10, 99, 165, 0.1);
            color: var(--primary);
        }

        .role-badge.worker {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Stats container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .stat-icon-primary { background: rgba(10, 99, 165, 0.1); color: var(--primary); }
        .stat-icon-success { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .stat-icon-accent { background: rgba(212, 47, 19, 0.1); color: var(--accent); }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
            color: var(--dark);
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body class="sidebar-main-layout">

    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Admin Panel</div>
            <a href="dashboard_admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
            <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
            <a href="proposals_review.php" class="nav-item"><i class="fas fa-lightbulb"></i> Proposals</a>
            <a href="users_list.php" class="nav-item active"><i class="fas fa-users"></i> Users</a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    AD
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@example.com'); ?></small>
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
                <h1 class="page-title">User Management</h1>
                <p class="page-description">Manage system users and their roles</p>
            </div>
            <a href="users_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add User
            </a>
        </div>
 
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo count($users); ?></div>
                <div class="stat-label">TOTAL USERS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-user-shield"></i></div>
                <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></div>
                <div class="stat-label">ADMINISTRATORS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-user-tie"></i></div>
                <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'pm')); ?></div>
                <div class="stat-label">PROJECT MANAGERS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-user-hard-hat"></i></div>
                <div class="stat-value"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'worker')); ?></div>
                <div class="stat-label">WORKERS</div>
            </div>
        </div>

        <div class="card">
            <?php if (count($users) > 0): ?>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['id']) ?></td>
                                <td><?= htmlspecialchars($u['name']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <span class="role-badge <?= htmlspecialchars($u['role']) ?>">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="users_edit.php?id=<?= $u['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="users_delete.php?id=<?= $u['id'] ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>Get started by adding your first user.</p>
                    <a href="users_create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
