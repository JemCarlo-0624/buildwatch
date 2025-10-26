<?php
session_start();
require_once 'config/db.php';

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: client_login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'] ?? 'Client';
$client_email = $_SESSION['client_email'] ?? '';

$stmt = $pdo->prepare("
    SELECT pp.*, c.name as client_name, c.email as client_email
    FROM project_proposals pp
    JOIN clients c ON pp.client_id = c.id
    WHERE pp.client_id = ? 
    ORDER BY pp.id DESC
");
$stmt->execute([$client_id]);
$proposals = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as created_by_name,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
           (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND progress = 100) as completed_tasks,
           (SELECT AVG(progress) FROM tasks WHERE project_id = p.id) as avg_task_progress
    FROM projects p
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.client_id = ?
    ORDER BY p.last_activity_at DESC
");
$stmt->execute([$client_id]);
$projects = $stmt->fetchAll();

$budget_notifications = [];
$unreadCount = 0;

try {
    $notifStmt = $pdo->prepare("
        SELECT n.* 
        FROM notifications n
        WHERE n.client_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $notifStmt->execute([$client_id]);
    $budget_notifications = $notifStmt->fetchAll();
    $unreadCount = count($budget_notifications);
} catch (PDOException $e) {
    // Notifications table doesn't exist yet - initialize empty arrays
    $budget_notifications = [];
    $unreadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - BuildWatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ========================================
           BASE STYLES & THEME
           ======================================== */
        :root {
            /* Transformed color palette from orange to professional deep teal */
            /* Updated color palette for professional, trustworthy aesthetic */
            --navy-blue: #1e3a5f;
            --navy-dark: #152d47;
            --primary-teal: #0d9488; /* NEW: Replaces construction-orange for modern, professional look */
            --primary-teal-dark: #0f766e; /* NEW: Darker teal for hover states */
            --primary-teal-light: #14b8a6; /* NEW: Lighter teal for highlights */
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --border-color: #e0e6ed;
            
            /* Spacing scale */
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;
            --space-2xl: 48px;
            
            /* Typography */
            --font-size-xs: 12px;
            --font-size-sm: 14px;
            --font-size-base: 16px;
            --font-size-lg: 18px;
            --font-size-xl: 24px;
            --font-size-2xl: 32px;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            
            /* Border radius */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-full: 9999px;
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            background: var(--light-bg);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            line-height: 1.6;
        }

        /* ========================================
           HEADER - IMPROVED VISUAL HIERARCHY
           ======================================== */
        /* Redesigned header with optimized spacing and perfect circular avatar */
        .client-header {
            background: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: var(--space-lg) var(--space-xl); /* Reduced from larger padding */
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .brand-section {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }
        
        .brand-logo {
            width: 48px; /* Optimized size */
            height: 48px;
            background: linear-gradient(135deg, var(--navy-blue), var(--navy-dark));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
        }
        
        .brand-logo i {
            font-size: 24px;
            color: var(--white);
        }
        
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .brand-name {
            font-size: 22px; /* Slightly reduced for compactness */
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1;
            color: var(--navy-blue);
        }
        
        .brand-tagline {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .header-right { 
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }
        
        /* Improved user info with perfect circular avatar - removed oval shape */
        .user-info { 
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-sm) var(--space-md);
            transition: var(--transition);
            min-height: 44px; /* Accessibility: minimum touch target */
        }
        
        
        /* Updated user avatar to use teal gradient instead of orange */
        /* Perfect circular avatar with consistent dimensions */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%; /* Perfect circle - removed oval shape */
            background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-dark)); /* CHANGED: From orange to teal gradient */
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: var(--font-size-base);
            box-shadow: var(--shadow-sm);
            flex-shrink: 0; /* Prevents distortion */
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .user-name {
            font-weight: 600;
            font-size: var(--font-size-sm);
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        .user-email {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
            line-height: 1.2;
        }
        
        /* Improved logout button with better accessibility */
        .logout-btn {
            background: var(--white);
            color: var(--danger);
            padding: var(--space-sm) var(--space-lg);
            border-radius: var(--radius-full);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-weight: 600;
            font-size: var(--font-size-sm);
            border: 2px solid var(--danger);
            min-height: 44px; /* Accessibility: minimum touch target */
        }
        
        .logout-btn:hover { 
            background: var(--danger);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ========================================
           MAIN CONTENT - IMPROVED LAYOUT
           ======================================== */
        /* Better content organization and spacing */
        .main-content { 
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--space-xl);
        }
        
        /* Added dashboard overview section with key metrics */
        .dashboard-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-2xl);
        }
        
        .metric-card {
            background: var(--white);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        
        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .metric-icon.primary {
            background: rgba(30, 58, 95, 0.1);
            color: var(--navy-blue);
        }
        
        .metric-icon.success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }
        
        .metric-icon.warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        /* Updated metric icon teal variant */
        .metric-icon.teal {
            background: rgba(13, 148, 136, 0.1); /* CHANGED: From orange to teal */
            color: var(--primary-teal);
        }
        
        .metric-content {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
        }
        
        .metric-value {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }
        
        .metric-label {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .metric-change {
            font-size: var(--font-size-xs);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }
        
        .metric-change.positive {
            color: var(--success);
        }
        
        .metric-change.neutral {
            color: var(--text-secondary);
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--navy-blue), var(--navy-dark));
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--space-xl);
            color: var(--white);
        }
        
        .welcome-card h2 { 
            color: var(--white);
            margin-bottom: var(--space-sm);
            font-size: var(--font-size-xl);
            font-weight: 700;
        }
        
        .welcome-card p { 
            color: rgba(255, 255, 255, 0.9);
            font-size: var(--font-size-base);
            margin-bottom: var(--space-md);
        }

        .last-updated {
            font-size: var(--font-size-xs);
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        /* Improved actions bar with better visual hierarchy */
        .actions-bar {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
            flex-wrap: wrap;
        }

        /* ========================================
           NOTIFICATION SYSTEM
           ======================================== */
        .update-notification {
            position: fixed;
            top: var(--space-lg);
            right: var(--space-lg);
            background: var(--white);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: none;
            align-items: center;
            gap: var(--space-md);
            z-index: 2000;
            border-left: 4px solid var(--success);
            animation: slideIn 0.3s ease;
            min-width: 320px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .update-notification.show {
            display: flex;
        }
        
        .update-notification i {
            font-size: 24px;
            color: var(--success);
        }
        
        .update-notification-content {
            flex: 1;
        }
        
        .update-notification-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
            font-size: var(--font-size-sm);
        }
        
        .update-notification-text {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }

        /* ========================================
           SECTION HEADERS - IMPROVED CLARITY
           ======================================== */
        /* Better section headers with improved visual hierarchy */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-md);
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-title {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }
        
        .section-title i {
            color: var(--navy-blue);
            font-size: 20px;
        }
        
        .section-count {
            background: var(--light-bg);
            padding: 2px var(--space-sm);
            border-radius: var(--radius-sm);
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-secondary);
        }

        /* ========================================
           PROPOSALS SECTION - IMPROVED CARDS
           ======================================== */
        .proposals-section,
        .projects-section {
            margin-bottom: var(--space-2xl);
        }
        
        .proposals-grid,
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: var(--space-lg);
        }
        
        /* Redesigned proposal cards with better visual hierarchy */
        .proposal-card {
            background: var(--white);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--navy-blue);
            cursor: pointer;
        }
        
        /* Updated proposal card hover to use teal accent */
        .proposal-card:hover { 
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-left-color: var(--primary-teal); /* CHANGED: From orange to teal */
        }

        .proposal-card.updated, 
        .project-card.updated {
            animation: highlight 1s ease;
        }
        
        @keyframes highlight {
            0%, 100% { background: var(--white); }
            50% { background: rgba(46, 204, 113, 0.05); }
        }
        
        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--space-md);
            gap: var(--space-md);
        }
        
        .proposal-title { 
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
            flex: 1;
        }
        
        /* Improved status badges with better contrast */
        .proposal-status {
            padding: var(--space-xs) var(--space-md);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .status-pending { 
            background: rgba(243, 156, 18, 0.1);
            color: #b8860b;
        }
        
        .status-approved { 
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }
        
        .status-rejected { 
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }
        
        .proposal-description {
            color: var(--text-secondary);
            margin-bottom: var(--space-md);
            line-height: 1.6;
            font-size: var(--font-size-sm);
        }
        
        .proposal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-md);
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
            padding-top: var(--space-md);
            border-top: 1px solid var(--border-color);
        }
        
        .proposal-meta span {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }
        
        .proposal-meta i { 
            color: var(--navy-blue);
        }

        /* ========================================
           PROJECTS SECTION - IMPROVED DESIGN
           ======================================== */
        /* Redesigned project cards with better information hierarchy */
        .project-card {
            background: var(--white);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            /* Updated project card border to use teal */
            border-left: 4px solid var(--primary-teal); /* CHANGED: From orange to teal */
            cursor: pointer;
        }

        .project-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--space-md);
            gap: var(--space-md);
        }

        .project-name {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
            flex: 1;
        }

        /* Improved progress bar with better visual feedback */
        .project-progress {
            margin: var(--space-md) 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            margin-bottom: var(--space-sm);
            font-weight: 500;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: var(--light-bg);
            border-radius: var(--radius-full);
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Updated progress bar to use teal gradient */
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-teal), var(--primary-teal-dark)); /* CHANGED: From orange to teal gradient */
            transition: width 0.5s ease;
            border-radius: var(--radius-full);
        }

        .project-stats {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-md);
            padding-top: var(--space-md);
            border-top: 1px solid var(--border-color);
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
        }

        .project-stats span {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .project-stats i {
            color: var(--navy-blue);
        }

        /* Improved action buttons with better accessibility */
        .view-details-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm) var(--space-lg);
            background: var(--navy-blue);
            color: var(--white);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-size-sm);
            font-weight: 600;
            transition: var(--transition);
            margin-top: var(--space-md);
            min-height: 44px; /* Accessibility: minimum touch target */
        }

        .view-details-btn:hover {
            background: var(--navy-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ========================================
           EMPTY STATE - IMPROVED DESIGN
           ======================================== */
        /* Better empty state with clearer messaging */
        .empty-state {
            background: var(--white);
            padding: var(--space-2xl) var(--space-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
            border: 2px dashed var(--border-color);
        }
        
        .empty-state i { 
            font-size: 64px;
            color: var(--text-secondary);
            margin-bottom: var(--space-lg);
            opacity: 0.5;
        }
        
        .empty-state h3 { 
            color: var(--text-primary);
            margin-bottom: var(--space-sm);
            font-size: var(--font-size-xl);
            font-weight: 600;
        }
        
        .empty-state p { 
            color: var(--text-secondary);
            margin-bottom: var(--space-xl);
            font-size: var(--font-size-base);
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ========================================
           REPORT GENERATION MODAL
           ======================================== */
        /* Updated generate report button to use teal */
        .generate-report-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-sm) var(--space-lg);
            background: var(--primary-teal); /* CHANGED: From orange to teal */
            color: var(--white);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: var(--space-md);
            margin-left: var(--space-sm);
            min-height: 44px; /* Accessibility */
        }
        
        .generate-report-btn:hover {
            /* Updated hover background to use teal-dark */
            background: var(--primary-teal-dark); /* CHANGED: From orange-dark to teal-dark */
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-content h3 {
            color: var(--text-primary);
            margin-top: 0;
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            font-size: var(--font-size-xl);
        }
        
        .close {
            color: var(--text-secondary);
            float: right;
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: var(--text-primary);
        }
        
        .report-format-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-md);
            margin: var(--space-xl) 0;
        }
        
        .format-btn {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--space-lg);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-sm);
            text-align: center;
            min-height: 44px; /* Accessibility */
        }
        
        .format-btn:hover {
            border-color: var(--navy-blue);
            background: rgba(30, 58, 95, 0.05);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .format-btn i {
            font-size: 32px;
            color: var(--navy-blue);
        }
        
        .format-btn span {
            font-weight: 600;
            color: var(--text-primary);
            font-size: var(--font-size-base);
        }
        
        .format-btn small {
            color: var(--text-secondary);
            font-size: var(--font-size-xs);
        }
        
        .report-progress {
            text-align: center;
            padding: var(--space-xl);
        }
        
        .spinner {
            border: 4px solid var(--light-bg);
            border-top: 4px solid var(--navy-blue);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto var(--space-lg);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .report-result {
            padding: var(--space-lg);
        }
        
        .success-message,
        .error-message {
            text-align: center;
        }
        
        .success-message i {
            font-size: 48px;
            color: var(--success);
            margin-bottom: var(--space-md);
        }
        
        .error-message i {
            font-size: 48px;
            color: var(--danger);
            margin-bottom: var(--space-md);
        }
        
        .success-message h4,
        .error-message h4 {
            color: var(--text-primary);
            margin: var(--space-sm) 0;
        }
        
        .report-actions {
            display: flex;
            gap: var(--space-sm);
            justify-content: center;
            margin-top: var(--space-lg);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
        }

        /* ========================================
           RESPONSIVE DESIGN
           ======================================== */
        @media (max-width: 768px) {
            .client-header { 
                flex-direction: column;
                gap: var(--space-md);
                padding: var(--space-md);
            }
            
            .brand-section {
                width: 100%;
                justify-content: center;
            }
            
            .header-right {
                width: 100%;
                flex-direction: column;
                gap: var(--space-sm);
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .logout-btn {
                width: 100%;
                justify-content: center;
            }
            
            .main-content { 
                padding: var(--space-md);
            }
            
            .dashboard-overview {
                grid-template-columns: 1fr;
            }
            
            .proposals-grid,
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-bar {
                flex-direction: column;
            }
            
            .actions-bar .btn {
                width: 100%;
                justify-content: center;
            }
            
            .report-format-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: var(--space-lg);
            }
        }
    </style>
</head>
<body>
    <div class="client-header">
        <div class="brand-section">
            <div class="brand-logo">
                <i class="fas fa-hard-hat"></i>
            </div>
            <div class="brand-info">
                <h1 class="brand-name">BuildWatch</h1>
                <span class="brand-tagline">Client Portal</span>
            </div>
        </div>
        <div class="header-right">
            <div class="notification-bell" id="notificationBell" onclick="toggleNotificationPanel()">
                <i class="fas fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-bell-badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($client_name, 0, 1)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($client_name); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($client_email); ?></div>
                </div>
            </div>
            <a href="client_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="update-notification" id="updateNotification">
        <i class="fas fa-check-circle"></i>
        <div class="update-notification-content">
            <div class="update-notification-title">Update</div>
            <div class="update-notification-text" id="updateNotificationText"></div>
        </div>
    </div>

    <div class="main-content">
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $client_name)[0]); ?>!</h2>
            <p>Track and manage your project proposals and active projects from your dashboard.</p>
            <div class="last-updated" id="lastUpdated">
                <i class="fas fa-sync-alt"></i>
                <span>Last updated: Just now</span>
            </div>
        </div>

        <div class="dashboard-overview">
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon primary">
                        <i class="fas fa-folder-open"></i>
                    </div>
                </div>
                <div class="metric-content">
                    <div class="metric-value" id="totalProposals"><?php echo count($proposals); ?></div>
                    <div class="metric-label">Total Proposals</div>
                    <div class="metric-change neutral">
                        <i class="fas fa-circle"></i>
                        <span>All submissions</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon success">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                </div>
                <div class="metric-content">
                    <div class="metric-value" id="activeProjects"><?php echo count($projects); ?></div>
                    <div class="metric-label">Active Projects</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>In progress</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="metric-content">
                    <div class="metric-value" id="pendingProposals">
                        <?php 
                            $pending = array_filter($proposals, function($p) { 
                                return ($p['status'] ?? 'pending') === 'pending'; 
                            });
                            echo count($pending);
                        ?>
                    </div>
                    <div class="metric-label">Pending Review</div>
                    <div class="metric-change neutral">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Awaiting approval</span>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">

                    <div class="metric-icon teal">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="metric-content">
                    <div class="metric-value">
                        <?php 
                            if (count($projects) > 0) {
                                $totalProgress = 0;
                                foreach ($projects as $project) {
                                    $totalTasks = (int)($project['total_tasks'] ?? 0);
                                    $completedTasks = (int)($project['completed_tasks'] ?? 0);
                                    // The calculation below is retained as a fallback if completion_percentage is not available
                                    $progress = $totalTasks > 0 
                                        ? round(($completedTasks / $totalTasks) * 100) 
                                        : (int)($project['completion_percentage'] ?? 0);
                                    $totalProgress += $progress;
                                }
                                echo round($totalProgress / count($projects)) . '%';
                            } else {
                                echo '0%';
                            }
                        ?>
                    </div>
                    <div class="metric-label">Avg. Completion</div>
                    <div class="metric-change positive">
                        <i class="fas fa-check-circle"></i>
                        <span>Overall progress</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <a href="client_submit_proposal.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Submit New Proposal
            </a>
        </div>

        <div class="proposals-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-folder-open"></i>
                    <span>Your Project Proposals</span>
                    <span class="section-count" id="proposalCount"><?php echo count($proposals); ?></span>
                </div>
            </div>
            
            <div id="proposalsContainer">
                <?php if (empty($proposals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Proposals Yet</h3>
                        <p>You haven't submitted any project proposals. Start by submitting your first project!</p>
                        <a href="client_submit_proposal.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Submit Your First Proposal
                        </a>
                    </div>
                <?php else: ?>
                    <div class="proposals-grid">
                        <?php foreach ($proposals as $proposal): ?>
                            <div class="proposal-card" data-proposal-id="<?php echo $proposal['id']; ?>" data-status="<?php echo htmlspecialchars($proposal['status'] ?? 'pending'); ?>">
                                <div class="proposal-header">
                                    <div class="proposal-title"><?php echo htmlspecialchars($proposal['title']); ?></div>
                                    <span class="proposal-status status-<?php echo htmlspecialchars($proposal['status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($proposal['status'] ?? 'pending')); ?>
                                    </span>
                                </div>
                                <div class="proposal-description">
                                    <?php echo htmlspecialchars(substr($proposal['description'], 0, 200)); ?>
                                    <?php if (strlen($proposal['description']) > 200) echo '...'; ?>
                                </div>
                                <div class="proposal-meta">
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($proposal['client_email']); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($proposal['client_name']); ?></span>
                                    <?php if (isset($proposal['submitted_at'])): ?>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($proposal['submitted_at'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($proposal['status'] === 'pending' && isset($proposal['has_budget'])): ?>
                                    <button class="btn btn-review-budget" onclick="openBudgetReview(<?php echo $proposal['id']; ?>)">
                                        <i class="fas fa-file-invoice-dollar"></i> Review Budget
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Added budget review modal -->
        <div id="budgetReviewModal" class="modal">
            <div class="modal-content budget-review-modal">
                <div class="modal-header">
                    <h2>Budget Review</h2>
                    <button class="modal-close" onclick="closeBudgetReview()">&times;</button>
                </div>
                <div id="budgetReviewContent" class="modal-body">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>

        <div class="projects-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-project-diagram"></i>
                    <span>Your Active Projects</span>
                    <span class="section-count" id="projectCount"><?php echo count($projects); ?></span>
                </div>
            </div>
            
            <div id="projectsContainer">
                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder"></i>
                        <h3>No Active Projects Yet</h3>
                        <p>Once your proposals are approved, they will appear here as active projects.</p>
                    </div>
                <?php else: ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <?php
                                $totalTasks = (int)($project['total_tasks'] ?? 0);
                                $completedTasks = (int)($project['completed_tasks'] ?? 0);
                                $progress = $project['completion_percentage'] ?? 0; // Use completion_percentage directly from database
                                $status = $project['status'] ?? 'planning';
                            ?>
                            <div class="project-card" onclick="window.location.href='client_project_details.php?id=<?php echo $project['id']; ?>'">
                                <div class="project-header">
                                    <div class="project-name"><?php echo htmlspecialchars($project['name']); ?></div>
                                    <span class="proposal-status status-<?php echo htmlspecialchars($status); ?>">
                                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    </span>
                                </div>
                                
                                <div class="proposal-description">
                                    <?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 150)); ?>
                                    <?php if (strlen($project['description'] ?? '') > 150) echo '...'; ?>
                                </div>

                                <div class="project-progress">
                                    <div class="progress-label">
                                        <span>Progress</span>
                                        <span><strong><?php echo $progress; ?>%</strong></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>

                                <div class="project-stats">
                                    <span><i class="fas fa-tasks"></i> <?php echo $totalTasks; ?> Tasks</span>
                                    <span><i class="fas fa-check-circle"></i> <?php echo $completedTasks; ?> Completed</span>
                                    <?php if (isset($project['end_date']) && $project['end_date']): ?>
                                        <span><i class="fas fa-calendar"></i> Due: <?php echo date('M j, Y', strtotime($project['end_date'])); ?></span>
                                    <?php elseif (isset($project['timeline']) && $project['timeline']): ?>
                                        <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($project['timeline']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <a href="client_project_details.php?id=<?php echo $project['id']; ?>" class="view-details-btn" onclick="event.stopPropagation();">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <button class="generate-report-btn" onclick="generateReport(event, <?php echo $project['id']; ?>)">
                                    <i class="fas fa-file-pdf"></i> Generate Report
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReportModal()">&times;</span>
            <h3><i class="fas fa-file-alt"></i> Generate Project Report</h3>
            <p>Select the format for your project report:</p>
            
            <div class="report-format-grid">
                <button class="format-btn" onclick="generateReportFormat('html')">
                    <i class="fas fa-file-code"></i>
                    <span>HTML Report</span>
                    <small>Web-viewable format</small>
                </button>
                <button class="format-btn" onclick="generateReportFormat('json')">
                    <i class="fas fa-file-code"></i>
                    <span>JSON Data</span>
                    <small>Machine-readable format</small>
                </button>
                <button class="format-btn" onclick="generateReportFormat('txt')">
                    <i class="fas fa-file-alt"></i>
                    <span>Text Report</span>
                    <small>Plain text format</small>
                </button>
            </div>
            
            <div id="reportProgress" class="report-progress" style="display: none;">
                <div class="spinner"></div>
                <p>Generating your report...</p>
            </div>
            
            <div id="reportResult" class="report-result" style="display: none;"></div>
        </div>
    </div>

    <script>
        // ========================================
        // CONFIGURATION
        // ========================================
        const CONFIG = {
            POLL_INTERVAL: 5000,
            MAX_RETRY_INTERVAL: 30000,
            NOTIFICATION_DURATION: 5000
        };

        // ========================================
        // STATE MANAGEMENT
        // ========================================
        const state = {
            currentProposals: <?php echo json_encode($proposals); ?>,
            currentProjects: <?php echo json_encode($projects); ?>,
            isFirstLoad: true,
            pollTimers: {
                proposals: null,
                projects: null
            }
        };

        console.log("Initial state loaded:", {
            proposals: state.currentProposals.length,
            projects: state.currentProjects.length
        });

        // ========================================
        // DATA FETCHING
        // ========================================
        async function fetchData(endpoint, type) {
            console.log(`Fetching ${type} from ${endpoint}`);
            
            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                console.log(`Response status for ${type}:`, response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log(` Received ${type} data:`, data);
                
                if (data.success) {
                    if (type === 'proposals') {
                        updateProposalsUI(data.proposals);
                    } else if (type === 'projects') {
                        updateProjectsUI(data.projects);
                    }
                    updateLastUpdatedTime();
                } else {
                    console.error(`${type} fetch failed:`, data.error);
                }
            } catch (error) {
                console.error(`Error fetching ${type}:`, error);
                retryFetch(type);
            }
        }

        function retryFetch(type) {
            const timer = state.pollTimers[type];
            if (timer) clearTimeout(timer);
            
            const retryInterval = Math.min(CONFIG.POLL_INTERVAL * 2, CONFIG.MAX_RETRY_INTERVAL);
            state.pollTimers[type] = setTimeout(() => {
                const endpoint = type === 'proposals' 
                    ? 'fetch_client_proposals_data.php' 
                    : 'fetch_client_projects_data.php';
                fetchData(endpoint, type);
            }, retryInterval);
        }

        // ========================================
        // UI UPDATE FUNCTIONS
        // ========================================
        function updateProposalsUI(newProposals) {
            console.log("Updating proposals UI:", newProposals.length);
            
            const changes = detectChanges(state.currentProposals, newProposals, 'proposal');
            
            if (changes && !state.isFirstLoad) {
                showNotification(changes);
                renderProposals(newProposals, changes);
            }
            
            if (!state.isFirstLoad) {
                state.currentProposals = newProposals;
            }
            
            state.isFirstLoad = false;
            updateCount('proposalCount', newProposals.length);
            document.getElementById('totalProposals').textContent = newProposals.length;
            
            const pendingCount = newProposals.filter(p => p.status === 'pending').length;
            document.getElementById('pendingProposals').textContent = pendingCount;

        }

        function updateProjectsUI(newProjects) {
            console.log("Updating projects UI:", newProjects.length);
            console.log("Current projects:", state.currentProjects.length);
            
            const changes = detectChanges(state.currentProjects, newProjects, 'project');
            
            if (!state.isFirstLoad) {
                renderProjects(newProjects, changes);
                if (changes) {
                    showNotification(changes);
                }
            }
            
            state.currentProjects = newProjects;
            updateCount('projectCount', newProjects.length);
            document.getElementById('activeProjects').textContent = newProjects.length;

            // Update Avg. Completion metric
            let totalProgress = 0;
            if (newProjects.length > 0) {
                newProjects.forEach(project => {
                    const totalTasks = parseInt(project.total_tasks) || 0;
                    const completedTasks = parseInt(project.completed_tasks) || 0;
                    const progress = totalTasks > 0 
                        ? Math.round((completedTasks / totalTasks) * 100) 
                        : parseInt(project.completion_percentage) || 0;
                    totalProgress += progress;
                });
                document.querySelector('.dashboard-overview .metric-card:last-child .metric-value').textContent = `${Math.round(totalProgress / newProjects.length)}%`;
            } else {
                document.querySelector('.dashboard-overview .metric-card:last-child .metric-value').textContent = '0%';
            }
        }


        // ========================================
        // CHANGE DETECTION
        // ========================================
        function detectChanges(oldData, newData, type) {
            const changes = {
                statusChanges: [],
                newItems: [],
                hasChanges: false,
                type: type
            };
            
            const oldIds = new Set(oldData.map(item => item.id));
            
            newData.forEach(newItem => {
                const oldItem = oldData.find(old => old.id === newItem.id);
                
                if (!oldItem) {
                    changes.newItems.push(newItem);
                    changes.hasChanges = true;
                } else if (oldItem.status !== newItem.status) {
                    changes.statusChanges.push({
                        id: newItem.id,
                        title: newItem.title || newItem.name,
                        oldStatus: oldItem.status,
                        newStatus: newItem.status
                    });
                    changes.hasChanges = true;
                }
            });
            
            return changes.hasChanges ? changes : null;
        }

        // ========================================
        // NOTIFICATION SYSTEM
        // ========================================
        function showNotification(changes) {
            const notification = document.getElementById('updateNotification');
            const notificationText = document.getElementById('updateNotificationText');
            const notificationTitle = notification.querySelector('.update-notification-title');
            
            const { title, message } = getNotificationContent(changes);
            
            notificationTitle.textContent = title;
            notificationText.textContent = message;
            notification.classList.add('show');
            
            setTimeout(() => notification.classList.remove('show'), CONFIG.NOTIFICATION_DURATION);
        }

        function getNotificationContent(changes) {
            if (changes.statusChanges.length > 0) {
                const change = changes.statusChanges[0];
                return {
                    title: 'Status Changed',
                    message: `"${change.title}" status changed to ${change.newStatus}`
                };
            }
            
            if (changes.newItems.length > 0) {
                const item = changes.newItems[0];
                const itemName = item.title || item.name;
                return {
                    title: `New ${changes.type === 'proposal' ? 'Proposal' : 'Project'}`,
                    message: `New ${changes.type} "${itemName}" has been added`
                };
            }
            
            return {
                title: 'Update',
                message: `${changes.type === 'proposal' ? 'Proposals' : 'Projects'} have been updated`
            };
        }

        // ========================================
        // RENDERING FUNCTIONS
        // ========================================
        function renderProposals(proposals, changes) {
            const container = document.getElementById('proposalsContainer');
            
            if (proposals.length === 0) {
                container.innerHTML = getEmptyState('proposals');
                return;
            }
            
            const html = proposals.map(proposal => createProposalCard(proposal, changes)).join('');
            container.innerHTML = `<div class="proposals-grid">${html}</div>`;
        }

        function renderProjects(projects, changes) {
            console.log("Rendering projects:", projects.length);
            const container = document.getElementById('projectsContainer');
            
            if (projects.length === 0) {
                container.innerHTML = getEmptyState('projects');
                return;
            }
            
            const html = projects.map(project => createProjectCard(project, changes)).join('');
            container.innerHTML = `<div class="projects-grid">${html}</div>`;
        }

        function createProposalCard(proposal, changes) {
            const status = proposal.status || 'pending';
            const isUpdated = changes && changes.statusChanges.some(c => c.id === proposal.id);
            const updatedClass = isUpdated ? 'updated' : '';
            const description = truncateText(proposal.description, 200);
            const submittedDate = formatDate(proposal.submitted_at);
            
            return `
                <div class="proposal-card ${updatedClass}" data-proposal-id="${proposal.id}" data-status="${status}">
                    <div class="proposal-header">
                        <div class="proposal-title">${escapeHtml(proposal.title)}</div>
                        <span class="proposal-status status-${status}">
                            ${capitalize(status)}
                        </span>
                    </div>
                    <div class="proposal-description">${escapeHtml(description)}</div>
                    <div class="proposal-meta">
                        <span><i class="fas fa-envelope"></i> ${escapeHtml(proposal.client_email)}</span>
                        <span><i class="fas fa-user"></i> ${escapeHtml(proposal.client_name)}</span>
                        ${submittedDate ? `<span><i class="fas fa-calendar"></i> ${submittedDate}</span>` : ''}
                    </div>
                </div>
            `;
        }

        function createProjectCard(project, changes) {
            const totalTasks = parseInt(project.total_tasks) || 0;
            const completedTasks = parseInt(project.completed_tasks) || 0;
            const progress = parseInt(project.completion_percentage) || 0;
            const status = project.status || 'planning';
            const isUpdated = changes && (
                changes.statusChanges.some(c => c.id === project.id) ||
                changes.newItems.some(n => n.id === project.id)
            );
            const updatedClass = isUpdated ? 'updated' : '';
            const description = truncateText(project.description, 150);
            
            let dateInfo = '';
            if (project.end_date) {
                dateInfo = `<span><i class="fas fa-calendar"></i> Due: ${formatDate(project.end_date)}</span>`;
            } else if (project.timeline) {
                dateInfo = `<span><i class="fas fa-clock"></i> ${escapeHtml(project.timeline)}</span>`;
            }
            
            return `
                <div class="project-card ${updatedClass}" onclick="window.location.href='client_project_details.php?id=${project.id}'">
                    <div class="project-header">
                        <div class="project-name">${escapeHtml(project.name)}</div>
                        <span class="proposal-status status-${status}">
                            ${capitalize(status)}
                        </span>
                    </div>
                    
                    <div class="proposal-description">${escapeHtml(description)}</div>

                    <div class="project-progress">
                        <div class="progress-label">
                            <span>Progress</span>
                            <span><strong>${progress}%</strong></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progress}%"></div>
                        </div>
                    </div>

                    <div class="project-stats">
                        <span><i class="fas fa-tasks"></i> ${totalTasks} Tasks</span>
                        <span><i class="fas fa-check-circle"></i> ${completedTasks} Completed</span>
                        ${dateInfo}
                    </div>

                    <a href="client_project_details.php?id=${project.id}" class="view-details-btn" onclick="event.stopPropagation();">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <button class="generate-report-btn" onclick="generateReport(event, ${project.id})">
                        <i class="fas fa-file-pdf"></i> Generate Report
                    </button>
                </div>
            `;
        }

        // ========================================
        // UTILITY FUNCTIONS
        // ========================================
        function updateCount(elementId, count) {
            const element = document.getElementById(elementId);
            if (element) element.textContent = count;
        }

        function updateLastUpdatedTime() {
            const lastUpdated = document.getElementById('lastUpdated');
            if (!lastUpdated) return;
            
            const timeString = new Date().toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit' 
            });
            
            lastUpdated.innerHTML = `
                <i class="fas fa-sync-alt"></i>
                <span>Last updated: ${timeString}</span>
            `;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }

        function capitalize(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            } catch (e) {
                console.error("Error formatting date:", dateString, e);
                return '';
            }
        }

        function getEmptyState(type) {
            const config = {
                proposals: {
                    icon: 'inbox',
                    title: 'No Proposals Yet',
                    message: "You haven't submitted any project proposals. Start by submitting your first project!",
                    link: 'client_submit_proposal.php',
                    linkText: 'Submit Your First Proposal'
                },
                projects: {
                    icon: 'folder',
                    title: 'No Active Projects Yet',
                    message: "Once your proposals are approved, they will appear here as active projects.",
                    link: null,
                    linkText: null
                }
            };
            
            const { icon, title, message, link, linkText } = config[type];
            
            return `
                <div class="empty-state">
                    <i class="fas fa-${icon}"></i>
                    <h3>${title}</h3>
                    <p>${message}</p>
                    ${link ? `<a href="${link}" class="btn btn-primary"><i class="fas fa-plus"></i> ${linkText}</a>` : ''}
                </div>
            `;
        }

        // ========================================
        // POLLING MANAGEMENT
        // ========================================
        function startPolling() {
            stopPolling();
            
            state.pollTimers.proposals = setInterval(() => {
                fetchData('fetch_client_proposals_data.php', 'proposals');
            }, CONFIG.POLL_INTERVAL);
            
            state.pollTimers.projects = setInterval(() => {
                fetchData('fetch_client_projects_data.php', 'projects');
            }, CONFIG.POLL_INTERVAL);
        }

        function stopPolling() {
            Object.values(state.pollTimers).forEach(timer => {
                if (timer) clearInterval(timer);
            });
        }

        // ========================================
        // REPORT GENERATION FUNCTIONS
        // ========================================
        let currentProjectId = null;
        
        function generateReport(event, projectId) {
            event.stopPropagation();
            currentProjectId = projectId;
            document.getElementById('reportModal').style.display = 'block';
            document.getElementById('reportProgress').style.display = 'none';
            document.getElementById('reportResult').style.display = 'none';
            document.getElementById('reportResult').innerHTML = ''; // Clear previous results
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
            currentProjectId = null;
            document.getElementById('reportProgress').style.display = 'none';
            document.getElementById('reportResult').style.display = 'none';
        }
        
        async function generateReportFormat(format) {
            if (!currentProjectId) return;
            
            const progressDiv = document.getElementById('reportProgress');
            const resultDiv = document.getElementById('reportResult');
            
            progressDiv.style.display = 'block';
            resultDiv.style.display = 'none';
            resultDiv.innerHTML = ''; // Clear previous results
            
            try {
                console.log('Generating report for project:', currentProjectId, 'format:', format);
                const response = await fetch(`generate_report.php?project_id=${currentProjectId}&format=${format}`);
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, response: ${errorText}`);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                progressDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <h4>Report Generated Successfully!</h4>
                            <p>Your ${format.toUpperCase()} report is ready.</p>
                            <div class="report-actions">
                                <a href="${data.reportUrl}" class="btn btn-primary" download>
                                    <i class="fas fa-download"></i> Download Report
                                </a>
                                ${format === 'html' ? `
                                    <a href="${data.reportUrl}" class="btn btn-secondary" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Report
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    let errorDetails = data.error || 'An error occurred while generating the report.';
                    if (data.details) {
                        errorDetails += '<br><br><strong>Details:</strong><br><pre style="text-align: left; background: #f5f5f5; padding: 10px; border-radius: 5px; font-size: 12px; max-height: 200px; overflow-y: auto;">' + 
                            escapeHtml(data.details) + '</pre>';
                    }
                    if (data.returnCode) {
                        errorDetails += '<br><small>Return code: ' + data.returnCode + '</small>';
                    }
                    
                    resultDiv.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <h4>Report Generation Failed</h4>
                            <p>${errorDetails}</p>
                            <button class="btn btn-secondary" onclick="closeReportModal()">Close</button>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Report generation error:', error);
                progressDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <h4>Connection Error</h4>
                        <p>Failed to connect to the report generator. Please try again.</p>
                        <p><small>Error: ${error.message}</small></p>
                        <button class="btn btn-secondary" onclick="closeReportModal()">Close</button>
                    </div>
                `;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('reportModal');
            if (event.target === modal) {
                closeReportModal();
            }
        }
        
        // ========================================
        // EVENT LISTENERS
        // ========================================
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopPolling();
            } else {
                // Re-fetch and restart polling when tab becomes visible
                fetchData('fetch_client_proposals_data.php', 'proposals');
                fetchData('fetch_client_projects_data.php', 'projects');
                startPolling();
            }
        });

        window.addEventListener('load', () => {
            updateLastUpdatedTime();
            startPolling();
        });

        window.addEventListener('beforeunload', stopPolling);

        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            panel.classList.toggle('show');
        }

        function markNotificationRead(event, notificationId) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('notification_id', notificationId);

            fetch('mark_notification_read.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the notification item from the panel
                    const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (item) {
                        item.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => item.remove(), 300);
                    }

                    // Update badge count
                    const badge = document.querySelector('.notification-bell-badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }

                    // Navigate to the budget review page
                    const link = event.target.closest('a').href;
                    window.location.href = link;
                }
            })
            .catch(error => console.error('Error marking notification:', error));
        }

        function pollNotifications() {
            fetch('fetch_client_notifications.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unread_count > 0) {
                    // Update badge
                    let badge = document.querySelector('.notification-bell-badge');
                    if (!badge) {
                        const bell = document.getElementById('notificationBell');
                        badge = document.createElement('span');
                        badge.className = 'notification-bell-badge';
                        bell.appendChild(badge);
                    }
                    badge.textContent = data.unread_count;

                    // Update notification list
                    const list = document.getElementById('notificationList');
                    if (data.notifications.length > 0) {
                        let html = '';
                        data.notifications.forEach(notif => {
                            const time = new Date(notif.created_at).toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                            html += `
                                <div class="notification-item" data-notification-id="${notif.id}">
                                    <div class="notification-item-header">
                                        <span class="notification-item-type">Budget Review</span>
                                        <span class="notification-item-time">${time}</span>
                                    </div>
                                    <div class="notification-item-message">
                                        ${notif.message}
                                    </div>
                                    <a href="${notif.link}" class="notification-item-action" onclick="markNotificationRead(event, ${notif.id})">
                                        <i class="fas fa-eye"></i> Review Now
                                    </a>
                                </div>
                            `;
                        });
                        list.innerHTML = html;
                    }
                }
            })
            .catch(error => console.error('Error polling notifications:', error));
        }

        // Start polling for notifications
        setInterval(pollNotifications, 10000);

        // Close notification panel when clicking outside
        document.addEventListener('click', (e) => {
            const panel = document.getElementById('notificationPanel');
            const bell = document.getElementById('notificationBell');
            if (!panel.contains(e.target) && !bell.contains(e.target)) {
                panel.classList.remove('show');
            }
        });

        // ========================================
        // BUDGET REVIEW FUNCTIONS - Modern HCI Design
        // ========================================
        function openBudgetReview(proposalId) {
            const proposal = state.currentProposals.find(p => p.id === proposalId);
            if (!proposal) return;
            
            const proposedBudget = parseFloat(proposal.budget) || 0;
            const adminBudget = parseFloat(proposal.admin_budget) || proposedBudget;
            const difference = adminBudget - proposedBudget;
            const differencePercent = proposedBudget > 0 ? ((difference / proposedBudget) * 100).toFixed(1) : 0;
            
            // Format currency with proper localization
            const formatCurrency = (value) => {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2
                }).format(value);
            };
            
            // Determine visual indicators for budget difference
            const differenceClass = difference >= 0 ? 'positive' : '';
            const differenceIcon = difference >= 0 ? '↑' : '↓';
            const differenceText = difference >= 0 ? 'Higher than proposed' : 'Lower than proposed';
            const differenceAbsolute = Math.abs(difference);
            
            // - Clear visual hierarchy with header, comparison, details, and actions
            // - Progressive disclosure of information
            // - Clear call-to-action buttons with visual feedback
            // - Accessibility features (ARIA labels, keyboard navigation)
            const content = `
                <div class="budget-header">
                    <h3 class="budget-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        ${proposal.title}
                    </h3>
                    <p class="budget-description">
                        Submitted by <strong>${proposal.client_name}</strong> on ${formatDate(proposal.submitted_at)}
                    </p>
                </div>

                <div class="budget-comparison" role="region" aria-label="Budget Comparison">
                    <div class="budget-box" role="article">
                        <span class="budget-box-label">Your Proposed Budget</span>
                        <div class="budget-box-value">${formatCurrency(proposedBudget)}</div>
                        <small style="color: var(--text-tertiary); font-size: var(--font-size-caption);">Original submission</small>
                    </div>
                    <div class="budget-box" role="article">
                        <span class="budget-box-label">Admin Evaluation</span>
                        <div class="budget-box-value">${formatCurrency(adminBudget)}</div>
                        <small style="color: var(--text-tertiary); font-size: var(--font-size-caption);">Reviewed amount</small>
                    </div>
                </div>

                <div class="budget-difference ${differenceClass}" role="status" aria-live="polite">
                    <strong>${differenceIcon} ${differenceText}</strong>
                    <span style="margin-left: var(--space-2);">${formatCurrency(differenceAbsolute)} (${differencePercent}%)</span>
                </div>

                ${proposal.admin_comment ? `
                    <div class="admin-comment" role="note">
                        <div class="admin-comment-label">
                            <i class="fas fa-comment-dots"></i> Admin Notes
                        </div>
                        <div class="admin-comment-text">${proposal.admin_comment}</div>
                    </div>
                ` : ''}

                <div class="decision-actions" role="group" aria-label="Budget Decision Actions">
                    <button class="btn-accept-budget" onclick="submitBudgetReview(${proposalId})" 
                            title="Approve this budget and proceed" 
                            aria-label="Accept budget for ${proposal.title}">
                        <i class="fas fa-check-circle"></i> Accept Budget
                    </button>
                    <button class="btn-reject-budget" onclick="rejectBudgetReview(${proposalId})" 
                            title="Reject this budget and request changes" 
                            aria-label="Reject budget for ${proposal.title}">
                        <i class="fas fa-times-circle"></i> Reject Budget
                    </button>
                </div>
            `;
            
            document.getElementById('budgetReviewContent').innerHTML = content;
            const modal = document.getElementById('budgetReviewModal');
            modal.classList.add('active');
            modal.style.display = 'flex';
            
            // Set focus to the modal for keyboard navigation
            modal.focus();
            
            // Close modal on Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    closeBudgetReview();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
            
            // Close modal when clicking outside (backdrop)
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeBudgetReview();
                }
            });
        }

        function closeBudgetReview() {
            const modal = document.getElementById('budgetReviewModal');
            modal.classList.remove('active');
            modal.style.display = 'none';
            
            const reviewButton = document.querySelector('[onclick*="openBudgetReview"]');
            if (reviewButton) {
                reviewButton.focus();
            }
        }

        function submitBudgetReview(proposalId) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            
            const formData = new FormData();
            formData.append('proposal_id', proposalId);
            formData.append('action', 'approve');
            
            fetch('submit_budget_review.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const proposal = state.currentProposals.find(p => p.id === proposalId);
                    if (proposal) {
                        proposal.status = 'approved';
                        proposal.has_budget = true;
                    }
                    
                    renderProposals(state.currentProposals, {
                        statusChanges: [{
                            id: proposalId,
                            title: proposal.title,
                            oldStatus: 'pending',
                            newStatus: 'approved'
                        }]
                    });
                    
                    closeBudgetReview();
                    
                    showNotification({
                        type: 'success',
                        title: 'Budget Approved',
                        message: `"${proposal.title}" budget has been approved successfully!`,
                        duration: 5000
                    });
                } else {
                    showNotification({
                        type: 'error',
                        title: 'Approval Failed',
                        message: 'Failed to approve budget: ' + (data.error || 'Unknown error. Please try again.')
                    });
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.setAttribute('aria-busy', 'false');
                }
            })
            .catch(error => {
                console.error('Error submitting budget review:', error);
                showNotification({
                    type: 'error',
                    title: 'Error',
                    message: 'An error occurred while processing your request. Please try again.'
                });
                button.innerHTML = originalText;
                button.disabled = false;
                button.setAttribute('aria-busy', 'false');
            });
        }

        function rejectBudgetReview(proposalId) {
            const proposal = state.currentProposals.find(p => p.id === proposalId);
            if (!proposal) return;
            
            const confirmReject = confirm(
                `Are you sure you want to reject the budget for "${proposal.title}"?\n\n` +
                `This will require the client to resubmit their proposal.`
            );
            
            if (!confirmReject) return;
            
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            
            const formData = new FormData();
            formData.append('proposal_id', proposalId);
            formData.append('action', 'reject');
            
            fetch('submit_budget_review.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const proposal = state.currentProposals.find(p => p.id === proposalId);
                    if (proposal) {
                        proposal.status = 'rejected';
                    }
                    
                    renderProposals(state.currentProposals, {
                        statusChanges: [{
                            id: proposalId,
                            title: proposal.title,
                            oldStatus: 'pending',
                            newStatus: 'rejected'
                        }]
                    });
                    
                    closeBudgetReview();
                    
                    showNotification({
                        type: 'warning',
                        title: 'Budget Rejected',
                        message: `"${proposal.title}" budget has been rejected. The client will be notified.`,
                        duration: 5000
                    });
                } else {
                    showNotification({
                        type: 'error',
                        title: 'Rejection Failed',
                        message: 'Failed to reject budget: ' + (data.error || 'Unknown error. Please try again.')
                    });
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.setAttribute('aria-busy', 'false');
                }
            })
            .catch(error => {
                console.error('Error rejecting budget review:', error);
                showNotification({
                    type: 'error',
                    title: 'Error',
                    message: 'An error occurred while processing your request. Please try again.'
                });
                button.innerHTML = originalText;
                button.disabled = false;
                button.setAttribute('aria-busy', 'false');
            });
        }
    </script>
</body>
</html>
