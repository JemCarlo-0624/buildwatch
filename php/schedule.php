<?php
require_once("auth_check.php");
require_once("../config/db.php");
requireRole(["pm","admin","worker"]);

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['admin', 'pm'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_schedule') {
        $project_id = $_POST['project_id'] ?? null;
        $task_id = $_POST['task_id'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        if ($start_date && $end_date) {
            $stmt = $pdo->prepare("INSERT INTO schedule (project_id, task_id, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$project_id, $task_id, $start_date, $end_date]);
            header("Location: schedule.php?success=added");
            exit;
        }
    } elseif ($action === 'delete_schedule') {
        $schedule_id = $_POST['schedule_id'] ?? null;
        if ($schedule_id) {
            $stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
            $stmt->execute([$schedule_id]);
            header("Location: schedule.php?success=deleted");
            exit;
        }
    }
}

$view = $_GET['view'] ?? 'list'; // list, calendar, timeline
$filter_project = $_GET['project'] ?? '';
$filter_month = $_GET['month'] ?? date('Y-m');

$query = "
    SELECT 
        s.id, s.project_id, s.task_id, s.start_date, s.end_date,
        t.title AS task_title, t.progress, t.due_date,
        p.name AS project_name, p.status AS project_status,
        u.name AS assigned_to_name
    FROM schedule s
    LEFT JOIN tasks t ON s.task_id = t.id
    LEFT JOIN projects p ON s.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE 1=1
";

$params = [];

if ($role === 'worker') {
    $query .= " AND t.assigned_to = ?";
    $params[] = $user_id;
}

if ($filter_project) {
    $query .= " AND s.project_id = ?";
    $params[] = $filter_project;
}

if ($view === 'calendar' || $view === 'timeline') {
    $query .= " AND DATE_FORMAT(s.start_date, '%Y-%m') = ?";
    $params[] = $filter_month;
}

$query .= " ORDER BY s.start_date ASC, s.end_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$projects = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC")->fetchAll();

if (in_array($role, ['admin', 'pm'])) {
    $tasks = $pdo->query("SELECT t.id, t.title, p.name AS project_name, t.project_id FROM tasks t JOIN projects p ON t.project_id = p.id ORDER BY p.name, t.title")->fetchAll();
}

function getCalendarData($schedules, $month) {
    $calendar = [];
    $firstDay = new DateTime($month . '-01');
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    
    $startDay = clone $firstDay;
    $startDay->modify('first day of this month');
    $startDay->modify('-' . $startDay->format('w') . ' days');
    
    $endDay = clone $lastDay;
    $endDay->modify('+' . (6 - $endDay->format('w')) . ' days');
    
    $current = clone $startDay;
    while ($current <= $endDay) {
        $dateStr = $current->format('Y-m-d');
        $calendar[$dateStr] = [
            'date' => clone $current,
            'events' => []
        ];
        $current->modify('+1 day');
    }
    
    foreach ($schedules as $schedule) {
        $start = new DateTime($schedule['start_date']);
        $end = new DateTime($schedule['end_date']);
        $current = clone $start;
        
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            if (isset($calendar[$dateStr])) {
                $calendar[$dateStr]['events'][] = $schedule;
            }
            $current->modify('+1 day');
        }
    }
    
    return $calendar;
}

function getWorkerName($id) {
    global $pdo;
    if (!$id) return 'Unassigned';
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - BuildWatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Added comprehensive styling for schedule views */
        .view-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .view-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            color: var(--gray);
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .view-tab:hover {
            color: var(--primary);
        }
        
        .view-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .filters-bar {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: var(--dark);
            margin: 0;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        /* Calendar View Styles */
        .calendar-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-nav button {
            padding: 8px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border: 1px solid #e0e0e0;
        }
        
        .calendar-day-header {
            background: var(--primary);
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .calendar-day {
            background: white;
            min-height: 100px;
            padding: 8px;
            position: relative;
        }
        
        .calendar-day.other-month {
            background: #f8f9fa;
            opacity: 0.5;
        }
        
        .calendar-day.today {
            background: #fff8e1;
        }
        
        .calendar-day-number {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .calendar-event {
            background: var(--primary);
            color: white;
            padding: 4px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 3px;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .calendar-event:hover {
            opacity: 0.8;
        }
        
        /* Timeline View Styles */
        .timeline-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .timeline-item {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 19px;
            top: 40px;
            bottom: -30px;
            width: 2px;
            background: #e0e0e0;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-date {
            min-width: 120px;
            text-align: right;
            padding-top: 5px;
        }
        
        .timeline-date-day {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            line-height: 1;
        }
        
        .timeline-date-month {
            font-size: 14px;
            color: var(--gray);
        }
        
        .timeline-marker {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            z-index: 1;
        }
        
        .timeline-content {
            flex: 1;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .timeline-content h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .timeline-content p {
            margin: 0;
            color: var(--gray);
            font-size: 14px;
        }
        
        /* List View Styles */
        .schedule-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .schedule-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .schedule-item:hover {
            background: #f8f9fa;
        }
        
        .schedule-item:last-child {
            border-bottom: none;
        }
        
        .schedule-color-bar {
            width: 4px;
            height: 60px;
            border-radius: 2px;
            margin-right: 20px;
        }
        
        .schedule-info {
            flex: 1;
        }
        
        .schedule-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .schedule-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .schedule-dates {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }
        
        .schedule-date-badge {
            padding: 6px 12px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .schedule-actions {
            display: flex;
            gap: 8px;
        }
        
        .progress-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
        }
        
        .progress-0-30 { background: #ffebee; color: #c62828; }
        .progress-31-70 { background: #fff3e0; color: #ef6c00; }
        .progress-71-99 { background: #e3f2fd; color: #1565c0; }
        .progress-100 { background: #e8f5e9; color: #2e7d32; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 80px;
                padding: 5px;
            }
            
            .calendar-event {
                font-size: 10px;
                padding: 2px 4px;
            }
            
            .timeline-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .timeline-date {
                text-align: left;
            }
            
            .schedule-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body class="sidebar-main-layout">
    
    <!-- Added sidebar navigation -->
    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">
                <?php 
                if ($role === 'admin') echo 'Admin Panel';
                elseif ($role === 'pm') echo 'PM Panel';
                else echo 'Worker Panel';
                ?>
            </div>
            <a href="<?php 
                if ($role === 'admin') echo 'dashboard_admin.php';
                elseif ($role === 'pm') echo 'dashboard_pm.php';
                else echo 'dashboard_worker.php';
            ?>" class="nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?php echo $role === 'worker' ? 'projects_worker.php' : 'projects_list.php'; ?>" class="nav-item">
                <i class="fas fa-project-diagram"></i> Projects
            </a>
            <a href="<?php echo $role === 'worker' ? 'tasks_worker.php' : 'tasks_list.php'; ?>" class="nav-item">
                <i class="fas fa-tasks"></i> Tasks
            </a>
            <a href="schedule.php" class="nav-item active">
                <i class="fas fa-calendar-alt"></i> Schedule
            </a>
            <?php if ($role === 'admin'): ?>
            <a href="users_list.php" class="nav-item">
                <i class="fas fa-users"></i> Users
            </a>
            <?php endif; ?>
            <?php if (in_array($role, ['admin', 'pm'])): ?>
            <a href="proposals_review.php" class="nav-item">
                <i class="fas fa-file-alt"></i> Proposals
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <small class="text-white-50"><?php echo ucfirst($role); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <!-- Added page header with actions -->
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Schedule</h1>
                <p class="page-description">View and manage project and task schedules</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (in_array($role, ['admin', 'pm'])): ?>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <?php 
            if ($_GET['success'] === 'added') echo 'Schedule entry added successfully!';
            elseif ($_GET['success'] === 'deleted') echo 'Schedule entry deleted successfully!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Added view tabs -->
        <div class="view-tabs">
            <button class="view-tab <?php echo $view === 'list' ? 'active' : ''; ?>" onclick="changeView('list')">
                <i class="fas fa-list"></i> List View
            </button>
            <button class="view-tab <?php echo $view === 'calendar' ? 'active' : ''; ?>" onclick="changeView('calendar')">
                <i class="fas fa-calendar"></i> Calendar View
            </button>
            <button class="view-tab <?php echo $view === 'timeline' ? 'active' : ''; ?>" onclick="changeView('timeline')">
                <i class="fas fa-stream"></i> Timeline View
            </button>
        </div>

        <!-- Added filters bar -->
        <div class="filters-bar">
            <div class="filter-group">
                <label><i class="fas fa-project-diagram"></i> Project:</label>
                <select id="projectFilter" onchange="applyFilters()">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $filter_project == $p['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($view === 'calendar' || $view === 'timeline'): ?>
            <div class="filter-group">
                <label><i class="fas fa-calendar-day"></i> Month:</label>
                <input type="month" id="monthFilter" value="<?php echo $filter_month; ?>" onchange="applyFilters()">
            </div>
            <?php endif; ?>
        </div>

        <!-- List View -->
        <?php if ($view === 'list'): ?>
        <div class="schedule-list">
            <?php if (empty($schedules)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Schedule Entries</h3>
                <p>There are no scheduled items to display.</p>
                <?php if (in_array($role, ['admin', 'pm'])): ?>
                <button class="btn btn-primary mt-3" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add Schedule Entry
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($schedules as $s): ?>
                <div class="schedule-item">
                    <div class="schedule-color-bar" style="background: <?php 
                        if ($s['project_status'] === 'completed') echo 'var(--success)';
                        elseif ($s['project_status'] === 'on-hold') echo 'var(--warning)';
                        else echo 'var(--primary)';
                    ?>;"></div>
                    
                    <?php if ($s['progress'] !== null): ?>
                    <div class="progress-indicator progress-<?php 
                        if ($s['progress'] == 100) echo '100';
                        elseif ($s['progress'] > 70) echo '71-99';
                        elseif ($s['progress'] > 30) echo '31-70';
                        else echo '0-30';
                    ?>">
                        <?php echo $s['progress']; ?>%
                    </div>
                    <?php endif; ?>
                    
                    <div class="schedule-info">
                        <div class="schedule-title">
                            <?php echo htmlspecialchars($s['task_title'] ?? 'Project Schedule'); ?>
                        </div>
                        <div class="schedule-meta">
                            <span><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($s['project_name'] ?? 'N/A'); ?></span>
                            <?php if ($s['assigned_to_name']): ?>
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($s['assigned_to_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="schedule-dates">
                        <div class="schedule-date-badge">
                            <i class="fas fa-calendar-day"></i>
                            <?php echo date('M j, Y', strtotime($s['start_date'])); ?>
                        </div>
                        <i class="fas fa-arrow-right" style="color: var(--gray);"></i>
                        <div class="schedule-date-badge">
                            <i class="fas fa-calendar-check"></i>
                            <?php echo date('M j, Y', strtotime($s['end_date'])); ?>
                        </div>
                    </div>
                    
                    <?php if (in_array($role, ['admin', 'pm'])): ?>
                    <div class="schedule-actions">
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this schedule entry?');">
                            <input type="hidden" name="action" value="delete_schedule">
                            <input type="hidden" name="schedule_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Calendar View -->
        <?php if ($view === 'calendar'): ?>
        <div class="calendar-container">
            <div class="calendar-header">
                <h3><?php echo date('F Y', strtotime($filter_month . '-01')); ?></h3>
                <div class="calendar-nav">
                    <button onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            
            <div class="calendar-grid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
                
                <?php 
                $calendarData = getCalendarData($schedules, $filter_month);
                $currentMonth = date('m', strtotime($filter_month . '-01'));
                $today = date('Y-m-d');
                
                foreach ($calendarData as $dateStr => $dayData):
                    $isCurrentMonth = $dayData['date']->format('m') === $currentMonth;
                    $isToday = $dateStr === $today;
                    $classes = ['calendar-day'];
                    if (!$isCurrentMonth) $classes[] = 'other-month';
                    if ($isToday) $classes[] = 'today';
                ?>
                <div class="<?php echo implode(' ', $classes); ?>">
                    <div class="calendar-day-number"><?php echo $dayData['date']->format('j'); ?></div>
                    <?php foreach (array_slice($dayData['events'], 0, 3) as $event): ?>
                    <div class="calendar-event" title="<?php echo htmlspecialchars($event['task_title'] ?? $event['project_name']); ?>">
                        <?php echo htmlspecialchars($event['task_title'] ?? $event['project_name']); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($dayData['events']) > 3): ?>
                    <div class="calendar-event" style="background: var(--gray);">
                        +<?php echo count($dayData['events']) - 3; ?> more
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline View -->
        <?php if ($view === 'timeline'): ?>
        <div class="timeline-container">
            <?php if (empty($schedules)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Schedule Entries</h3>
                <p>There are no scheduled items for this month.</p>
            </div>
            <?php else: ?>
                <?php foreach ($schedules as $s): ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <div class="timeline-date-day"><?php echo date('d', strtotime($s['start_date'])); ?></div>
                        <div class="timeline-date-month"><?php echo date('M Y', strtotime($s['start_date'])); ?></div>
                    </div>
                    <div class="timeline-marker">
                        <i class="fas fa-<?php echo $s['task_id'] ? 'tasks' : 'project-diagram'; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <h4><?php echo htmlspecialchars($s['task_title'] ?? $s['project_name']); ?></h4>
                        <p>
                            <strong><?php echo htmlspecialchars($s['project_name']); ?></strong>
                            <?php if ($s['assigned_to_name']): ?>
                            • Assigned to <?php echo htmlspecialchars($s['assigned_to_name']); ?>
                            <?php endif; ?>
                            <?php if ($s['progress'] !== null): ?>
                            • Progress: <?php echo $s['progress']; ?>%
                            <?php endif; ?>
                        </p>
                        <p>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M j, Y', strtotime($s['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($s['end_date'])); ?>
                            (<?php 
                            $start = new DateTime($s['start_date']);
                            $end = new DateTime($s['end_date']);
                            echo $start->diff($end)->days + 1;
                            ?> days)
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Schedule Modal -->
    <?php if (in_array($role, ['admin', 'pm'])): ?>
    <div class="modal-overlay" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add Schedule Entry</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_schedule">
                
                <div class="form-group mb-3">
                    <label class="form-label">Task (Optional)</label>
                    <select name="task_id" id="taskSelect" class="form-input" onchange="updateProjectFromTask()">
                        <option value="">Select a task</option>
                        <?php foreach ($tasks as $t): ?>
                        <option value="<?php echo $t['id']; ?>" data-project="<?php echo $t['project_id']; ?>">
                            <?php echo htmlspecialchars($t['project_name'] . ' - ' . $t['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <label class="form-label">Project</label>
                    <select name="project_id" id="projectSelect" class="form-input" required>
                        <option value="">Select a project</option>
                        <?php foreach ($projects as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-grid mb-3">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Schedule
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeView(view) {
            const url = new URL(window.location);
            url.searchParams.set('view', view);
            window.location = url;
        }
        
        function applyFilters() {
            const url = new URL(window.location);
            const project = document.getElementById('projectFilter').value;
            const month = document.getElementById('monthFilter')?.value;
            
            if (project) url.searchParams.set('project', project);
            else url.searchParams.delete('project');
            
            if (month) url.searchParams.set('month', month);
            
            window.location = url;
        }
        
        function changeMonth(delta) {
            const currentMonth = '<?php echo $filter_month; ?>';
            const date = new Date(currentMonth + '-01');
            date.setMonth(date.getMonth() + delta);
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            
            const url = new URL(window.location);
            url.searchParams.set('month', `${year}-${month}`);
            window.location = url;
        }
        
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        function updateProjectFromTask() {
            const taskSelect = document.getElementById('taskSelect');
            const projectSelect = document.getElementById('projectSelect');
            const selectedOption = taskSelect.options[taskSelect.selectedIndex];
            
            if (selectedOption.value) {
                const projectId = selectedOption.getAttribute('data-project');
                projectSelect.value = projectId;
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('addModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>
</body>
</html>
