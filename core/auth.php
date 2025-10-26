<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent caching so the browser won't show a cached page after logout
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

/**
 * Require a user role
 * @param string|array $roles Single role or array of allowed roles
 */
function requireRole($roles) {
    if (!isset($_SESSION['role'])) {
        header('Location: /login');
        exit;
    }

    if (is_array($roles)) {
        // If array, check if user's role is in allowed roles
        if (!in_array($_SESSION['role'], $roles)) {
            header('Location: /login');
            exit;
        }
    } else {
        // Single role string
        if ($_SESSION['role'] !== $roles) {
            header('Location: /login');
            exit;
        }
    }
}
