<?php
/**
 * Router Class for BuildWatch
 * Handles URL routing and role-based access control
 */

class Router {
    private $routes = [];
    private $middleware = [];
    
    // Base directory for modules
    private $basePath = __DIR__ . '/../';
    
    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set up routes
        $this->registerRoutes();
    }
    
    /**
     * Register all application routes
     */
    private function registerRoutes() {
        // Public routes (no auth required)
        $this->addRoute('GET', '/', 'public/frontpage.php', false);
        $this->addRoute('GET', '/login', 'auth/login.php', false);
        $this->addRoute('POST', '/login', 'auth/login_process.php', false);
        $this->addRoute('GET', '/signup', 'auth/signup.php', false);
        $this->addRoute('POST', '/signup', 'auth/signup_process.php', false);
        $this->addRoute('GET', '/logout', 'auth/logout.php');
        $this->addRoute('GET', '/client/login', 'auth/client_login.php', false);
        $this->addRoute('POST', '/client/login', 'auth/client_login_process.php', false);
        $this->addRoute('GET', '/client/signup', 'auth/client_signup.php', false);
        $this->addRoute('POST', '/client/signup', 'auth/client_signup_process.php', false);
        $this->addRoute('GET', '/client/logout', 'auth/client_logout.php');
        
        // Client routes
        $this->addRoute('GET', '/client/dashboard', 'modules/client/dashboard.php', true, 'client');
        $this->addRoute('GET', '/client/budget-review', 'modules/client/budget_review.php', true, 'client');
        $this->addRoute('GET', '/client/project/:id', 'modules/client/project_details.php', true, 'client');
        $this->addRoute('GET', '/client/proposal/:id', 'modules/client/proposal_details.php', true, 'client');
        $this->addRoute('GET', '/client/submit-proposal', 'modules/client/submit_proposal.php', true, 'client');
        
        // Admin routes
        $this->addRoute('GET', '/admin/dashboard', 'modules/admin/dashboard.php', true, 'admin');
        $this->addRoute('GET', '/admin/approve-budget', 'modules/admin/approve_budget.php', true, 'admin');
        $this->addRoute('GET', '/admin/review-budget', 'modules/admin/review_budget.php', true, 'admin');
        $this->addRoute('GET', '/admin/users', 'modules/admin/users/list.php', true, 'admin');
        $this->addRoute('GET', '/admin/users/create', 'modules/admin/users/create.php', true, 'admin');
        $this->addRoute('GET', '/admin/users/edit/:id', 'modules/admin/users/edit.php', true, 'admin');
        $this->addRoute('GET', '/admin/users/delete/:id', 'modules/admin/users/delete.php', true, 'admin');
        
        // PM routes
        $this->addRoute('GET', '/pm/dashboard', 'modules/pm/dashboard.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects', 'modules/pm/projects/list.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects/create', 'modules/pm/projects/create.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects/edit/:id', 'modules/pm/projects/edit.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects/delete/:id', 'modules/pm/projects/delete.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects/details/:id', 'modules/pm/projects/details.php', true, 'pm');
        $this->addRoute('GET', '/pm/projects/assign/:id', 'modules/pm/projects/assign.php', true, 'pm');
        
        // Worker routes
        $this->addRoute('GET', '/worker/dashboard', 'modules/worker/dashboard.php', true, 'worker');
        $this->addRoute('GET', '/worker/projects', 'modules/worker/projects.php', true, 'worker');
        $this->addRoute('GET', '/worker/tasks', 'modules/worker/tasks.php', true, 'worker');
        
        // Shared routes (require auth but multiple roles can access)
        $this->addRoute('GET', '/tasks', 'modules/shared/tasks/list.php', true, ['pm', 'admin']);
        $this->addRoute('GET', '/tasks/create', 'modules/shared/tasks/create.php', true, ['pm', 'admin']);
        $this->addRoute('GET', '/tasks/edit/:id', 'modules/shared/tasks/edit.php', true, ['pm', 'admin']);
        $this->addRoute('GET', '/tasks/details/:id', 'modules/shared/tasks/details.php', true, ['pm', 'admin']);
        
        $this->addRoute('GET', '/proposals/review', 'modules/shared/proposals/review.php', true, 'admin');
        $this->addRoute('GET', '/proposals/submit', 'modules/shared/proposals/submit.php', false);
        $this->addRoute('GET', '/proposals/details/:id', 'modules/shared/proposals/details.php', true, 'admin');
        
        $this->addRoute('GET', '/reports/generate', 'modules/shared/reports/generate.php', true, ['admin', 'pm']);
        
        // API routes
        $this->addRoute('GET', '/api/client/notifications', 'api/notifications/fetch_client_notifications.php', true, 'client');
        $this->addRoute('GET', '/api/client/projects', 'api/client/fetch_projects.php', true, 'client');
        $this->addRoute('GET', '/api/client/proposals', 'api/client/fetch_proposals.php', true, 'client');
        $this->addRoute('GET', '/api/client/project-details', 'api/client/fetch_project_details.php', true, 'client');
        
        $this->addRoute('GET', '/api/budget/breakdown', 'api/budget/fetch_breakdown.php');
        $this->addRoute('GET', '/api/budget/details', 'api/budget/fetch_details.php');
        $this->addRoute('GET', '/api/budget/review', 'api/budget/fetch_review.php');
        $this->addRoute('POST', '/api/budget/decision', 'api/budget/process_decision.php');
        
        $this->addRoute('GET', '/api/projects/workers', 'api/projects/get_workers.php');
        $this->addRoute('POST', '/api/notifications/mark-read', 'api/notifications/mark_read.php');
    }
    
    /**
     * Add a route to the router
     */
    private function addRoute($method, $path, $handler, $requireAuth = true, $allowedRoles = null) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'requireAuth' => $requireAuth,
            'allowedRoles' => $allowedRoles
        ];
    }
    
    /**
     * Dispatch the request to the appropriate handler
     */
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertPathToPattern($route['path']);
            if (preg_match($pattern, $uri, $matches)) {
                // Extract dynamic parameters
                $params = array_slice($matches, 1);
                
                // Check authentication
                if ($route['requireAuth']) {
                    if (!isset($_SESSION['user_id'])) {
                        // Redirect based on URI pattern
                        if (strpos($uri, '/client/') === 0) {
                            header('Location: /client/login');
                        } else {
                            header('Location: /login');
                        }
                        exit;
                    }
                    
                    // Check role authorization
                    if ($route['allowedRoles'] !== null) {
                        $userRole = $_SESSION['role'] ?? null;
                        $allowedRoles = is_array($route['allowedRoles']) ? $route['allowedRoles'] : [$route['allowedRoles']];
                        
                        if ($userRole === null || !in_array($userRole, $allowedRoles)) {
                            http_response_code(403);
                            die('Access Denied: Insufficient permissions');
                        }
                    }
                }
                
                // Load the handler file
                $handlerPath = $this->basePath . $route['handler'];
                
                // Extract params for use in handler
                $this->extractParams($route['path'], $uri, $matches);
                
                if (file_exists($handlerPath)) {
                    require $handlerPath;
                } else {
                    http_response_code(404);
                    die('Handler not found: ' . $route['handler']);
                }
                
                return;
            }
        }
        
        // No route matched
        http_response_code(404);
        die('Route not found: ' . $uri);
    }
    
    /**
     * Convert path pattern to regex
     */
    private function convertPathToPattern($path) {
        // Escape forward slashes
        $pattern = preg_quote($path, '/');
        
        // Replace :param with regex capture group
        $pattern = preg_replace('/\\\\:([a-zA-Z0-9_]+)/', '([^/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Extract parameters from URI and set them in global scope
     */
    private function extractParams($path, $uri, $matches) {
        // Parse the path to find named parameters
        $pathParts = explode('/', $path);
        $uriParts = explode('/', $uri);
        
        for ($i = 0; $i < count($pathParts); $i++) {
            if (isset($pathParts[$i]) && isset($uriParts[$i])) {
                if (strpos($pathParts[$i], ':') === 0) {
                    // This is a named parameter
                    $paramName = substr($pathParts[$i], 1);
                    $_GET[$paramName] = $uriParts[$i];
                }
            }
        }
    }
    
    /**
     * Get the current URI
     */
    private function getUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $uri = strtok($uri, '?');
        
        // Remove base path if in subdirectory
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strlen($basePath) > 0) {
            $uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
        }
        
        // Ensure URI starts with /
        if (strlen($uri) === 0 || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        return $uri;
    }
    
    /**
     * Generate URL for a route
     */
    public static function url($path, $params = []) {
        foreach ($params as $key => $value) {
            $path = str_replace(':' . $key, $value, $path);
        }
        return $path;
    }
}

