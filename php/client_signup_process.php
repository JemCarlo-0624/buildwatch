<?php
session_start();
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: client_signup.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: client_signup.php');
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters long.';
        header('Location: client_signup.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: client_signup.php');
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'An account with this email already exists.';
            header('Location: client_signup.php');
            exit;
        }

        // Create new client account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO clients (name, email, phone, company, password, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $email, $phone, $company, $hashed_password]);

        $_SESSION['success'] = 'Account created successfully! Please sign in.';
        header('Location: client_login.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = 'An error occurred. Please try again.';
        header('Location: client_signup.php');
        exit;
    }
} else {
    header('Location: client_signup.php');
    exit;
}
