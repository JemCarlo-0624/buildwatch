<?php
require_once(__DIR__ . '/../../config/db.php');
session_start();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & trim form data
    $role            = trim($_POST['role'] ?? '');
    $full_name       = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password= $_POST['confirm_password'] ?? '';
    $phone           = trim($_POST['phone'] ?? '');

    // Basic validation
    $errors = [];
    if (!$role) $errors[] = "Please select your role.";
    if (!$full_name) $errors[] = "Full name is required.";
    if (!$email) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (!$password) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "This email is already registered. Please use a different email.";
    }

    // Check if name already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
    $stmt->execute([$full_name]);
    if ($stmt->fetch()) {
        $errors[] = "This name is already taken. Please use a different name.";
    }
    // </CHANGE>

    if (!empty($errors)) {
        $_SESSION['error'] = implode(' ', $errors);
        $_SESSION['form_data'] = [
            'role' => $role,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone
        ];
        header("Location: sign_up.php");
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if `phone` column exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
    if ($columns) {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, phone)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $password_hash, $role, $phone]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $password_hash, $role]);
    }

    // Auto-login after registration
    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['name'] = $full_name;

    $_SESSION['success'] = "Registration successful! Welcome to BuildWatch.";

    // Redirect to dashboard
    if ($role === 'pm') {
        header("Location: ../php/dashboard_pm.php");
    } else {
        header("Location: ../php/dashboard_worker.php");
    }
    exit;
} else {
    // Not a POST request
    header("Location: sign_up.php");
    exit;
}
