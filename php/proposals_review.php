<?php
// Include auth_check first — defines requireRole()
require_once("auth_check.php"); // <-- important!
requireRole(["pm","admin"]);
require_once("../config/db.php");

if (isset($_POST['approve_proposal'])) {
    $proposalId = (int)$_POST['proposal_id'];
    $assignedPm = (int)$_POST['assigned_pm'];
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $category = !empty($_POST['category']) ? $_POST['category'] : null;

    try {
        $pdo->beginTransaction();

        // Update proposal status to approved
        $stmt = $pdo->prepare("UPDATE project_proposals SET status='approved' WHERE id=?");
        $stmt->execute([$proposalId]);

        // Get proposal details
        $proposalStmt = $pdo->prepare("SELECT * FROM project_proposals WHERE id=?");
        $proposalStmt->execute([$proposalId]);
        $proposal = $proposalStmt->fetch(PDO::FETCH_ASSOC);

        if (empty($endDate)) {
            // If no end date provided, default to 6 months from start
            $endDate = date('Y-m-d', strtotime($startDate . ' + 6 months'));
        }

        // Create project from proposal with automatic schedule generation
        $insertProject = $pdo->prepare("
            INSERT INTO projects (name, description, status, created_by, client_id, category, start_date, end_date) 
            VALUES (?, ?, 'planning', ?, ?, ?, ?, ?)
        ");
        $insertProject->execute([
            $proposal['title'],
            $proposal['description'],
            $assignedPm,
            $proposal['client_id'],
            $category,
            $startDate,
            $endDate
        ]);

        $projectId = $pdo->lastInsertId();

        // Assign project to the selected PM
        $assignStmt = $pdo->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
        $assignStmt->execute([$projectId, $assignedPm]);

        $pdo->commit();
        
        $_SESSION['success_message'] = "Proposal approved and project created successfully! Project schedule set from " . date('M j, Y', strtotime($startDate)) . " to " . date('M j, Y', strtotime($endDate)) . ".";
        header("Location: proposals_review.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error approving proposal: " . $e->getMessage();
        header("Location: proposals_review.php");
        exit;
    }
}

// Reject proposal
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'rejected') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE project_proposals SET status='rejected' WHERE id=?");
    $stmt->execute([$id]);
    
    $_SESSION['success_message'] = "Proposal rejected successfully.";
    header("Location: proposals_review.php");
    exit;
}

// Fetch proposals
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

$sql = "SELECT pp.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company 
        FROM project_proposals pp 
        LEFT JOIN clients c ON pp.client_id = c.id 
        WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " AND pp.status = ?";
    $params[] = $filterStatus;
}

if (!empty($searchQuery)) {
    $sql .= " AND (pp.title LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY pp.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proposals = $stmt->fetchAll();

$pmStmt = $pdo->query("SELECT id, name, email FROM users WHERE role='pm' ORDER BY name");
$projectManagers = $pmStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals")->fetchColumn();
$pendingProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals WHERE status='pending'")->fetchColumn();
$approvedProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals WHERE status='approved'")->fetchColumn();
$rejectedProposals = $pdo->query("SELECT COUNT(*) FROM project_proposals WHERE status='rejected'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Project Proposals - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .stat-icon-primary { background: rgba(10, 99, 165, 0.1); color: var(--primary); }
        .stat-icon-warning { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .stat-icon-success { background: rgba(46, 204, 113, 0.1); color: var(--success); }
        .stat-icon-danger { background: rgba(212, 47, 19, 0.1); color: var(--accent); }

        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .filters-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            display: block;
        }

        .proposals-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .proposals-header {
            padding: 20px 25px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .proposals-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .proposals-title i {
            color: var(--primary);
        }

        .proposal-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }

        .proposal-item:hover {
            background-color: #f8f9fa;
        }

        .proposal-item:last-child {
            border-bottom: none;
        }

        .proposal-header-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
            gap: 15px;
        }

        .proposal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 5px 0;
        }

        .proposal-id {
            font-size: 12px;
            color: var(--gray);
            font-weight: 500;
        }

        .proposal-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .proposal-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--gray);
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .proposal-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }

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

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
        }

        @media (max-width: 768px) {
            .proposal-header-row {
                flex-direction: column;
            }

            .proposal-actions {
                width: 100%;
            }

            .proposal-actions .btn {
                flex: 1;
            }

            .filters-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }
        }

        /* Added modal styles */
        .modal-backdrop.show {
            opacity: 0.5;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, #0a4d8a 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 20px 25px;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-label .required {
            color: var(--accent);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }
    </style>
</head>
<body class="sidebar-main-layout">

    <div class="sidebar">
        <div class="logo">
            <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">
                <?php echo $_SESSION['role'] === 'admin' ? 'Admin Panel' : 'PM Panel'; ?>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="dashboard_admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item active"><i class="fas fa-lightbulb"></i> Proposals</a>
                <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
            <?php else: ?>
                <a href="dashboard_pm.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
                <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="proposals_review.php" class="nav-item active"><i class="fas fa-lightbulb"></i> Proposals</a>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 40px; height: 40px;">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                    <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h1 class="page-title">Project Proposals</h1>
                <p class="page-description">Review and manage client project proposals</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo $_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_pm.php'; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Added success/error message alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary"><i class="fas fa-lightbulb"></i></div>
                <div class="stat-value"><?php echo number_format($totalProposals); ?></div>
                <div class="stat-label">TOTAL PROPOSALS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-warning"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo number_format($pendingProposals); ?></div>
                <div class="stat-label">PENDING REVIEW</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo number_format($approvedProposals); ?></div>
                <div class="stat-label">APPROVED</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-danger"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?php echo number_format($rejectedProposals); ?></div>
                <div class="stat-label">REJECTED</div>
            </div>
        </div>

        <div class="filters-card">
            <form method="GET" action="proposals_review.php">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by title, client name, or email..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>
                    <div class="filter-group" style="max-width: 200px;">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="proposals_review.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="proposals-card">
            <div class="proposals-header">
                <h2 class="proposals-title">
                    <i class="fas fa-list"></i>
                    Proposals List
                </h2>
                <span class="badge bg-primary"><?php echo count($proposals); ?> Results</span>
            </div>
            
            <?php if (!empty($proposals)): ?>
                <?php foreach ($proposals as $p): ?>
                <a href="<?php echo $p['status'] === 'pending' ? 'admin_review_budget.php?proposal_id=' . $p['id'] : 'proposal_details.php?proposal_id=' . $p['id']; ?>" style="text-decoration: none; color: inherit; display: block;">
                <div class="proposal-item" style="cursor: pointer; transition: all 0.2s ease;">
                    <div class="proposal-header-row">
                        <div>
                            <h3 class="proposal-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                            <div class="proposal-id">Proposal ID: #<?php echo (int)$p['id']; ?></div>
                        </div>
                        <div class="status-badge status-<?php echo $p['status']; ?>">
                            <?php echo ucfirst($p['status']); ?>
                        </div>
                    </div>

                    <div class="proposal-meta">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span><strong>Client:</strong> <?php echo htmlspecialchars($p['client_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($p['client_email']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span><strong>Phone:</strong> <?php echo htmlspecialchars($p['client_phone']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-building"></i>
                            <span><strong>Company:</strong> <?php echo htmlspecialchars($p['client_company']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><strong>Submitted:</strong> <?php echo date('M j, Y', strtotime($p['submitted_at'])); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($p['description'])): ?>
                    <div class="proposal-description">
                        <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Proposals Found</h3>
                    <p>
                        <?php if (!empty($searchQuery) || $filterStatus !== 'all'): ?>
                            No proposals match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            There are no project proposals to review at this time.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Added approval modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalLabel">
                        <i class="fas fa-check-circle"></i>
                        Approve Proposal & Create Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="proposals_review.php" id="approvalForm">
                    <div class="modal-body">
                        <input type="hidden" name="proposal_id" id="proposal_id">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Proposal:</strong> <span id="proposal_title"></span>
                        </div>

                        <div class="alert alert-light border-start border-primary border-4">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-calendar-alt text-primary"></i> Project Schedule
                            </h6>
                            <small class="text-muted">
                                Define concrete start and end dates for scheduling and resource allocation. 
                                Dates from the client's proposal will be pre-filled if available.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_pm" class="form-label">
                                Assign to Project Manager <span class="required">*</span>
                            </label>
                            <select class="form-select" id="assigned_pm" name="assigned_pm" required>
                                <option value="">Select a Project Manager</option>
                                <?php foreach ($projectManagers as $pm): ?>
                                    <option value="<?php echo $pm['id']; ?>">
                                        <?php echo htmlspecialchars($pm['name']); ?> (<?php echo htmlspecialchars($pm['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">The selected PM will be assigned to manage this project</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar-day text-success"></i> Project Start Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">When the project will begin</small>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-calendar-check text-danger"></i> Project End Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required
                                       min="<?php echo date('Y-m-d'); ?>">
                                <small class="text-muted">Expected project completion date</small>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0" id="durationDisplay" style="display: none;">
                            <i class="fas fa-clock"></i>
                            <strong>Project Duration:</strong> <span id="durationText"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="approve_proposal" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve & Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Store proposal data for modal
        const proposalsData = <?php echo json_encode($proposals); ?>;

        function openApprovalModal(proposalId, proposalTitle) {
            document.getElementById('proposal_id').value = proposalId;
            document.getElementById('proposal_title').textContent = proposalTitle;
            
            // Find the proposal data
            const proposal = proposalsData.find(p => p.id == proposalId);
            
            // Reset form fields
            document.getElementById('assigned_pm').value = '';
            document.getElementById('start_date').value = '';
            
            // Pre-fill dates from client's proposal if available
            if (proposal) {
                if (proposal.start_date) {
                    document.getElementById('start_date').value = proposal.start_date;
                }
                if (proposal.end_date) {
                    document.getElementById('end_date').value = proposal.end_date;
                    updateDuration(); // Calculate and show duration
                } else {
                    document.getElementById('end_date').value = '';
                }
            } else {
                document.getElementById('end_date').value = '';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }

        // Date validation and duration calculation
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const durationDisplay = document.getElementById('durationDisplay');
        const durationText = document.getElementById('durationText');

        function updateDuration() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end > start) {
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    const diffWeeks = Math.floor(diffDays / 7);
                    const diffMonths = Math.floor(diffDays / 30);
                    
                    let durationString = '';
                    if (diffMonths > 0) {
                        durationString = `${diffMonths} month${diffMonths > 1 ? 's' : ''} (${diffDays} days)`;
                    } else if (diffWeeks > 0) {
                        durationString = `${diffWeeks} week${diffWeeks > 1 ? 's' : ''} (${diffDays} days)`;
                    } else {
                        durationString = `${diffDays} day${diffDays > 1 ? 's' : ''}`;
                    }
                    
                    durationText.textContent = durationString;
                    durationDisplay.style.display = 'block';
                } else {
                    durationDisplay.style.display = 'none';
                }
            } else {
                durationDisplay.style.display = 'none';
            }
        }

        startDateInput.addEventListener('change', function() {
            // Update minimum end date
            endDateInput.min = this.value;
            
            // Clear end date if it's before start date
            if (endDateInput.value && endDateInput.value <= this.value) {
                endDateInput.value = '';
            }
            
            updateDuration();
        });

        endDateInput.addEventListener('change', function() {
            updateDuration();
        });

        // Form validation
        document.getElementById('approvalForm').addEventListener('submit', function(e) {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Please provide both start and end dates for the project.');
                return false;
            }
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end <= start) {
                e.preventDefault();
                alert('End date must be after start date.');
                return false;
            }
        });
    </script>
</body>
</html>
