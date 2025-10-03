<?php
require_once("../config/db.php"); // Database connection
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

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already registered.";
    }

    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<p style='color:red;'>❌ " . htmlspecialchars($err) . "</p>";
        }
        echo "<p><a href='signup.php'>⬅ Back to Sign Up</a></p>";
        exit;
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if `phone` column exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
    if ($columns) {
        // `phone` exists → include it
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, phone)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$full_name, $email, $password_hash, $role, $phone]);
    } else {
        // `phone` does not exist → exclude it
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

    // Redirect to dashboard
    if ($role === 'pm') {
        header("Location: ../php/dashboard_pm.php");
    } else {
        header("Location: ../php/dashboard_worker.php");
    }
    exit;
} else {
    // Not a POST request
    header("Location: signup.php");
    exit;
}
