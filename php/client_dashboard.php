<?php
session_start();
require_once("../config/db.php");

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
           BASE STYLES
           ======================================== */
        body { 
            background: var(--light);
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* ========================================
           HEADER STYLES
           ======================================== */
        .client-header {
            background: linear-gradient(135deg, #0a4275 0%, #084980 100%);
            color: white;
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(10, 66, 117, 0.25);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        /* Enhanced branding section with BuildWatch logo and name */
        .brand-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .brand-logo i {
            font-size: 28px;
            color: #0a4275;
        }
        
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .brand-name {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1;
        }
        
        .brand-tagline {
            font-size: 12px;
            opacity: 0.85;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        /* Enhanced user details section with better HCI principles */
        .header-right { 
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .user-info { 
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .user-info:hover {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.25);
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .user-name {
            font-weight: 700;
            font-size: 15px;
            line-height: 1.2;
            letter-spacing: 0.2px;
        }
        
        .user-email {
            font-size: 12px;
            opacity: 0.85;
            line-height: 1.2;
            font-weight: 500;
        }
        
        .logout-btn {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid transparent;
        }
        
        .logout-btn:hover { 
            background: rgba(192, 57, 43, 1);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(231, 76, 60, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* ========================================
           LIVE INDICATOR
           ======================================== */
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            background: rgba(46, 204, 113, 0.15);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #2ecc71;
            border: 2px solid rgba(46, 204, 113, 0.3);
            letter-spacing: 0.5px;
        }
        
        .live-dot {
            width: 10px;
            height: 10px;
            background: #2ecc71;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
        }
        
        @keyframes pulse {
            0%, 100% { 
                opacity: 1;
                transform: scale(1);
            }
            50% { 
                opacity: 0.6;
                transform: scale(0.9);
            }
        }

        /* ========================================
           MAIN CONTENT AREA
           ======================================== */
        .main-content { 
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }
        
        .welcome-card h2 { 
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .welcome-card p { 
            color: var(--gray);
            font-size: 16px;
        }

        .last-updated {
            font-size: 12px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 10px;
        }
        
        .last-updated i {
            font-size: 10px;
        }

        .actions-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        /* ========================================
           NOTIFICATION STYLES
           ======================================== */
        .update-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            border-left: 4px solid var(--success);
            animation: slideIn 0.3s ease;
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
            font-size: 20px;
            color: var(--success);
        }
        
        .update-notification-content {
            flex: 1;
        }
        
        .update-notification-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .update-notification-text {
            font-size: 13px;
            color: var(--gray);
        }

        /* ========================================
           PROPOSALS SECTION
           ======================================== */
        .proposals-section h3,
        .projects-section h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .proposals-grid,
        .projects-grid {
            display: grid;
            gap: 20px;
        }
        
        .proposal-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            border-left: 4px solid var(--secondary);
        }
        
        .proposal-card:hover { 
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .proposal-card.updated, 
        .project-card.updated {
            animation: highlight 1s ease;
        }
        
        @keyframes highlight {
            0%, 100% { background: white; }
            50% { background: rgba(46, 204, 113, 0.1); }
        }
        
        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .proposal-title { 
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .proposal-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { 
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved { 
            background: rgba(46, 204, 113, 0.1);
            color: var(--success);
        }
        
        .status-rejected { 
            background: rgba(212, 47, 19, 0.1);
            color: var(--accent);
        }
        
        .proposal-description {
            color: var(--gray);
            margin-bottom: 15px;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .proposal-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--gray);
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .proposal-meta i { 
            color: var(--primary);
            margin-right: 5px;
        }

        /* ========================================
           PROJECTS SECTION
           ======================================== */
        .projects-section {
            margin-top: 40px;
        }

        .project-card {
            background: white;
            padding: 25px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-normal);
            border-left: 4px solid var(--primary);
            cursor: pointer;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .project-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .project-progress {
            margin: 15px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.5s ease;
        }

        .project-stats {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: var(--gray);
        }

        .project-stats i {
            color: var(--primary);
            margin-right: 5px;
        }

        .view-details-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition-normal);
            margin-top: 15px;
        }

        .view-details-btn:hover {
            background: var(--dark);
            color: white;
        }

        /* ========================================
           EMPTY STATE
           ======================================== */
        .empty-state {
            background: white;
            padding: 60px 30px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        
        .empty-state i { 
            font-size: 60px;
            color: var(--gray);
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 { 
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .empty-state p { 
            color: var(--gray);
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* ========================================
           REPORT GENERATION STYLES
           ======================================== */
        .generate-report-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-normal);
            margin-top: 15px;
            margin-left: 10px;
        }
        
        .generate-report-btn:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
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
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: var(--gray);
            float: right;
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: var(--dark);
        }
        
        .report-format-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        
        .format-btn {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-lg);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-align: center;
        }
        
        .format-btn:hover {
            border-color: var(--primary);
            background: rgba(10, 66, 117, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(10, 66, 117, 0.15);
        }
        
        .format-btn i {
            font-size: 32px;
            color: var(--primary);
        }
        
        .format-btn span {
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }
        
        .format-btn small {
            color: var(--gray);
            font-size: 12px;
        }
        
        .report-progress {
            text-align: center;
            padding: 30px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .report-result {
            padding: 20px;
        }
        
        .success-message,
        .error-message {
            text-align: center;
        }
        
        .success-message i {
            font-size: 48px;
            color: var(--success);
            margin-bottom: 15px;
        }
        
        .error-message i {
            font-size: 48px;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .success-message h4,
        .error-message h4 {
            color: var(--dark);
            margin: 10px 0;
        }
        
        .report-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: var(--gray);
        }
        
        .btn-secondary:hover {
            background: var(--dark);
        }
        
        @media (max-width: 768px) {
            .report-format-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
        }

        /* ========================================
           RESPONSIVE STYLES
           ======================================== */
        @media (max-width: 768px) {
            .client-header { 
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }
            
            .brand-section {
                width: 100%;
                justify-content: center;
            }
            
            .header-right {
                width: 100%;
                flex-direction: column;
                gap: 12px;
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
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .actions-bar {
                flex-direction: column;
            }
            
            .actions-bar .btn {
                width: 100%;
                justify-content: center;
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
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($client_name, 0, 1)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($client_name); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($client_email); ?></div>
                </div>
            </div>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>LIVE</span>
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

        <div class="actions-bar">
            <a href="client_submit_proposal.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Submit New Proposal
            </a>
        </div>

        <div class="proposals-section">
            <h3><i class="fas fa-folder-open"></i> Your Project Proposals (<span id="proposalCount"><?php echo count($proposals); ?></span>)</h3>
            
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="projects-section">
            <h3><i class="fas fa-project-diagram"></i> Your Active Projects (<span id="projectCount"><?php echo count($projects); ?></span>)</h3>
            
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
                                $progress = $totalTasks > 0 
                                    ? round(($completedTasks / $totalTasks) * 100) 
                                    : (int)($project['completion_percentage'] ?? 0);
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
                                
                                 <!-- CHANGE: Added Generate Report button -->
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

    <!-- CHANGE: Added report generation modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReportModal()">&times;</span>
            <h3><i class="fas fa-file-alt"></i> Generate Project Report</h3>
            <p>Select the format for your project report:</p>
            
            <div class="report-format-grid">
                <button class="format-btn" onclick="generateReportFormat('pdf')">
                    <i class="fas fa-file-pdf"></i>
                    <span>PDF Report</span>
                    <small>Professional formatted document</small>
                </button>
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

        console.log("[v0] Initial state loaded:", {
            proposals: state.currentProposals.length,
            projects: state.currentProjects.length
        });

        // ========================================
        // DATA FETCHING
        // ========================================
        async function fetchData(endpoint, type) {
            console.log(`[v0] Fetching ${type} from ${endpoint}`);
            
            try {
                const response = await fetch(endpoint, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                
                console.log(`[v0] Response status for ${type}:`, response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log(`[v0] Received ${type} data:`, data);
                
                if (data.success) {
                    if (type === 'proposals') {
                        updateProposalsUI(data.proposals);
                    } else if (type === 'projects') {
                        updateProjectsUI(data.projects);
                    }
                    updateLastUpdatedTime();
                } else {
                    console.error(`[v0] ${type} fetch failed:`, data.error);
                }
            } catch (error) {
                console.error(`[v0] Error fetching ${type}:`, error);
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
            console.log("[v0] Updating proposals UI:", newProposals.length);
            
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
        }

        function updateProjectsUI(newProjects) {
            console.log("[v0] Updating projects UI:", newProjects.length);
            console.log("[v0] Current projects:", state.currentProjects.length);
            
            const changes = detectChanges(state.currentProjects, newProjects, 'project');
            
            if (!state.isFirstLoad) {
                renderProjects(newProjects, changes);
                if (changes) {
                    showNotification(changes);
                }
            }
            
            state.currentProjects = newProjects;
            updateCount('projectCount', newProjects.length);
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
            console.log("[v0] Rendering projects:", projects.length);
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
            const progress = project.progress || 0;
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
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
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
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
            currentProjectId = null;
        }
        
        async function generateReportFormat(format) {
            if (!currentProjectId) return;
            
            const progressDiv = document.getElementById('reportProgress');
            const resultDiv = document.getElementById('reportResult');
            
            progressDiv.style.display = 'block';
            resultDiv.style.display = 'none';
            
            try {
                console.log('[v0] Generating report for project:', currentProjectId, 'format:', format);
                const response = await fetch(`generate_report.php?project_id=${currentProjectId}&format=${format}`);
                console.log('[v0] Response status:', response.status);
                
                const data = await response.json();
                console.log('[v0] Response data:', data);
                
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
                console.error('[v0] Report generation error:', error);
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
    </script>
</body>
</html>
