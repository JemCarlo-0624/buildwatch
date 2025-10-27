<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    header("Location: client_login.php");
    exit;
}

// Fetch budget proposals for this client
$stmt = $pdo->prepare("
    SELECT 
        pb.*,
        pp.title AS project_name,
        pp.budget AS proposed_budget,
        u.name AS admin_name
    FROM project_budgets pb
    JOIN project_proposals pp ON pb.proposal_id = pp.id
    LEFT JOIN users u ON pp.admin_id = u.id
    WHERE pp.client_id = ?
    ORDER BY pb.created_at DESC
");

$stmt->execute([$_SESSION['client_id']]);
$budgets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Reviews - BuildWatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'layout.php'; ?>

    <div class="container mt-4">
        <h2>Budget Reviews</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Your Budget</th>
                        <th>Admin Evaluation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $budget): ?>
                        <tr>
                            <td><?= htmlspecialchars($budget['project_name']) ?></td>
                            <td>₱<?= number_format($budget['proposed_budget'], 2) ?></td>
                            <td>₱<?= number_format($budget['admin_evaluation'], 2) ?></td>
                            <td>
                                <?php
                                $statusClass = match($budget['status']) {
                                    'approved' => 'success',
                                    'pending_client_decision' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>">
                                    <?= ucwords(str_replace('_', ' ', $budget['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($budget['status'] === 'pending_client_decision'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-primary"
                                            onclick="window.location.href='client_review_budget.php?budget_id=<?= $budget['id'] ?>'">
                                        Review
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($budgets)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No budget reviews found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
