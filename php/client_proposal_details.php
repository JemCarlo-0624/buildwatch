<?php
session_start();
require_once("../config/db.php");

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: client_login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$proposal_id = $_GET['proposal_id'] ?? null;

if (!$proposal_id) {
    header('Location: client_dashboard.php');
    exit;
}

// Fetch proposal and budget details
$sql = "SELECT pp.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company,
               pb.id AS budget_id, pb.proposed_amount, pb.evaluated_amount, pb.status as budget_status, pb.admin_comment
        FROM project_proposals pp
        LEFT JOIN clients c ON pp.client_id = c.id
        LEFT JOIN project_budgets pb ON pp.id = pb.proposal_id
        WHERE pp.id = ? AND pp.client_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$proposal_id, $client_id]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) {
    $_SESSION['error_message'] = "Proposal not found.";
    header('Location: client_dashboard.php');
    exit;
}

// Fetch budget breakdowns
$breakdownStmt = $pdo->prepare("
    SELECT * FROM budget_breakdowns 
    WHERE budget_id = ? 
    ORDER BY created_at DESC
");
$breakdownStmt->execute([$proposal['budget_id'] ?? 0]);
$breakdowns = $breakdownStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Details - BuildWatch</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/client-dashboard.css">
    <style>
        .proposal-details-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #152d47 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .proposal-details-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .details-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .details-meta-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid white;
        }

        .details-meta-label {
            font-size: 11px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .details-meta-value {
            font-size: 16px;
            font-weight: 600;
        }

        .details-content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .details-card-header {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a5f;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .info-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #1e3a5f;
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
        .status-pending_client_decision { background: #cfe2ff; color: #084298; }

        .budget-comparison-section {
            background: linear-gradient(135deg, #f0f8ff 0%, #e7f3ff 100%);
            border-left: 4px solid #1e3a5f;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .budget-comparison-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(30, 58, 95, 0.1);
        }

        .budget-comparison-row:last-child {
            border-bottom: none;
        }

        .budget-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .budget-value {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a5f;
        }

        .budget-breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .budget-breakdown-table thead {
            background: #f8f9fa;
        }

        .budget-breakdown-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e6ed;
        }

        .budget-breakdown-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e6ed;
        }

        .budget-breakdown-table tbody tr:hover {
            background: #f8f9fa;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .category-materials { background: rgba(13, 148, 136, 0.1); color: #0d9488; }
        .category-labor { background: rgba(30, 58, 95, 0.1); color: #1e3a5f; }
        .category-equipment { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .category-misc { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .admin-comment-box {
            background: #e7f3ff;
            border-left: 4px solid #0d9488;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .admin-comment-label {
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-comment-text {
            color: #555;
            line-height: 1.6;
        }

        .timeline-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .timeline-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .timeline-icon {
            font-size: 24px;
            color: #0d9488;
            margin-bottom: 8px;
        }

        .timeline-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .timeline-date {
            font-size: 16px;
            font-weight: 600;
            color: #1e3a5f;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            flex-wrap: wrap;
        }

        .btn-custom {
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
            min-height: 44px;
        }

        .btn-primary-custom {
            background: #1e3a5f;
            color: white;
        }

        .btn-primary-custom:hover {
            background: #152d47;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary-custom {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary-custom:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .empty-budget-state {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #666;
        }

        .empty-budget-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .proposal-details-header {
                padding: 25px;
            }

            .proposal-details-title {
                font-size: 24px;
            }

            .details-content-card {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn-custom {
                width: 100%;
                justify-content: center;
            }

            .budget-breakdown-table {
                font-size: 14px;
            }

            .budget-breakdown-table th,
            .budget-breakdown-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
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
            <a href="client_logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <!-- Back button -->
        <div style="margin-bottom: 20px;">
            <a href="client_dashboard.php" class="btn-custom btn-secondary-custom">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Proposal header with key information -->
        <div class="proposal-details-header">
            <div class="proposal-details-title">
                <i class="fas fa-file-contract"></i>
                <?php echo htmlspecialchars($proposal['title']); ?>
            </div>
            <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <span class="status-badge status-<?php echo htmlspecialchars($proposal['status'] ?? 'pending'); ?>">
                    <?php echo ucfirst(htmlspecialchars($proposal['status'] ?? 'pending')); ?>
                </span>
                <?php if (($proposal['budget_status'] ?? '') !== ''): ?>
                    <span class="status-badge status-<?php echo htmlspecialchars($proposal['budget_status']); ?>">
                        Budget: <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($proposal['budget_status']))); ?>
                    </span>
                <?php endif; ?>
                <span style="font-size: 14px; opacity: 0.9;">
                    <i class="fas fa-hashtag"></i> Proposal #<?php echo (int)$proposal['id']; ?>
                </span>
            </div>

            <div class="details-meta-grid">
                <div class="details-meta-box">
                    <div class="details-meta-label">Submitted Date</div>
                    <div class="details-meta-value"><?php echo date('M j, Y', strtotime($proposal['submitted_at'])); ?></div>
                </div>
                <div class="details-meta-box">
                    <div class="details-meta-label">Proposed Timeline</div>
                    <div class="details-meta-value">
                        <?php 
                        if ($proposal['start_date'] && $proposal['end_date']) {
                            echo date('M j', strtotime($proposal['start_date'])) . ' - ' . date('M j, Y', strtotime($proposal['end_date']));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </div>
                </div>
                <div class="details-meta-box">
                    <div class="details-meta-label">Project Status</div>
                    <div class="details-meta-value"><?php echo ucfirst(htmlspecialchars($proposal['status'] ?? 'pending')); ?></div>
                </div>
            </div>
        </div>

        <!-- Project description section -->
        <div class="details-content-card">
            <div class="details-card-header">
                <i class="fas fa-align-left"></i> Project Description
            </div>
            <div class="description-text">
                <?php echo nl2br(htmlspecialchars($proposal['description'] ?: 'No description provided')); ?>
            </div>
        </div>

        <!-- Project objectives and scope section -->
        <div class="details-content-card">
            <div class="details-card-header">
                <i class="fas fa-bullseye"></i> Project Objectives & Scope
            </div>
            <div class="info-grid">
                <div class="info-block">
                    <div class="info-label">Project Scope</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($proposal['scope'] ?? 'Comprehensive project implementation'); ?>
                    </div>
                </div>
                <div class="info-block">
                    <div class="info-label">Expected Deliverables</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($proposal['deliverables'] ?? 'As per project specifications'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline section -->
        <div class="details-content-card">
            <div class="details-card-header">
                <i class="fas fa-calendar-alt"></i> Project Timeline
            </div>
            <div class="timeline-section">
                <div class="timeline-box">
                    <div class="timeline-icon"><i class="fas fa-play-circle"></i></div>
                    <div class="timeline-label">Start Date</div>
                    <div class="timeline-date">
                        <?php echo $proposal['start_date'] ? date('M j, Y', strtotime($proposal['start_date'])) : 'TBD'; ?>
                    </div>
                </div>
                <div class="timeline-box">
                    <div class="timeline-icon"><i class="fas fa-flag-checkered"></i></div>
                    <div class="timeline-label">End Date</div>
                    <div class="timeline-date">
                        <?php echo $proposal['end_date'] ? date('M j, Y', strtotime($proposal['end_date'])) : 'TBD'; ?>
                    </div>
                </div>
                <div class="timeline-box">
                    <div class="timeline-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="timeline-label">Duration</div>
                    <div class="timeline-date">
                        <?php 
                        if ($proposal['start_date'] && $proposal['end_date']) {
                            $start = new DateTime($proposal['start_date']);
                            $end = new DateTime($proposal['end_date']);
                            $interval = $start->diff($end);
                            echo $interval->days . ' days';
                        } else {
                            echo 'TBD';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Evaluation & Review Section -->
        <div class="details-content-card">
            <div class="details-card-header">
                <i class="fas fa-calculator"></i> Budget Breakdown & Evaluation
            </div>

            <?php if ($proposal['budget_id']): ?>
                <!-- Budget comparison -->
                <div class="budget-comparison-section">
                    <div class="budget-comparison-row">
                        <span class="budget-label">Your Proposed Budget:</span>
                        <span class="budget-value">₱<?php echo number_format($proposal['proposed_amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php if ($proposal['evaluated_amount']): ?>
                        <div class="budget-comparison-row">
                            <span class="budget-label">Admin Evaluation:</span>
                            <span class="budget-value">₱<?php echo number_format($proposal['evaluated_amount'], 2); ?></span>
                        </div>
                        <div class="budget-comparison-row">
                            <span class="budget-label">Difference:</span>
                            <span class="budget-value" style="color: <?php echo ($proposal['evaluated_amount'] - ($proposal['proposed_amount'] ?? 0)) > 0 ? '#d42f13' : '#0a8f2d'; ?>;">
                                ₱<?php echo number_format(abs($proposal['evaluated_amount'] - ($proposal['proposed_amount'] ?? 0)), 2); ?>
                                <small style="font-size: 12px; margin-left: 8px;">
                                    <?php echo ($proposal['evaluated_amount'] - ($proposal['proposed_amount'] ?? 0)) > 0 ? '(Higher)' : '(Lower)'; ?>
                                </small>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Budget breakdown table -->
                <?php if (!empty($breakdowns)): ?>
                    <h6 style="margin-top: 25px; margin-bottom: 15px; font-weight: 600; color: #1e3a5f;">
                        <i class="fas fa-list"></i> Itemized Budget Breakdown
                    </h6>
                    <table class="budget-breakdown-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Estimated Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($breakdowns as $breakdown): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($breakdown['item_name']); ?></td>
                                    <td>
                                        <span class="category-badge category-<?php echo htmlspecialchars($breakdown['category']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($breakdown['category'])); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; color: #1e3a5f;">
                                        ₱<?php echo number_format($breakdown['estimated_cost'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Admin comment -->
                <?php if ($proposal['admin_comment']): ?>
                    <div class="admin-comment-box">
                        <div class="admin-comment-label">
                            <i class="fas fa-comment"></i> Admin Notes & Remarks
                        </div>
                        <div class="admin-comment-text">
                            <?php echo nl2br(htmlspecialchars($proposal['admin_comment'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-budget-state">
                    <i class="fas fa-inbox"></i>
                    <p>Budget evaluation is pending. The admin team will review and provide a detailed budget breakdown soon.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action buttons -->
        <div class="action-buttons">
            <a href="client_dashboard.php" class="btn-custom btn-secondary-custom">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <?php if (($proposal['budget_status'] ?? '') === 'pending_client_decision' && $proposal['budget_id']): ?>
                <button class="btn-custom btn-primary-custom" onclick="alert('Budget decision functionality would be implemented here.')">
                    <i class="fas fa-check"></i> Review Budget Decision
                </button>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
