<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    header("Location: client_login.php");
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    header("Location: client_dashboard.php");
    exit;
}

$client_id = $_SESSION['client_id'];

$stmt = $pdo->prepare("
    SELECT p.* 
    FROM projects p
    WHERE p.id = ? AND p.client_id = ?
");
$stmt->execute([$project_id, $client_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Access denied or project not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['name']) ?> - Project Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Updated header to match dashboard with BuildWatch branding */
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
            margin-bottom: 30px;
        }
        
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

        .container-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px 30px;
        }

        .back-btn {
            background: white;
            color: var(--primary);
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            transition: all var(--transition-normal);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            margin-bottom: 20px;
            border: 2px solid var(--primary);
        }
        
        .back-btn:hover {
            background: var(--primary);
            color: white;
        }

        .project-header {
            background: linear-gradient(135deg, #0a4275, #084980);
            color: white;
            padding: 50px 40px;
            border-radius: 16px;
            margin-bottom: 35px;
            box-shadow: 0 8px 24px rgba(10, 66, 117, 0.2);
        }

        .project-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }

        .project-meta {
            display: flex;
            gap: 35px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: rgba(255, 255, 255, 0.95);
        }

        .meta-item i {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-planning {
            background: #3498db;
            color: white;
        }

        .status-in-progress, .status-in_progress {
            background: #f39c12;
            color: white;
        }

        .status-completed {
            background: #2ecc71;
            color: white;
        }

        .status-on-hold, .status-on_hold {
            background: #e74c3c;
            color: white;
        }

        .status-pending {
            background: #95a5a6;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #0a4275;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .stat-card.highlight-change {
            animation: highlightPulse 2s ease-in-out;
        }

        @keyframes highlightPulse {
            0%, 100% { background-color: white; }
            50% { background-color: #fff3cd; }
        }

        .stat-value {
            font-size: 38px;
            font-weight: 700;
            color: #0a4275;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 35px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #0a4275;
            font-size: 24px;
        }

        .activity-item {
            padding: 20px;
            border-left: 4px solid #0a4275;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 18px;
            transition: background 0.2s ease;
        }

        .activity-item:hover {
            background: #e9ecef;
        }

        .activity-item.highlight-change {
            animation: highlightPulse 2s ease-in-out;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .activity-type {
            font-weight: 700;
            color: #2c3e50;
            font-size: 16px;
        }

        .activity-time {
            font-size: 13px;
            color: #6c757d;
        }

        .activity-description {
            color: #495057;
            font-size: 15px;
            line-height: 1.6;
        }

        .milestone-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: background 0.2s ease;
        }

        .milestone-item:hover {
            background: #e9ecef;
        }

        .milestone-item.highlight-change {
            animation: highlightPulse 2s ease-in-out;
        }

        .milestone-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a4275;
            color: white;
            flex-shrink: 0;
            font-size: 20px;
        }

        .milestone-info {
            flex: 1;
        }

        .milestone-name {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 6px;
            font-size: 16px;
        }

        .milestone-date {
            font-size: 14px;
            color: #6c757d;
        }

        .progress-bar-container {
            background: #e9ecef;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 12px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0a4275, #3498db);
            transition: width 0.5s ease;
            border-radius: 5px;
        }

        .update-notification {
            position: fixed;
            top: 30px;
            right: 30px;
            background: white;
            padding: 18px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 9999;
            transform: translateX(450px);
            transition: transform 0.3s ease;
            max-width: 420px;
        }

        .update-notification.show {
            transform: translateX(0);
        }

        .update-notification.success {
            border-left: 5px solid #2ecc71;
        }

        .update-notification.info {
            border-left: 5px solid #3498db;
        }

        .update-notification i {
            font-size: 22px;
            color: #2ecc71;
        }

        .update-notification.info i {
            color: #3498db;
        }

        .update-notification span {
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .fw-bold {
            font-weight: 700;
        }

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

            .container-wrapper {
                padding: 0 15px 20px;
            }

            .project-header {
                padding: 30px 25px;
            }

            .project-title {
                font-size: 28px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['client_name'] ?? 'C', 0, 1)); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['client_name'] ?? 'Client'); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['client_email'] ?? ''); ?></div>
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

    <div class="container-wrapper">
        <a href="client_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="project-header">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="project-title"><?= htmlspecialchars($project['name']) ?></h1>
                    <span class="status-badge status-<?= strtolower($project['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                    </span>
                </div>
                <div class="live-indicator">
                    <span class="live-dot"></span>
                    Live Updates
                </div>
            </div>
            <div class="project-meta">
                <?php if (!empty($project['category'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span><?= htmlspecialchars($project['category']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($project['location'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($project['location']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($project['timeline'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?= htmlspecialchars($project['timeline']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="stats-container" class="stats-grid">
             Stats will be loaded dynamically 
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Project Description
                    </h2>
                    <p style="color: #495057; line-height: 1.8; font-size: 15px;"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
                </div>

                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-tasks"></i>
                        Project Tasks
                    </h2>
                    <div id="tasks-container">
                        <p class="text-muted">Loading tasks...</p>
                    </div>
                </div>

                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-alt"></i>
                        Project Schedule
                    </h2>
                    <div id="schedule-container">
                        <p class="text-muted">Loading schedule...</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Project Info
                    </h2>
                    <div id="info-container">
                        <p class="text-muted">Loading info...</p>
                    </div>
                </div>

                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i>
                        Project Team
                    </h2>
                    <div id="team-container">
                        <p class="text-muted">Loading team...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CONFIG = {
            projectId: <?= $project_id ?>,
            pollInterval: 5000,
            notificationDuration: 5000,
            highlightDuration: 2000
        };

        const state = {
            lastUpdate: null,
            previousData: null,
            isPolling: true,
            pollTimer: null
        };

        async function fetchProjectDetails() {
            try {
                const response = await fetch(`fetch_client_project_details_data.php?id=${CONFIG.projectId}`);
                
                if (!response.ok) {
                    console.error('HTTP error:', response.status, response.statusText);
                    return null;
                }
                
                const data = await response.json();

                if (data.error) {
                    console.error('Error from server:', data.error);
                    return null;
                }

                if (!data.success) {
                    console.error('Request unsuccessful:', data);
                    return null;
                }

                return data;
            } catch (error) {
                console.error('Fetch error:', error);
                return null;
            }
        }

        function detectChanges(newData) {
            if (!state.previousData) return null;

            const changes = {
                hasChanges: false,
                completion: false,
                tasks: false,
                team: false,
                schedule: false
            };

            if (newData.project.completion_percentage !== state.previousData.project.completion_percentage) {
                changes.completion = true;
                changes.hasChanges = true;
            }

            if (newData.tasks.length !== state.previousData.tasks.length) {
                changes.tasks = true;
                changes.hasChanges = true;
            }

            if (newData.team.length !== state.previousData.team.length) {
                changes.team = true;
                changes.hasChanges = true;
            }

            if (JSON.stringify(newData.schedule) !== JSON.stringify(state.previousData.schedule)) {
                changes.schedule = true;
                changes.hasChanges = true;
            }

            return changes;
        }

        function updateUI(data, changes = null) {
            updateStats(data, changes?.completion);
            updateTasks(data.tasks, changes?.tasks);
            updateSchedule(data.schedule, changes?.schedule);
            updateProjectInfo(data.project);
            updateTeam(data.team, changes?.team);
        }

        function updateStats(data, hasChanged) {
            const statsHTML = `
                <div class="stat-card ${hasChanged ? 'highlight-change' : ''}">
                    <div class="stat-value">${data.project.completion_percentage || 0}%</div>
                    <div class="stat-label">Completion</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: ${data.project.completion_percentage || 0}%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${data.tasks_summary.total_tasks || 0}</div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${data.tasks_summary.completed_tasks || 0}</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${data.team.length || 0}</div>
                    <div class="stat-label">Team Members</div>
                </div>
            `;
            document.getElementById('stats-container').innerHTML = statsHTML;

            if (hasChanged) {
                setTimeout(() => {
                    const statCard = document.querySelector('.stat-card.highlight-change');
                    if (statCard) statCard.classList.remove('highlight-change');
                }, CONFIG.highlightDuration);
            }
        }

        function updateTasks(tasks, hasChanged) {
            if (tasks && tasks.length > 0) {
                const tasksHTML = tasks.map((task, index) => `
                    <div class="activity-item ${hasChanged && index === 0 ? 'highlight-change' : ''}">
                        <div class="activity-header">
                            <span class="activity-type">${escapeHtml(task.title)}</span>
                            <span class="status-badge status-${task.progress == 100 ? 'completed' : task.progress > 0 ? 'in-progress' : 'pending'}">
                                ${task.progress}%
                            </span>
                        </div>
                        <div class="activity-description">${escapeHtml(task.description || 'No description')}</div>
                        ${task.assigned_to_name ? `<small class="text-muted">Assigned to: ${escapeHtml(task.assigned_to_name)}</small>` : ''}
                        ${task.due_date ? `<small class="text-muted d-block">Due: ${formatDate(task.due_date)}</small>` : ''}
                    </div>
                `).join('');
                document.getElementById('tasks-container').innerHTML = tasksHTML;
            } else {
                document.getElementById('tasks-container').innerHTML = '<p class="text-muted">No tasks yet</p>';
            }
        }

        function updateSchedule(schedule, hasChanged) {
            if (schedule && schedule.length > 0) {
                const scheduleHTML = schedule.map((item, index) => `
                    <div class="milestone-item ${hasChanged && index === 0 ? 'highlight-change' : ''}">
                        <div class="milestone-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="milestone-info">
                            <div class="milestone-name">${escapeHtml(item.task_title || 'Schedule Item')}</div>
                            <div class="milestone-date">
                                ${formatDate(item.start_date)} - ${formatDate(item.end_date)}
                            </div>
                        </div>
                    </div>
                `).join('');
                document.getElementById('schedule-container').innerHTML = scheduleHTML;
            } else {
                document.getElementById('schedule-container').innerHTML = '<p class="text-muted">No schedule items yet</p>';
            }
        }

        function updateProjectInfo(project) {
            const infoHTML = `
                <div class="mb-3">
                    <strong>Status:</strong>
                    <span class="status-badge status-${project.status}">${project.status}</span>
                </div>
                ${project.priority ? `
                <div class="mb-3">
                    <strong>Priority:</strong>
                    <span class="badge bg-${project.priority === 'high' ? 'danger' : project.priority === 'medium' ? 'warning' : 'info'}">
                        ${project.priority.toUpperCase()}
                    </span>
                </div>
                ` : ''}
                ${project.budget ? `
                <div class="mb-3">
                    <strong>Budget:</strong> ${project.budget_formatted || '$' + project.budget}
                </div>
                ` : ''}
                ${project.start_date ? `
                <div class="mb-3">
                    <strong>Start Date:</strong> ${formatDate(project.start_date)}
                </div>
                ` : ''}
                ${project.end_date ? `
                <div class="mb-3">
                    <strong>End Date:</strong> ${formatDate(project.end_date)}
                </div>
                ` : ''}
                ${project.timeline ? `
                <div class="mb-3">
                    <strong>Timeline:</strong> ${escapeHtml(project.timeline)}
                </div>
                ` : ''}
                <div class="mb-3">
                    <strong>Created:</strong> ${formatDate(project.created_at)}
                </div>
            `;
            document.getElementById('info-container').innerHTML = infoHTML;
        }

        function updateTeam(team, hasChanged) {
            if (team && team.length > 0) {
                const teamHTML = team.map((member, index) => `
                    <div class="d-flex align-items-center gap-3 mb-3 ${hasChanged && index === 0 ? 'highlight-change' : ''}">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                            ${member.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-semibold">${escapeHtml(member.name)}</div>
                            <small class="text-muted">${escapeHtml(member.role.toUpperCase())}</small>
                        </div>
                    </div>
                `).join('');
                document.getElementById('team-container').innerHTML = teamHTML;

                if (hasChanged) {
                    setTimeout(() => {
                        const item = document.querySelector('#team-container .highlight-change');
                        if (item) item.classList.remove('highlight-change');
                    }, CONFIG.highlightDuration);
                }
            } else {
                document.getElementById('team-container').innerHTML = '<p class="text-muted">No team members assigned</p>';
            }
        }

        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.update-notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `update-notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, CONFIG.notificationDuration);
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function pollForUpdates() {
            if (!state.isPolling) return;

            const data = await fetchProjectDetails();
            if (!data) return;

            const changes = detectChanges(data);

            if (changes?.hasChanges) {
                const changeMessages = [];
                if (changes.completion) changeMessages.push('Project progress updated');
                if (changes.tasks) changeMessages.push('Tasks updated');
                if (changes.schedule) changeMessages.push('Schedule updated');
                if (changes.team) changeMessages.push('Team members changed');

                showNotification(changeMessages.join(' • '), 'success');
            }

            updateUI(data, changes);
            state.previousData = data;
            state.lastUpdate = data.last_updated;
        }

        function startPolling() {
            state.isPolling = true;
            state.pollTimer = setInterval(pollForUpdates, CONFIG.pollInterval);
        }

        function stopPolling() {
            state.isPolling = false;
            if (state.pollTimer) {
                clearInterval(state.pollTimer);
                state.pollTimer = null;
            }
        }

        async function initialize() {
            const data = await fetchProjectDetails();
            if (data) {
                updateUI(data);
                state.previousData = data;
                state.lastUpdate = data.last_updated;
            } else {
                showNotification('Failed to load project details', 'error');
            }
            startPolling();
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopPolling();
            } else {
                startPolling();
                pollForUpdates();
            }
        });

        window.addEventListener('beforeunload', stopPolling);

        initialize();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
