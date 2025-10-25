<?php
/**
 * Unified Layout Template for BuildWatch
 * Provides consistent navigation, sidebar, and styling across all dashboards
 * Supports role-based menu visibility
 */

// Ensure auth is checked before layout is used
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Define role-based menu items
$menuConfig = [
    'admin' => [
        ['icon' => 'fa-cog', 'label' => 'Settings', 'href' => 'settings.php', 'section' => 'Admin Panel'],
    ],
    'client' => [  // Add this section
        ['icon' => 'fa-dollar-sign', 'label' => 'Budget Review', 'href' => 'client_budget_review.php', 'section' => 'Client Panel'],
        ['icon' => 'fa-project-diagram', 'label' => 'My Projects', 'href' => 'client_projects.php', 'section' => 'Client Panel'],
    ],
    'pm' => [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'href' => 'dashboard_pm.php', 'section' => 'PM Panel'],
        ['icon' => 'fa-project-diagram', 'label' => 'My Projects', 'href' => 'projects_list.php', 'section' => 'PM Panel'],
        ['icon' => 'fa-tasks', 'label' => 'Tasks', 'href' => 'tasks_list.php', 'section' => 'PM Panel'],
        ['icon' => 'fa-download', 'label' => 'Reports', 'href' => 'reports.php', 'section' => 'PM Panel'],
    ],
    'worker' => [
        ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'href' => 'dashboard_worker.php', 'section' => 'Worker Panel'],
        ['icon' => 'fa-tasks', 'label' => 'My Tasks', 'href' => 'tasks_worker.php', 'section' => 'Worker Panel'],
        ['icon' => 'fa-project-diagram', 'label' => 'My Projects', 'href' => 'projects_worker.php', 'section' => 'Worker Panel'],
    ],
];

// Get current user role
$userRole = $_SESSION['role'] ?? 'worker';
$userName = $_SESSION['name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

// Get menu items for current role
$menuItems = $menuConfig[$userRole] ?? [];

// Group menu items by section
$groupedMenu = [];
foreach ($menuItems as $item) {
    $section = $item['section'];
    if (!isset($groupedMenu[$section])) {
        $groupedMenu[$section] = [];
    }
    $groupedMenu[$section][] = $item;
}

// Function to get role badge
function getRoleBadge($role) {
    $badges = [
        'admin' => 'A',
        'pm' => 'PM',
        'worker' => 'W',
    ];
    return $badges[$role] ?? 'U';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'BuildWatch'); ?> - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/buttons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Improved unified layout styles with better sidebar and button consistency */
        
        /* Sidebar improvements */
        .sidebar {
            background: var(--primary);
            color: var(--white);
            width: 280px;
            padding: var(--space-lg) 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .logo {
            padding: 0 var(--space-lg) var(--space-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: var(--space-lg);
        }

        .logo h1 {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            margin: 0;
            font-size: 24px;
            color: var(--white);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo i {
            font-size: 28px;
        }

        .nav-section {
            flex: 1;
            padding: 0 var(--space-md);
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            padding: var(--space-md) var(--space-md);
            margin-top: var(--space-lg);
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: 12px var(--space-md);
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all var(--transition-normal);
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--secondary);
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-footer {
            padding: var(--space-lg) var(--space-md);
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin-top: auto;
        }

        .user-info-sidebar {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .user-avatar-sidebar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .user-details-sidebar {
            flex: 1;
            min-width: 0;
        }

        .user-name-sidebar {
            font-weight: 600;
            font-size: 14px;
            color: var(--white);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email-sidebar {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .main-content {
            margin-left: 280px;
            padding: var(--space-xl);
            flex: 1;
            background: var(--light-gray);
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--space-2xl);
            gap: var(--space-lg);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 8px 0;
            line-height: 1.2;
        }

        .page-description {
            color: var(--gray);
            margin: 0;
            font-size: 14px;
        }

        .page-actions {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: var(--space-md) 0;
            }

            .main-content {
                margin-left: 0;
                padding: var(--space-lg);
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .nav-section {
                display: flex;
                flex-wrap: wrap;
                gap: var(--space-md);
                padding: 0 var(--space-md);
            }

            .nav-section-title {
                width: 100%;
            }

            .nav-item {
                flex: 1;
                min-width: 150px;
            }
        }
    </style>
    <?php if (isset($additionalCSS)): ?>
        <?php echo $additionalCSS; ?>
    <?php endif; ?>
</head>
<body class="sidebar-main-layout">
    <!-- Improved unified sidebar with consistent styling -->
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> BuildWatch</h1>
        </div>

        <div class="nav-section">
            <?php foreach ($groupedMenu as $section => $items): ?>
                <div class="nav-section-title"><?php echo htmlspecialchars($section); ?></div>
                <?php foreach ($items as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['href']); ?>" 
                       class="nav-item <?php echo ($currentPage === basename($item['href'])) ? 'active' : ''; ?>">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-footer">
            <div class="user-info-sidebar">
                <div class="user-avatar-sidebar">
                    <?php echo htmlspecialchars(getRoleBadge($userRole)); ?>
                </div>
                <div class="user-details-sidebar">
                    <div class="user-name-sidebar"><?php echo htmlspecialchars($userName); ?></div>
                    <div class="user-email-sidebar"><?php echo htmlspecialchars($userEmail); ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Improved main content area with consistent button styling -->
    <div class="main-content">
        <?php if (isset($pageTitle) || isset($pageDescription)): ?>
            <div class="page-header">
                <div>
                    <?php if (isset($pageTitle)): ?>
                        <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php endif; ?>
                    <?php if (isset($pageDescription)): ?>
                        <p class="page-description"><?php echo htmlspecialchars($pageDescription); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (isset($pageActions)): ?>
                    <div class="page-actions">
                        <?php echo $pageActions; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Page content goes here -->
        <?php if (isset($pageContent)): ?>
            <?php echo $pageContent; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>
