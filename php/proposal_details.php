<?php
require_once("auth_check.php");
requireRole(["admin"]);
require_once("../config/db.php");

if (session_status() === PHP_SESSION_NONE) session_start();

$proposal_id = $_GET['proposal_id'] ?? null;

if (!$proposal_id) {
    header("Location: proposals_review.php");
    exit;
}

// Fetch proposal and budget details
$sql = "SELECT p.id, p.title, p.description, p.client_id, p.start_date, p.end_date, p.status, p.submitted_at, p.budget,
               p.evaluated_start_date, p.evaluated_end_date, p.evaluation_notes,
               c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company,
               b.id AS budget_id, b.proposed_amount, b.evaluated_amount, b.status as budget_status, b.remarks
        FROM project_proposals p
        LEFT JOIN clients c ON p.client_id = c.id
        LEFT JOIN project_budgets b ON p.id = b.proposal_id
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$proposal_id]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    $_SESSION['error_message'] = "Proposal not found.";
    header("Location: proposals_review.php");
    exit;
}

if (!$proposal['budget_id'] && $proposal['budget']) {
    $createBudgetStmt = $pdo->prepare("
        INSERT INTO project_budgets (proposal_id, proposed_amount, status)
        VALUES (?, ?, 'pending')
    ");
    $createBudgetStmt->execute([$proposal_id, $proposal['budget']]);
    $proposal['budget_id'] = $pdo->lastInsertId();
    $proposal['proposed_amount'] = $proposal['budget'];
}

// Fetch existing budget breakdowns
$breakdownStmt = $pdo->prepare("
    SELECT * FROM budget_breakdowns 
    WHERE budget_id = ? 
    ORDER BY created_at DESC
");
$breakdownStmt->execute([$proposal['budget_id'] ?? 0]);
$breakdowns = $breakdownStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch project managers for approval modal
$pmStmt = $pdo->query("SELECT id, name, email FROM users WHERE role='pm' ORDER BY name");
$projectManagers = $pmStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Details - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .proposal-header {
            background: linear-gradient(135deg, #0a4275 0%, #084980 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .proposal-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .proposal-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .meta-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid white;
        }

        .meta-label {
            font-size: 11px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 16px;
            font-weight: 600;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .card-header-title {
            font-size: 22px;
            font-weight: 700;
            color: #0a4275;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .description-text {
            color: #555;
            line-height: 1.8;
            font-size: 15px;
        }

        .client-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0a4275;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 15px;
            color: #2c3e50;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .budget-section {
            background: linear-gradient(135deg, #f0f8ff 0%, #e7f3ff 100%);
            border-left: 4px solid #0a4275;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .budget-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(10, 66, 117, 0.1);
        }

        .budget-row:last-child {
            border-bottom: none;
        }

        .budget-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .budget-value {
            font-size: 18px;
            font-weight: 700;
            color: #0a4275;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #0a4275;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 66, 117, 0.1);
        }

        .breakdown-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            align-items: end;
        }

        .breakdown-row input {
            width: 100%;
        }

        .btn-add-row {
            background: #0a4275;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-row:hover {
            background: #084980;
            transform: translateY(-2px);
        }

        .btn-remove-row {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .btn-remove-row:hover {
            background: #c82333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #0a4275;
            color: white;
        }

        .btn-primary:hover {
            background: #084980;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-outline-primary {
            background: transparent;
            color: #0a4275;
            border: 2px solid #0a4275;
        }

        .btn-outline-primary:hover {
            background: #0a4275;
            color: white;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #0a4275;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .timeline-date {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .proposal-header {
                padding: 25px;
            }

            .proposal-title {
                font-size: 24px;
            }

            .breakdown-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }
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
                <h1 class="page-title">Proposal Details</h1>
                <p class="page-description">Review comprehensive proposal information and budget evaluation</p>
            </div>
            <a href="proposals_review.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Proposals
            </a>
        </div>

        <!-- Proposal header with key information -->
        <div class="proposal-header">
            <div class="proposal-title">
                <i class="fas fa-file-contract"></i>
                <?php echo htmlspecialchars($proposal['title']); ?>
            </div>
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <span class="status-badge status-<?php echo $proposal['status']; ?>">
                    <?php echo ucfirst($proposal['status']); ?>
                </span>
                <span style="font-size: 14px; opacity: 0.9;">
                    <i class="fas fa-hashtag"></i> Proposal #<?php echo (int)$proposal['id']; ?>
                </span>
            </div>

            <div class="proposal-meta-grid">
                <div class="meta-box">
                    <div class="meta-label">Client Name</div>
                    <div class="meta-value"><?php echo htmlspecialchars($proposal['client_name']); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Company</div>
                    <div class="meta-value"><?php echo htmlspecialchars($proposal['client_company'] ?: 'N/A'); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Submitted Date</div>
                    <div class="meta-value"><?php echo date('M j, Y', strtotime($proposal['submitted_at'])); ?></div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Proposed Timeline</div>
                    <div class="meta-value">
                        <?php 
                        if ($proposal['start_date'] && $proposal['end_date']) {
                            echo date('M j', strtotime($proposal['start_date'])) . ' - ' . date('M j, Y', strtotime($proposal['end_date']));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project description section -->
        <div class="content-card">
            <div class="card-header-title">
                <i class="fas fa-align-left"></i> Project Description
            </div>
            <div class="description-text">
                <?php echo nl2br(htmlspecialchars($proposal['description'] ?: 'No description provided')); ?>
            </div>
        </div>

        <!-- Client information section -->
        <div class="content-card">
            <div class="card-header-title">
                <i class="fas fa-user-tie"></i> Client Information
            </div>
            <div class="client-info-grid">
                <div class="info-block">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($proposal['client_name']); ?></div>
                </div>
                <div class="info-block">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">
                        <a href="mailto:<?php echo htmlspecialchars($proposal['client_email']); ?>" style="color: #0a4275; text-decoration: none;">
                            <?php echo htmlspecialchars($proposal['client_email']); ?>
                        </a>
                    </div>
                </div>
                <div class="info-block">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">
                        <a href="tel:<?php echo htmlspecialchars($proposal['client_phone']); ?>" style="color: #0a4275; text-decoration: none;">
                            <?php echo htmlspecialchars($proposal['client_phone'] ?: 'Not provided'); ?>
                        </a>
                    </div>
                </div>
                <div class="info-block">
                    <div class="info-label">Company</div>
                    <div class="info-value"><?php echo htmlspecialchars($proposal['client_company'] ?: 'Not provided'); ?></div>
                </div>
            </div>
        </div>

        <!-- Evaluated Timeline (Admin) -->
        <div class="content-card">
            <div class="card-header-title">
                <i class="fas fa-calendar-check"></i> Evaluated Timeline
            </div>

            <form method="POST" action="admin_review_timeline.php" id="timelineForm">
                <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="evaluated_start_date"><i class="fas fa-calendar-day"></i> Evaluated Start Date <span style="color: #d42f13;">*</span></label>
                            <input type="date" id="evaluated_start_date" name="evaluated_start_date"
                                   value="<?php echo htmlspecialchars($proposal['evaluated_start_date'] ?? ''); ?>"
                                   <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="evaluated_end_date"><i class="fas fa-calendar-check"></i> Evaluated End Date <span style="color: #d42f13;">*</span></label>
                            <input type="date" id="evaluated_end_date" name="evaluated_end_date"
                                   value="<?php echo htmlspecialchars($proposal['evaluated_end_date'] ?? ''); ?>"
                                   <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?> required>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label for="evaluation_notes"><i class="fas fa-comment-dots"></i> Notes</label>
                    <textarea id="evaluation_notes" name="evaluation_notes" rows="3"
                              placeholder="Add notes about your timeline evaluation..."
                              <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>><?php echo htmlspecialchars($proposal['evaluation_notes'] ?? ''); ?></textarea>
                </div>

                <?php if (!empty($proposal['evaluated_start_date']) && !empty($proposal['evaluated_end_date'])): ?>
                <div class="alert alert-light border-start border-primary border-4" style="border-radius: 8px;">
                    <strong>Current Evaluated Timeline:</strong>
                    <?php echo date('M j, Y', strtotime($proposal['evaluated_start_date'])) . ' - ' . date('M j, Y', strtotime($proposal['evaluated_end_date'])); ?>
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-outline-primary" <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                        <i class="fas fa-save"></i> Save Evaluated Timeline
                    </button>
                </div>
            </form>
        </div>

        <!-- Budget review section integrated from admin_review_budget.php -->
        <div class="content-card">
            <div class="card-header-title">
                <i class="fas fa-calculator"></i> Budget Evaluation & Review
            </div>

            <form method="POST" action="admin_review_budget.php" id="budgetForm">
                <input type="hidden" name="budget_id" value="<?php echo $proposal['budget_id'] ?? ''; ?>">
                <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">

                <div class="form-group">
                    <label for="evaluated_amount">
                        <i class="fas fa-peso-sign"></i> Evaluated Total Budget (₱) <span style="color: #d42f13;">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="evaluated_amount" 
                        name="evaluated_amount" 
                        placeholder="Enter the evaluated total budget"
                        value="<?php echo $proposal['evaluated_amount'] ?? ''; ?>"
                        required
                        min="0"
                        step="0.01"
                        <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                    >
                    <small>Your professional evaluation of the total project cost</small>
                </div>

                <div class="form-group">
                    <label for="remarks">
                        <i class="fas fa-comment"></i> Remarks & Notes
                    </label>
                    <textarea 
                        id="remarks" 
                        name="remarks" 
                        placeholder="Add any notes or remarks about this budget evaluation..."
                        rows="4"
                        <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                    ><?php echo htmlspecialchars($proposal['remarks'] ?? ''); ?></textarea>
                    <small>Optional: Include any adjustments, concerns, or recommendations</small>
                </div>

                <!-- Budget breakdown section -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <h5 style="font-weight: 600; color: #0a4275; margin-bottom: 15px;">
                        <i class="fas fa-list"></i> Budget Breakdown
                    </h5>
                    <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> Add itemized breakdown of costs by category
                    </p>

                    <div id="breakdown">
                        <?php if (!empty($breakdowns)): ?>
                            <?php foreach ($breakdowns as $breakdown): ?>
                            <div class="breakdown-row">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="item_name[]" placeholder="Item Name" 
                                           value="<?php echo htmlspecialchars($breakdown['item_name']); ?>" 
                                           <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                                           required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="number" name="cost[]" placeholder="Estimated Cost" 
                                           value="<?php echo $breakdown['estimated_cost']; ?>" 
                                           <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                                           required min="0" step="0.01" oninput="updateSummary()">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <select name="category[]" 
                                            <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                                            onchange="updateSummary()">
                                        <option value="materials" <?php echo $breakdown['category'] === 'materials' ? 'selected' : ''; ?>>Materials</option>
                                        <option value="labor" <?php echo $breakdown['category'] === 'labor' ? 'selected' : ''; ?>>Labor</option>
                                        <option value="equipment" <?php echo $breakdown['category'] === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                                        <option value="misc" <?php echo $breakdown['category'] === 'misc' ? 'selected' : ''; ?>>Misc</option>
                                    </select>
                                </div>
                                <button type="button" class="btn-remove-row" onclick="removeRow(this)"
                                        <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="breakdown-row">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="item_name[]" placeholder="Item Name"
                                           <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="number" name="cost[]" placeholder="Estimated Cost" 
                                           <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                                           min="0" step="0.01" oninput="updateSummary()">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <select name="category[]" 
                                            <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>
                                            onchange="updateSummary()">
                                        <option value="materials">Materials</option>
                                        <option value="labor">Labor</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="misc">Misc</option>
                                    </select>
                                </div>
                                <button type="button" class="btn-remove-row" onclick="removeRow(this)"
                                        <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn-add-row" onclick="addRow()"
                            <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                        <i class="fas fa-plus"></i> Add Row
                    </button>

                    <!-- Budget summary display -->
                    <div class="budget-section">
                        <div class="budget-row">
                            <span class="budget-label">Client Proposed:</span>
                            <span class="budget-value">₱<?php echo number_format($proposal['proposed_amount'] ?? $proposal['budget'] ?? 0, 2); ?></span>
                        </div>
                        <div class="budget-row">
                            <span class="budget-label">Your Evaluation:</span>
                            <span class="budget-value" id="evaluatedDisplay">₱0.00</span>
                        </div>
                        <div class="budget-row">
                            <span class="budget-label">Difference:</span>
                            <span class="budget-value" id="differenceDisplay">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Action buttons for proposal -->
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary"
                            <?php echo $proposal['status'] === 'approved' ? 'disabled' : ''; ?>>
                        <i class="fas fa-save"></i> Submit Budget Review
                    </button>
                    <?php if ($proposal['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-success" 
                                onclick="openApprovalModal(<?php echo $proposal['id']; ?>, '<?php echo htmlspecialchars($proposal['title'], ENT_QUOTES); ?>')">
                            <i class="fas fa-check"></i> Approve Proposal
                        </button>
                        <a href="proposals_review.php?action=rejected&id=<?php echo $proposal['id']; ?>" class="btn btn-danger" 
                           onclick="return confirm('Reject this proposal?')">
                            <i class="fas fa-times"></i> Reject Proposal
                        </a>
                    <?php endif; ?>
                    <a href="proposals_review.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Approval modal for creating project from proposal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #0a4275 0%, #084980 100%); color: white; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title" id="approvalModalLabel" style="color: white; font-weight: 600;">
                        <i class="fas fa-check-circle"></i> Approve Proposal & Create Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                </div>
                <form method="POST" action="proposals_review.php" id="approvalForm">
                    <div class="modal-body" style="padding: 25px;">
                        <input type="hidden" name="proposal_id" id="proposal_id">
                        
                        <div class="alert alert-info" style="border-radius: 8px; border: none;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Proposal:</strong> <span id="proposal_title"></span>
                        </div>

                        <div class="alert alert-light border-start border-primary border-4" style="border-radius: 8px;">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-calendar-alt text-primary"></i> Project Schedule
                            </h6>
                            <small class="text-muted">
                                Define concrete start and end dates for scheduling and resource allocation.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="assigned_pm" class="form-label" style="font-weight: 600;">
                                Assign to Project Manager <span style="color: #d42f13;">*</span>
                            </label>
                            <select class="form-select" id="assigned_pm" name="assigned_pm" required style="padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px;">
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
                                <label for="start_date" class="form-label" style="font-weight: 600;">
                                    <i class="fas fa-calendar-day text-success"></i> Project Start Date <span style="color: #d42f13;">*</span>
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $proposal['start_date'] ?? date('Y-m-d'); ?>" required
                                       min="<?php echo date('Y-m-d'); ?>" style="padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <small class="text-muted">When the project will begin</small>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label" style="font-weight: 600;">
                                    <i class="fas fa-calendar-check text-danger"></i> Project End Date <span style="color: #d42f13;">*</span>
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $proposal['end_date'] ?? ''; ?>" required
                                       min="<?php echo date('Y-m-d'); ?>" style="padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px;">
                                <small class="text-muted">Expected project completion date</small>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0" id="durationDisplay" style="display: none; border-radius: 8px; border: none;">
                            <i class="fas fa-clock"></i>
                            <strong>Project Duration:</strong> <span id="durationText"></span>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 15px 25px; border-top: 1px solid #f0f0f0;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="approve_proposal" class="btn btn-success" style="background: #28a745; color: white;">
                            <i class="fas fa-check"></i> Approve & Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addRow() {
            const row = document.createElement('div');
            row.className = 'breakdown-row';
            row.innerHTML = `
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" name="item_name[]" placeholder="Item Name" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="number" name="cost[]" placeholder="Estimated Cost" min="0" step="0.01" required oninput="updateSummary()">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <select name="category[]" onchange="updateSummary()">
                        <option value="materials">Materials</option>
                        <option value="labor">Labor</option>
                        <option value="equipment">Equipment</option>
                        <option value="misc">Misc</option>
                    </select>
                </div>
                <button type="button" class="btn-remove-row" onclick="removeRow(this)">
                    <i class="fas fa-trash"></i> Remove
                </button>
            `;
            document.getElementById('breakdown').appendChild(row);
            updateSummary();
        }

        function removeRow(btn) {
            btn.parentElement.remove();
            updateSummary();
        }

        function updateSummary() {
            const costs = document.querySelectorAll('input[name="cost[]"]');
            let total = 0;

            costs.forEach(cost => {
                total += parseFloat(cost.value) || 0;
            });

            const proposed = <?php echo $proposal['proposed_amount'] ?? $proposal['budget'] ?? 0; ?>;
            const difference = total - proposed;

            const evaluatedInput = document.getElementById('evaluated_amount');
            evaluatedInput.value = total.toFixed(2);

            document.getElementById('evaluatedDisplay').textContent = 
                '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            document.getElementById('differenceDisplay').textContent = 
                '₱' + difference.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const diffDisplay = document.getElementById('differenceDisplay');
            diffDisplay.style.color = difference > 0 ? '#d42f13' : '#0a8f2d';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const costInputs = document.querySelectorAll('input[name="cost[]"]');
            costInputs.forEach(input => input.addEventListener('input', updateSummary));
            updateSummary();
        });

        function openApprovalModal(proposalId, proposalTitle) {
            document.getElementById('proposal_id').value = proposalId;
            document.getElementById('proposal_title').textContent = proposalTitle;
            
            document.getElementById('assigned_pm').value = '';
            document.getElementById('start_date').value = '<?php echo $proposal['start_date'] ?? date('Y-m-d'); ?>';
            document.getElementById('end_date').value = '<?php echo $proposal['end_date'] ?? ''; ?>';
            
            updateDuration();
            
            const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }

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
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value <= this.value) {
                endDateInput.value = '';
            }
            updateDuration();
        });

        endDateInput.addEventListener('change', function() {
            updateDuration();
        });

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

        document.getElementById('budgetForm').addEventListener('submit', function(e) {
            const evaluatedAmount = document.getElementById('evaluated_amount').value;
            const status = document.getElementById('status').value;

            if (!evaluatedAmount || !status) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
        });
    </script>

</body>
</html>