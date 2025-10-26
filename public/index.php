<?php
/**
 * Main Entry Point - BuildWatch Router
 * All requests are routed through this file
 */

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('MODULES_PATH', BASE_PATH . '/modules');
define('CORE_PATH', BASE_PATH . '/core');
define('API_PATH', BASE_PATH . '/api');
define('AUTH_PATH', BASE_PATH . '/auth');
define('CONFIG_PATH', BASE_PATH . '/config');

// Set error reporting
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php_error.log');
error_reporting(E_ALL);

// Load router
require_once CORE_PATH . '/Router.php';

// Initialize and dispatch the router
$router = new Router();
$router->dispatch();
?>
