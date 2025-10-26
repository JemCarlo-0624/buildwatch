<?php
session_start();
require_once(__DIR__ . "/../../config/db.php");

// Check if client is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: /client/login');
    exit;
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'] ?? 'Client';
$client_email = $_SESSION['client_email'] ?? '';

$stmt = $pdo->prepare("
    SELECT pp.*, c.name as client_name, c.email as client_email,
           COALESCE(pb.id, 0) AS budget_id,
           COALESCE(pb.evaluated_amount, 0) AS admin_evaluation,
           pb.admin_comment,
           COALESCE(pb.status, 'pending') AS budget_status
    FROM project_proposals pp
    JOIN clients c ON pp.client_id = c.id
    LEFT JOIN project_budgets pb ON pp.id = pb.proposal_id
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- New unified design system -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/notifications.css">
</head>
<body>
    <!-- Improved header with better spacing and visual hierarchy -->
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
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end" id="notificationsMenu">
                    <span class="dropdown-item text-muted">Loading notifications...</span>
                </div>
            </li>
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
        <!-- Welcome card with improved typography and spacing -->
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $client_name)[0]); ?>!</h2>
            <p>Track and manage your project proposals and active projects from your dashboard.</p>
            <div class="last-updated" id="lastUpdated">
                <i class="fas fa-sync-alt"></i>
                <span>Last updated: Just now</span>
            </div>
        </div>

        <!-- Dashboard overview with 4 key metrics - improved sizing and spacing -->
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

        <!-- Primary action button with improved sizing and accessibility -->
        <div class="actions-bar">
            <a href="client_submit_proposal.php" class="btn btn-primary btn-lg" aria-label="Submit a new project proposal">
                <i class="fas fa-plus" aria-hidden="true"></i> Submit New Proposal
            </a>
        </div>

        <!-- Proposals section with improved card layout and spacing -->
        <section class="proposals-section" aria-labelledby="proposals-heading">
            <div class="section-header">
                <h2 id="proposals-heading" class="section-title">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <span>Your Project Proposals</span>
                    <span class="section-count" aria-label="Number of proposals"><?php echo count($proposals); ?></span>
                </h2>
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
                            <a href="client_proposal_details.php?proposal_id=<?php echo $proposal['id']; ?>" class="proposal-card" style="text-decoration: none; color: inherit;">
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
                                <?php if (($proposal['status'] ?? 'pending') === 'pending' && $proposal['budget_id'] > 0 && $proposal['admin_evaluation'] > 0): ?>
                                    <button class="budget-review-btn" onclick="showBudgetReview(<?php echo $proposal['budget_id']; ?>, event)">
                                        <i class="fas fa-file-invoice-dollar"></i> Review Budget
                                    </button>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Projects section with improved card layout and better visual hierarchy -->
        <section class="projects-section" aria-labelledby="projects-heading">
            <div class="section-header">
                <h2 id="projects-heading" class="section-title">
                    <i class="fas fa-project-diagram" aria-hidden="true"></i>
                    <span>Your Active Projects</span>
                    <span class="section-count" aria-label="Number of projects"><?php echo count($projects); ?></span>
                </h2>
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
                                $progress = $project['completion_percentage'] ?? 0;
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

    <!-- Budget review modal with improved styling -->
    <div class="modal fade" id="budgetReviewModal" tabindex="-1" style="z-index: 9999;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Budget Review</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="budgetBreakdown"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="handleBudgetDecision('reject')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button type="button" class="btn btn-success" onclick="handleBudgetDecision('accept')">
                        <i class="fas fa-check"></i> Accept
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report generation modal with improved layout -->
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentBudgetId = null;
        let currentProjectId = null; // Added to track current project for report generation

        function showBudgetReview(budgetId, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            if (!budgetId) {
                alert('Invalid budget ID');
                return;
            }

            currentBudgetId = budgetId;
            
            // Show loading state
            document.getElementById('budgetBreakdown').innerHTML = '<div class="text-center"><div class="spinner-border"></div><p>Loading budget details...</p></div>';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('budgetReviewModal'));
            modal.show();
            
            // Fetch budget details
            fetch(`fetch_budget_details.php?id=${budgetId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Unknown error occurred');
                    }
                    updateBudgetModal(data);
                })
                .catch(error => {
                    console.error("Error loading budget details:", error);
                    document.getElementById('budgetBreakdown').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Error loading budget details: ${error.message}
                        </div>
                    `;
                });
        }

        function updateBudgetModal(data) {
            const differenceClass = data.difference > 0 ? 'text-danger' : 'text-success';
            const differenceLabel = data.difference > 0 ? 'Higher than proposed' : 'Lower than proposed';
            
            document.getElementById('budgetBreakdown').innerHTML = `
                <div class="budget-breakdown">
                    <div class="budget-comparison">
                        <div class="budget-item">
                            <label>Your Proposed Budget:</label>
                            <div class="budget-amount">₱${numberFormat(data.proposed_budget)}</div>
                        </div>
                        <div class="budget-item">
                            <label>Admin Evaluation:</label>
                            <div class="budget-amount highlight">₱${numberFormat(data.admin_evaluation)}</div>
                        </div>
                        <div class="budget-item ${differenceClass}">
                            <label>Difference:</label>
                            <div class="budget-amount">₱${numberFormat(Math.abs(data.difference))}</div>
                            <small>${differenceLabel}</small>
                        </div>
                    </div>
                    ${data.admin_comment ? `
                        <div class="alert alert-info mt-4">
                            <strong><i class="fas fa-comment"></i> Admin Comment:</strong><br>
                            <p class="mt-2">${escapeHtml(data.admin_comment)}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        function handleBudgetDecision(decision) {
            if (!currentBudgetId) {
                alert('Error: Budget ID not found');
                return;
            }

            const confirmMessage = decision === 'accept' 
                ? 'Are you sure you want to accept this budget?' 
                : 'Are you sure you want to reject this budget? This action cannot be undone.';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Show loading state
            const modal = bootstrap.Modal.getInstance(document.getElementById('budgetReviewModal'));
            const footer = document.querySelector('#budgetReviewModal .modal-footer');
            const originalFooter = footer.innerHTML;
            footer.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm"></div><span class="ms-2">Processing...</span></div>';

            fetch('handle_budget_decision.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `budget_id=${currentBudgetId}&decision=${decision}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    document.getElementById('budgetBreakdown').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>${data.message}</strong>
                            <p class="mt-2">The proposal has been ${decision === 'accept' ? 'approved' : 'rejected'}.</p>
                        </div>
                    `;
                    
                    // Close modal after 2 seconds and reload
                    setTimeout(() => {
                        modal.hide();
                        location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error("Error processing decision:", error);
                footer.innerHTML = originalFooter;
                document.getElementById('budgetBreakdown').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Error processing your decision: ${error.message}
                    </div>
                `;
            });
        }

        function generateReport(event, projectId) {
            event.stopPropagation();
            currentProjectId = projectId;
            document.getElementById('reportModal').style.display = 'block';
            document.getElementById('reportProgress').style.display = 'none';
            document.getElementById('reportResult').style.display = 'none';
            document.getElementById('reportResult').innerHTML = '';
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
            resultDiv.innerHTML = '';
            
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

        // Security function - escape HTML in comments
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Format numbers as Philippine Peso
        function numberFormat(number) {
            return new Intl.NumberFormat('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        }
    </script>
</body>
</html>
