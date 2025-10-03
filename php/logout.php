<?php
// /php/logout.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Unset all session variables
$_SESSION = [];

// If session uses cookies, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Optional: regenerate the session id
if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
}

// Redirect to login using an absolute path (adjust if your project path differs)
header("Location: /buildwatch/php/login.php");
exit;
