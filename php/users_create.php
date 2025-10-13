<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// Allow only admins
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered. Please use a different email.";
        } else {
            // Create the user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                header("Location: users_list.php?success=user_created");
                exit;
            } else {
                $error = "Failed to create user. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - BuildWatch</title>
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

        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .password-strength-weak { background: #dc3545; width: 33%; }
        .password-strength-medium { background: #ffc107; width: 66%; }
        .password-strength-strong { background: #28a745; width: 100%; }

        .role-description {
            font-size: 12px;
            color: var(--gray);
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-top: 10px;
        }

        .role-description strong {
            color: var(--dark);
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
            <h1 class="page-title">Create New User</h1>
            <p class="page-description">Add a new user to the system with appropriate role and permissions</p>
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

            <form method="POST" id="createUserForm">
                <div class="mb-3">
                    <label for="name" class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                           placeholder="Enter full name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                           placeholder="user@example.com" required>
                    <div class="form-text">This will be used for login credentials</div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i> Role
                    </label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Select a role...</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                        <option value="pm" <?= ($_POST['role'] ?? '') === 'pm' ? 'selected' : '' ?>>Project Manager</option>
                        <option value="worker" <?= ($_POST['role'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                    </select>
                    <div class="role-description" id="roleDescription" style="display: none;"></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter password (min. 6 characters)" required>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" 
                                id="togglePassword" style="text-decoration: none; color: #6c757d; z-index: 10;">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="form-text" id="strengthText">Password strength: <span id="strengthLabel">Not set</span></div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter password" required>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" 
                                id="toggleConfirmPassword" style="text-decoration: none; color: #6c757d; z-index: 10;">
                            <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="form-text" id="matchText"></div>
                </div>

                <div class="btn-group-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Create User
                    </button>
                    <a href="users_list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = document.getElementById('toggleConfirmPasswordIcon');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Role descriptions
        const roleDescriptions = {
            'admin': '<strong>Administrator:</strong> Full system access. Can manage users, projects, tasks, and all system settings.',
            'pm': '<strong>Project Manager:</strong> Can create and manage projects, assign workers, and oversee project progress.',
            'worker': '<strong>Worker:</strong> Can view assigned projects, update task progress, and submit proposals. Can only be assigned to one active project at a time.'
        };

        // Show role description when role is selected
        document.getElementById('role').addEventListener('change', function() {
            const roleDesc = document.getElementById('roleDescription');
            if (this.value && roleDescriptions[this.value]) {
                roleDesc.innerHTML = roleDescriptions[this.value];
                roleDesc.style.display = 'block';
            } else {
                roleDesc.style.display = 'none';
            }
        });

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthLabel = document.getElementById('strengthLabel');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('password-strength-weak');
                strengthLabel.textContent = 'Weak';
                strengthLabel.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('password-strength-medium');
                strengthLabel.textContent = 'Medium';
                strengthLabel.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('password-strength-strong');
                strengthLabel.textContent = 'Strong';
                strengthLabel.style.color = '#28a745';
            }
        });

        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchText.innerHTML = '<i class="fas fa-check-circle" style="color: #28a745;"></i> Passwords match';
                    matchText.style.color = '#28a745';
                } else {
                    matchText.innerHTML = '<i class="fas fa-times-circle" style="color: #dc3545;"></i> Passwords do not match';
                    matchText.style.color = '#dc3545';
                }
            } else {
                matchText.textContent = '';
            }
        });

        // Form validation
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
    </script>
</body>
</html>
