<?php
require_once("auth_check.php");
requireRole("worker");
require_once("../config/db.php");

$user_id = $_SESSION['user_id'];

// Fetch projects assigned to this worker with additional details
$stmt = $pdo->prepare("
    SELECT p.*, 
           u.name as creator_name,
           COUNT(DISTINCT t.id) as total_tasks,
           SUM(CASE WHEN t.progress = 100 THEN 1 ELSE 0 END) as completed_tasks,
           AVG(t.progress) as avg_progress
    FROM project_assignments pa 
    JOIN projects p ON pa.project_id = p.id
    JOIN users u ON p.created_by = u.id
    LEFT JOIN tasks t ON t.project_id = p.id AND t.assigned_to = ?
    WHERE pa.user_id = ?
    GROUP BY p.id, u.name
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$projects = $stmt->fetchAll();

// Calculate statistics
$totalProjects = count($projects);
$activeProjects = count(array_filter($projects, function($p) { return strtolower($p['status']) === 'active'; }));
$completedProjects = count(array_filter($projects, function($p) { return strtolower($p['status']) === 'completed'; }));
$onHoldProjects = count(array_filter($projects, function($p) { return strtolower($p['status']) === 'on-hold'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .project-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border-left: 4px solid var(--primary);
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .project-card.status-active {
            border-left-color: var(--success);
        }

        .project-card.status-completed {
            border-left-color: var(--primary);
        }

        .project-card.status-on-hold {
            border-left-color: var(--warning);
        }

        .project-card-header {
            padding: 20px 20px 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .project-card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .project-id {
            font-size: 13px;
            color: var(--gray);
            font-weight: 500;
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .project-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .project-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--gray);
        }

        .meta-item i {
            width: 18px;
            color: var(--primary);
            text-align: center;
        }

        .meta-item strong {
            color: var(--dark);
        }

        .project-progress-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .progress-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
        }

        .progress-percentage {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
        }

        .progress-bar-wrapper {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .task-summary {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--gray);
        }

        .project-actions {
            display: flex;
            gap: 8px;
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-on-hold { background-color: #f8d7da; color: #721c24; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-tab:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .project-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body class="sidebar-main-layout">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Worker Panel</div>
            <a href="dashboard_worker.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="tasks_worker.php" class="nav-item"><i class="fas fa-tasks"></i> My Tasks</a>
            <a href="projects_worker.php" class="nav-item active"><i class="fas fa-project-diagram"></i> My Projects</a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'W', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Worker'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">My Projects</h1>
                <p class="page-description">View and manage your assigned construction projects</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard_worker.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="tasks_worker.php" class="btn btn-primary">
                    <i class="fas fa-tasks"></i> View My Tasks
                </a>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-project-diagram"></i></div>
                <div class="stat-value"><?php echo number_format($totalProjects); ?></div>
                <div class="stat-label">TOTAL PROJECTS</div>
                <div class="stat-change positive">Assigned to you</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-play-circle"></i></div>
                <div class="stat-value"><?php echo number_format($activeProjects); ?></div>
                <div class="stat-label">ACTIVE PROJECTS</div>
                <div class="stat-change positive">In progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-accent"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($completedProjects); ?></div>
                <div class="stat-label">COMPLETED</div>
                <div class="stat-change positive">Finished</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-secondary"><i class="fas fa-pause-circle"></i></div>
                <div class="stat-value"><?php echo number_format($onHoldProjects); ?></div>
                <div class="stat-label">ON HOLD</div>
                <div class="stat-change negative">Paused</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">
                All Projects (<?php echo $totalProjects; ?>)
            </button>
            <button class="filter-tab" data-filter="active">
                Active (<?php echo $activeProjects; ?>)
            </button>
            <button class="filter-tab" data-filter="completed">
                Completed (<?php echo $completedProjects; ?>)
            </button>
            <button class="filter-tab" data-filter="on-hold">
                On Hold (<?php echo $onHoldProjects; ?>)
            </button>
        </div>

        <!-- Projects Grid -->
        <?php if (!empty($projects)): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $p): ?>
                    <?php 
                    $statusClass = strtolower(str_replace(' ', '-', $p['status'] ?? 'unknown'));
                    $avgProgress = round($p['avg_progress'] ?? 0);
                    $totalTasks = (int)($p['total_tasks'] ?? 0);
                    $completedTasks = (int)($p['completed_tasks'] ?? 0);
                    
                    $progressColor = $avgProgress == 100 ? 'var(--success)' : ($avgProgress > 50 ? 'var(--primary)' : 'var(--warning)');
                    ?>
                    <div class="project-card status-<?php echo $statusClass; ?>" data-status="<?php echo $statusClass; ?>">
                        <div class="project-card-header">
                            <div class="project-card-title">
                                <span><?php echo htmlspecialchars($p['name'] ?? 'Untitled Project'); ?></span>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($p['status'] ?? 'Unknown'); ?>
                                </span>
                            </div>
                            <div class="project-id">Project #<?php echo (int)$p['id']; ?></div>
                        </div>
                        
                        <div class="card-body">
                            <p class="project-description">
                                <?php echo htmlspecialchars($p['description'] ?? 'No description available'); ?>
                            </p>
                            
                            <?php if ($totalTasks > 0): ?>
                            <div class="project-progress-section">
                                <div class="progress-header">
                                    <span class="progress-label">Overall Progress</span>
                                    <span class="progress-percentage"><?php echo $avgProgress; ?>%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill" style="width: <?php echo $avgProgress; ?>%; background: <?php echo $progressColor; ?>;"></div>
                                </div>
                                <div class="task-summary">
                                    <span><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks completed</span>
                                    <span><?php echo $totalTasks - $completedTasks; ?> remaining</span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="project-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>Project Manager: <strong><?php echo htmlspecialchars($p['creator_name'] ?? 'Unknown'); ?></strong></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Created: <strong><?php echo date('M j, Y', strtotime($p['created_at'])); ?></strong></span>
                                </div>
                                <?php if (!empty($p['location'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Location: <strong><?php echo htmlspecialchars($p['location']); ?></strong></span>
                                </div>
                                <?php endif; ?>
                                <div class="meta-item">
                                    <i class="fas fa-tasks"></i>
                                    <span>Your Tasks: <strong><?php echo $totalTasks; ?> assigned</strong></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="project-actions">
                            <a href="tasks_worker.php?project=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary flex-fill">
                                <i class="fas fa-tasks"></i> View Tasks
                            </a>
                            <a href="projects_details.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fas fa-info-circle"></i> Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-project-diagram"></i>
                <h3>No Projects Assigned</h3>
                <p>You don't have any projects assigned to you yet. Check back later or contact your project manager.</p>
                <a href="dashboard_worker.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        const filterTabs = document.querySelectorAll('.filter-tab');
        const projectCards = document.querySelectorAll('.project-card');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter projects
                projectCards.forEach(card => {
                    const status = card.getAttribute('data-status');
                    
                    if (filter === 'all' || status === filter) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
