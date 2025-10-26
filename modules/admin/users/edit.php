<?php
require_once(__DIR__ . '/../../config/db.php');
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SESSION['role'] !== 'admin') { header('Location: /login'); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { die("Invalid user ID"); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) { die("User not found"); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($role)) {
        $error = "Name, email, and role are required.";
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $error = "Email is already in use by another user.";
        } else {
            // Update user
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $hashedPassword, $id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $id]);
            }

            header("Location: users_list.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            max-width: 600px;
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
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 99, 165, 0.1);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 16px;
            font-size: 14px;
        }

        .btn-group-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .form-text {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
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
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
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
        <div class="mb-4">
            <a href="users_list.php" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <h1 class="page-title">Edit User</h1>
            <p class="page-description">Update user information and permissions</p>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i> Role
                    </label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="pm" <?= $user['role'] === 'pm' ? 'selected' : '' ?>>Project Manager</option>
                        <option value="worker" <?= $user['role'] === 'worker' ? 'selected' : '' ?>>Worker</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> New Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Leave blank to keep current password">
                    <div class="form-text">Only fill this if you want to change the password</div>
                </div>

                <div class="btn-group-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="users_list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
