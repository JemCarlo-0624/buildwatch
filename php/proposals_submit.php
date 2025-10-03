<?php
require_once("../config/db.php");

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['client_name']);
    $email = trim($_POST['client_email']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $budget = trim($_POST['budget'] ?? '');
    $timeline = trim($_POST['timeline'] ?? '');

    if ($name && $email && $title && $description) {
        $stmt = $pdo->prepare("
            INSERT INTO project_proposals (client_name, client_email, title, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $title, $description]);
        $success = true;
    } else {
        $error = "⚠️ Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project Proposal - Build Watch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Added modern styling matching dashboard design */
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

        .success-message { background:#d4edda; color:#155724; padding:15px; border-radius:6px; 
                          margin-bottom:20px; border:1px solid #c3e6cb; display:flex; align-items:center; gap:10px; }
        .success-message i { font-size:20px; }
        .error-message { background:#fee; color:#c33; padding:12px; border-radius:6px; margin-bottom:20px; }

        .proposal-form { display:grid; gap:20px; }
        .form-group { display:flex; flex-direction:column; gap:8px; }
        .form-group label { font-weight:600; font-size:14px; }
        .form-group label .required { color:#d42f13; }
        .form-group input, .form-group select, .form-group textarea {
            padding:12px; border:1px solid #ddd; border-radius:6px; font-size:14px;
            transition:0.3s;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            border-color:var(--primary); outline:none; 
        }
        .form-group textarea { resize:vertical; min-height:120px; }
        .form-group small { color:var(--gray); font-size:12px; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

        .btn { padding:12px 20px; border-radius:6px; border:none; cursor:pointer;
               font-weight:600; transition:0.3s; text-decoration:none; display:inline-block; 
               text-align:center; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:#084980; }
        .btn-secondary { background:var(--gray); color:white; }
        .btn-secondary:hover { background:#7f8c8d; }
        .btn-success { background:#28a745; color:white; }
        .btn-success:hover { background:#218838; }

        .form-actions { display:flex; gap:15px; justify-content:flex-start; }

        .info-box {
            background:#e7f3ff;
            border-left:4px solid var(--primary);
            padding:15px;
            border-radius:6px;
            margin-bottom:25px;
        }
        .info-box h3 { font-size:16px; margin-bottom:8px; color:var(--primary); }
        .info-box ul { margin-left:20px; color:#555; }
        .info-box ul li { margin-bottom:5px; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .main-content { margin:20px; padding:20px; }
            .header { padding:15px 20px; flex-direction:column; gap:15px; }
            .header nav { display:flex; gap:15px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-hard-hat"></i> Build Watch</h1>
        <nav>
            <a href="frontpage.php"><i class="fas fa-home"></i> Home</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        </nav>
    </div>

    <div class="main-content">
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Proposal Submitted Successfully!</strong><br>
                    Thank you for your submission. We will review your proposal and get back to you within 2-3 business days.
                </div>
            </div>
            <div class="form-actions">
                <a href="proposals_submit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Submit Another Proposal
                </a>
                <a href="frontpage.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        <?php else: ?>
            <h2>Submit a Project Proposal</h2>
            <p>Have a construction project in mind? Fill out the form below and our team will review your proposal.</p>

            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> What to Include</h3>
                <ul>
                    <li>Clear project title and detailed description</li>
                    <li>Your contact information for follow-up</li>
                    <li>Estimated budget and timeline (if known)</li>
                    <li>Any specific requirements or constraints</li>
                </ul>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="proposal-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Your Name <span class="required">*</span></label>
                        <input type="text" id="client_name" name="client_name" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="client_email">Email Address <span class="required">*</span></label>
                        <input type="email" id="client_email" name="client_email" placeholder="your.email@example.com" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="(123) 456-7890">
                        <small>Optional - for faster communication</small>
                    </div>
                    <div class="form-group">
                        <label for="company">Company Name</label>
                        <input type="text" id="company" name="company" placeholder="Your company name">
                        <small>Optional - if applicable</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Project Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Brief title for your project" required>
                </div>

                <div class="form-group">
                    <label for="description">Project Description <span class="required">*</span></label>
                    <textarea id="description" name="description" placeholder="Provide a detailed description of your project, including scope, objectives, and any specific requirements..." required></textarea>
                    <small>Be as detailed as possible to help us understand your needs</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="budget">Estimated Budget ($)</label>
                        <input type="number" id="budget" name="budget" placeholder="Enter estimated budget" min="0" step="0.01">
                        <small>Optional - helps us provide accurate proposals</small>
                    </div>
                    <div class="form-group">
                        <label for="timeline">Expected Timeline</label>
                        <input type="text" id="timeline" name="timeline" placeholder="e.g., 3-6 months">
                        <small>Optional - when do you need this completed?</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Proposal
                    </button>
                    <a href="frontpage.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
