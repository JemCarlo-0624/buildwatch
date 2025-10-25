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
$sql = "SELECT p.id, p.title, p.description, p.client_id, c.name as client_name, c.email as client_email,
               b.id AS budget_id, b.proposed_amount, b.evaluated_amount, b.status, b.remarks
        FROM project_proposals p
        LEFT JOIN project_budgets b ON p.id = b.proposal_id
        LEFT JOIN clients c ON p.client_id = c.id
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$proposal_id]);
$budget = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$budget) {
    $_SESSION['error_message'] = "Proposal not found.";
    header("Location: proposals_review.php");
    exit;
}

// Fetch existing budget breakdowns
$breakdownStmt = $pdo->prepare("
    SELECT * FROM budget_breakdowns 
    WHERE budget_id = ? 
    ORDER BY created_at DESC
");
$breakdownStmt->execute([$budget['budget_id']]);
$breakdowns = $breakdownStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Budget - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .budget-header {
            background: linear-gradient(135deg, #0a4275 0%, #084980 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .budget-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .budget-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid white;
        }

        .info-label {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            font-weight: 600;
        }

        .budget-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #0a4275;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
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

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .breakdown-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
        }

        .breakdown-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .breakdown-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .breakdown-table tbody tr:hover {
            background: #f8f9fa;
        }

        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .category-materials { background: #e3f2fd; color: #1976d2; }
        .category-labor { background: #f3e5f5; color: #7b1fa2; }
        .category-equipment { background: #fff3e0; color: #e65100; }
        .category-misc { background: #f5f5f5; color: #424242; }

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

        .summary-box {
            background: linear-gradient(135deg, #f0f8ff 0%, #e7f3ff 100%);
            border-left: 4px solid #0a4275;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(10, 66, 117, 0.1);
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 700;
            color: #0a4275;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
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

        @media (max-width: 768px) {
            .breakdown-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
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
            <div class="nav-section-title">Admin Panel</div>
            <a href="dashboard_admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="projects_list.php" class="nav-item"><i class="fas fa-project-diagram"></i> Projects</a>
            <a href="tasks_list.php" class="nav-item"><i class="fas fa-tasks"></i> Tasks</a>
            <a href="proposals_review.php" class="nav-item active"><i class="fas fa-lightbulb"></i> Proposals</a>
            <a href="users_list.php" class="nav-item"><i class="fas fa-users"></i> Users</a>
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
                <h1 class="page-title">Budget Review</h1>
                <p class="page-description">Review and evaluate project budget proposal</p>
            </div>
            <a href="proposals_review.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Proposals
            </a>
        </div>

        <div class="budget-header">
            <div class="budget-title">
                <i class="fas fa-money-bill-wave"></i>
                <?php echo htmlspecialchars($budget['title']); ?>
            </div>
            <div class="budget-info">
                <div class="info-item">
                    <div class="info-label">Client</div>
                    <div class="info-value"><?php echo htmlspecialchars($budget['client_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($budget['client_email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Proposed Budget</div>
                    <div class="info-value">₱<?php echo number_format($budget['proposed_amount'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Current Status</div>
                    <div class="info-value"><?php echo ucfirst(htmlspecialchars($budget['status'])); ?></div>
                </div>
            </div>
        </div>

        <form method="POST" action="admin_approve_budget.php" id="budgetForm">
            <input type="hidden" name="budget_id" value="<?php echo $budget['budget_id']; ?>">
            <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">

            <div class="budget-card">
                <div class="card-title">
                    <i class="fas fa-file-alt"></i> Project Description
                </div>
                <p><?php echo nl2br(htmlspecialchars($budget['description'])); ?></p>
            </div>

            <div class="budget-card">
                <div class="card-title">
                    <i class="fas fa-calculator"></i> Budget Evaluation
                </div>

                <div class="form-group">
                    <label for="evaluated_amount">
                        <i class="fas fa-peso-sign"></i> Evaluated Total Budget (₱) <span style="color: #d42f13;">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="evaluated_amount" 
                        name="evaluated_amount" 
                        placeholder="Enter the evaluated total budget"
                        value="<?php echo $budget['evaluated_amount'] ?? ''; ?>"
                        required
                        min="0"
                        step="0.01"
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
                    ><?php echo htmlspecialchars($budget['remarks'] ?? ''); ?></textarea>
                    <small>Optional: Include any adjustments, concerns, or recommendations</small>
                </div>
            </div>

            <div class="budget-card">
                <div class="card-title">
                    <i class="fas fa-list"></i> Budget Breakdown
                </div>

                <p style="color: #666; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> Add itemized breakdown of costs by category
                </p>

                <div id="breakdown">
                    <?php if (!empty($breakdowns)): ?>
                        <?php foreach ($breakdowns as $breakdown): ?>
                        <div class="breakdown-row">
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="text" name="item_name[]" placeholder="Item Name" 
                                       value="<?php echo htmlspecialchars($breakdown['item_name']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="number" name="cost[]" placeholder="Estimated Cost" 
                                       value="<?php echo $breakdown['estimated_cost']; ?>" required min="0" step="0.01">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <select name="category[]" required>
                                    <option value="materials" <?php echo $breakdown['category'] === 'materials' ? 'selected' : ''; ?>>Materials</option>
                                    <option value="labor" <?php echo $breakdown['category'] === 'labor' ? 'selected' : ''; ?>>Labor</option>
                                    <option value="equipment" <?php echo $breakdown['category'] === 'equipment' ? 'selected' : ''; ?>>Equipment</option>
                                    <option value="misc" <?php echo $breakdown['category'] === 'misc' ? 'selected' : ''; ?>>Misc</option>
                                </select>
                            </div>
                            <button type="button" class="btn-remove-row" onclick="removeRow(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="breakdown-row">
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="text" name="item_name[]" placeholder="Item Name">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="number" name="cost[]" placeholder="Estimated Cost" min="0" step="0.01">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <select name="category[]">
                                    <option value="materials">Materials</option>
                                    <option value="labor">Labor</option>
                                    <option value="equipment">Equipment</option>
                                    <option value="misc">Misc</option>
                                </select>
                            </div>
                            <button type="button" class="btn-remove-row" onclick="removeRow(this)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn-add-row" onclick="addRow()">
                    <i class="fas fa-plus"></i> Add Row
                </button>

                <div class="summary-box">
                    <div class="summary-row">
                        <span class="summary-label">Client Proposed:</span>
                        <span class="summary-value">₱<?php echo number_format($budget['proposed_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Your Evaluation:</span>
                        <span class="summary-value" id="evaluatedDisplay">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Difference:</span>
                        <span class="summary-value" id="differenceDisplay">₱0.00</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Submit Review
                </button>
                <a href="proposals_review.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
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

        const proposed = <?php echo $budget['proposed_amount']; ?>;
        const difference = total - proposed;

        // Update evaluated input field
        const evaluatedInput = document.getElementById('evaluated_amount');
        evaluatedInput.value = total.toFixed(2);

        // Update display boxes
        document.getElementById('evaluatedDisplay').textContent = 
            '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        document.getElementById('differenceDisplay').textContent = 
            '₱' + difference.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Add red color if over budget, green if under
        const diffDisplay = document.getElementById('differenceDisplay');
        diffDisplay.style.color = difference > 0 ? '#d42f13' : '#0a8f2d';
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', () => {
        const costInputs = document.querySelectorAll('input[name="cost[]"]');
        costInputs.forEach(input => input.addEventListener('input', updateSummary));
        updateSummary();
    });

    document.getElementById('budgetForm').addEventListener('submit', function(e) {
        const evaluatedAmount = document.getElementById('evaluated_amount').value;

        if (!evaluatedAmount) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });
</script>

</body>
</html>
