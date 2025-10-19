<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $status = $_POST['status'] ?? 'planning';
    $priority = $_POST['priority'] ?? 'medium';
    $client_id = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
    $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : null;
    $timeline = trim($_POST['timeline']);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $category = $_POST['category'] ?? '';
    $estimated_hours = !empty($_POST['estimated_hours']) ? intval($_POST['estimated_hours']) : null;

    if ($name) {
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                name, description, status, priority, created_by, client_id, 
                budget, timeline, start_date, end_date, category, estimated_hours,
                completion_percentage, total_hours_spent, created_at, last_activity_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW(), NOW())
        ");
        $stmt->execute([
            $name, $desc, $status, $priority, $_SESSION['user_id'], $client_id,
            $budget, $timeline, $start_date, $end_date, $category, $estimated_hours
        ]);

        header("Location: projects_list.php");
        exit;
    } else {
        $error = "⚠️ Please enter a project name.";
    }
}

$clients = [];
try {
    $clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    // Clients table doesn't exist, skip
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Project - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Reset & Theme */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, sans-serif; }
        :root {
            --primary:#0a63a5; --accent:#d42f13; --secondary:#cb9501;
            --gray:#95a5a6; --light:#f9f9f9; --dark:#2c3e50;
        }
        body { background:#f5f7fa; color:#333; }

        .header {
            background:var(--primary);
            color:white;
            padding:20px 40px;
            display:flex;
            align-items:center;
            justify-content:space-between;
        }
        .header h1 { font-size:22px; display:flex; align-items:center; gap:10px; }
        .header nav a { color:white; margin-left:20px; text-decoration:none; }
        .header nav a:hover { text-decoration:underline; }

        .main-content { max-width:800px; margin:40px auto; padding:30px; background:white;
                       border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.08); }
        .main-content h2 { font-size:26px; margin-bottom:10px; color:var(--primary); }
        .main-content p { color:var(--gray); margin-bottom:25px; }

        .error-message { background:#fee; color:#c33; padding:12px; border-radius:6px; margin-bottom:20px; }

        .project-form { display:grid; gap:20px; }
        .form-group { display:flex; flex-direction:column; gap:8px; }
        .form-group label { font-weight:600; font-size:14px; }
        .form-group input, .form-group select, .form-group textarea {
            padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px;
            transition:0.3s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            border-color:var(--primary); outline:none; 
        }
        .form-group textarea { resize:vertical; min-height:120px; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

        .btn { padding:12px 20px; border-radius:6px; border:none; cursor:pointer;
               font-weight:600; transition:0.3s; text-decoration:none; display:inline-block; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:#084980; }
        .btn-secondary { background:var(--gray); color:white; }
        .btn-secondary:hover { background:#7f8c8d; }

        .form-actions { display:flex; gap:15px; justify-content:flex-start; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .main-content { margin:20px; padding:20px; }
            .header { padding:15px 20px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-project-diagram"></i>Build Watch</h1>
        <nav>
            <a href="../<?php echo $_SESSION['role']; ?>/dashboard_<?php echo $_SESSION['role']; ?>.php">Dashboard</a>
            <a href="projects_list.php">Projects</a>
        </nav>
    </div>

    <div class="main-content">
        <h2>Create New Project</h2>
        <p>Fill in the form below to create a new project. All required fields must be completed.</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="project-form">
            <div class="form-group">
                <label for="name">Project Name *</label>
                <input type="text" id="name" name="name" placeholder="Enter project name" required>
            </div>

            <?php if (!empty($clients)): ?>
            <div class="form-group">
                <label for="client_id">Client</label>
                <select id="client_id" name="client_id">
                    <option value="">Select client (optional)</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="budget">Budget ($)</label>
                    <input type="number" id="budget" name="budget" placeholder="Enter budget" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="estimated_hours">Estimated Hours</label>
                    <input type="number" id="estimated_hours" name="estimated_hours" placeholder="e.g. 160" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="timeline">Timeline Description</label>
                    <input type="text" id="timeline" name="timeline" placeholder="e.g. 6 months, Q1 2024">
                </div>
                <div class="form-group">
                    <label for="category">Project Category</label>
                    <select id="category" name="category">
                        <option value="">Select category</option>
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="infrastructure">Infrastructure</option>
                        <option value="industrial">Industrial</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Project Status</label>
                    <select id="status" name="status">
                        <option value="planning">Planning</option>
                        <option value="ongoing" selected>Ongoing</option>
                        <option value="on-hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Project Description</label>
                <textarea id="description" name="description" placeholder="Provide detailed project description"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Project
                </button>
                <a href="projects_list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </div>
        </form>
    </div>
</body>
</html>
