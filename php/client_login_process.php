<?php
session_start();
require_once("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: client_login.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && password_verify($password, $client['password'])) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_name'] = $client['name'];
            $_SESSION['client_email'] = $client['email'];
            header('Location: client_dashboard.php');
            exit;
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: client_login.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again.';
        header('Location: client_login.php');
        exit;
    }
} else {
    header('Location: client_login.php');
    exit;
}
?>
