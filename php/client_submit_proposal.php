<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['client_id'])) {
    header('Location: client_login.php');
    exit;
}

$client_id = $_SESSION['client_id'];
$client_name = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if ($title && $description) {
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            if ($end <= $start) {
                $error = "End date must be after start date.";
            }
        }
        
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO project_proposals (client_id, title, description, start_date, end_date, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $client_id, 
                    $title, 
                    $description,
                    $start_date ?: null,
                    $end_date ?: null
                ]);
                $success = true;
            } catch (PDOException $e) {
                error_log("Proposal submission error: " . $e->getMessage());
                $error = "An error occurred while submitting your proposal. Please try again.";
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit New Proposal - BuildWatch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { 
            background: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        .client-header {
            background: linear-gradient(135deg, #0a4275 0%, #084980 100%);
            color: white;
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(10, 66, 117, 0.25);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .brand-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand-logo {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .brand-logo i {
            font-size: 28px;
            color: #0a4275;
        }
        
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .brand-name {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            line-height: 1;
        }
        
        .brand-tagline {
            font-size: 12px;
            opacity: 0.85;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .header-right { 
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .back-btn:hover { 
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .main-content {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 30px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border-left: 4px solid #0a4275;
        }

        .page-header h1 {
            font-size: 28px;
            color: #0a4275;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: start;
            gap: 15px;
            animation: slideDown 0.3s ease;
        }

        .success-message i {
            font-size: 24px;
            margin-top: 2px;
        }

        .success-message-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
        }

        .success-message-content p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .error-message i {
            font-size: 20px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-box {
            background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
            border-left: 4px solid #0a4275;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #0a4275;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
            color: #555;
        }

        .info-box ul li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .proposal-form {
            display: grid;
            gap: 25px;
        }

        .form-section {
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section-title i {
            color: #0a4275;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label .required {
            color: #d42f13;
            font-size: 16px;
        }

        .form-group label .label-icon {
            color: #0a4275;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #0a4275;
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 66, 117, 0.1);
        }

        .form-group input:disabled {
            background: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 140px;
            line-height: 1.6;
        }

        .form-group small {
            color: #666;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group small i {
            font-size: 12px;
            color: #0a4275;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #999;
            margin-top: -5px;
        }

        .form-group input[type="date"] {
            cursor: pointer;
        }

        .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
        }

        .form-group input[type="date"]::-webkit-calendar-picker-indicator:hover {
            background: rgba(10, 66, 117, 0.1);
        }

        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0a4275 0%, #084980 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(10, 66, 117, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(10, 66, 117, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        @media (max-width: 768px) {
            .client-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .brand-section {
                width: 100%;
                justify-content: center;
            }

            .header-right {
                width: 100%;
                justify-content: center;
            }

            .main-content {
                padding: 0 15px;
                margin: 20px auto;
            }

            .form-container {
                padding: 25px 20px;
            }

            .form-row {
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
<body>

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
            <a href="client_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="main-content">

        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> Submit New Proposal</h1>
            <p>Have a new construction project in mind? Fill out the form below to submit your proposal. Our team will review it and get back to you within 2-3 business days.</p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>

                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <div class="success-message-content">
                        <h3>Proposal Submitted Successfully!</h3>
                        <p>Thank you for your submission. We will review your proposal and get back to you within 2-3 business days.</p>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="client_submit_proposal.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Submit Another Proposal
                    </a>
                    <a href="client_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <h3><i class="fas fa-lightbulb"></i> Tips for a Great Proposal</h3>
                    <ul>
                        <li>Provide a clear and descriptive project title</li>
                        <li>Include detailed project description with scope and objectives</li>
                        <li>Specify your project timeline dates</li>
                        <li>Mention any specific materials, techniques, or standards required</li>
                    </ul>
                </div>

                <form method="POST" class="proposal-form" id="proposalForm">

                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-user"></i> Your Information
                        </h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="client_name">
                                    <i class="fas fa-user label-icon"></i> Full Name
                                </label>
                                <input type="text" id="client_name" value="<?php echo htmlspecialchars($client['name'] ?? $client_name); ?>" disabled>
                                <small><i class="fas fa-info-circle"></i> Pre-filled from your account</small>
                            </div>
                            <div class="form-group">
                                <label for="client_email">
                                    <i class="fas fa-envelope label-icon"></i> Email Address
                                </label>
                                <input type="email" id="client_email" value="<?php echo htmlspecialchars($client['email'] ?? $client_email); ?>" disabled>
                                <small><i class="fas fa-info-circle"></i> Pre-filled from your account</small>
                            </div>
                        </div>
                        <?php if (!empty($client['phone']) || !empty($client['company'])): ?>
                            <div class="form-row">
                                <?php if (!empty($client['phone'])): ?>
                                    <div class="form-group">
                                        <label for="client_phone">
                                            <i class="fas fa-phone label-icon"></i> Phone Number
                                        </label>
                                        <input type="tel" id="client_phone" value="<?php echo htmlspecialchars($client['phone']); ?>" disabled>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($client['company'])): ?>
                                    <div class="form-group">
                                        <label for="client_company">
                                            <i class="fas fa-building label-icon"></i> Company
                                        </label>
                                        <input type="text" id="client_company" value="<?php echo htmlspecialchars($client['company']); ?>" disabled>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-project-diagram"></i> Project Details
                        </h3>
                        <div class="form-group">
                            <label for="title">
                                <i class="fas fa-heading label-icon"></i> Project Title <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                placeholder="e.g., Commercial Building Construction, Residential Renovation" 
                                required
                                maxlength="200"
                            >
                            <small><i class="fas fa-info-circle"></i> A brief, descriptive title for your project</small>
                        </div>

                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left label-icon"></i> Project Description <span class="required">*</span>
                            </label>
                            <textarea 
                                id="description" 
                                name="description" 
                                placeholder="Provide a detailed description of your project including scope, objectives, specific requirements, materials, location, and any other relevant information..."
                                required
                                maxlength="2000"
                            ></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span> / 2000 characters
                            </div>
                            <small><i class="fas fa-info-circle"></i> Be as detailed as possible to help us understand your needs</small>
                        </div>
                    </div>

                    <!-- removed budget field section -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-calendar-alt"></i> Timeline
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">
                                    <i class="fas fa-calendar-day label-icon"></i> Expected Start Date
                                </label>
                                <input 
                                    type="date" 
                                    id="start_date" 
                                    name="start_date"
                                    min="<?php echo date('Y-m-d'); ?>"
                                >
                                <small><i class="fas fa-info-circle"></i> When do you want the project to begin?</small>
                            </div>
                            <div class="form-group">
                                <label for="end_date">
                                    <i class="fas fa-calendar-check label-icon"></i> Expected Completion Date
                                </label>
                                <input 
                                    type="date" 
                                    id="end_date" 
                                    name="end_date"
                                    min="<?php echo date('Y-m-d'); ?>"
                                >
                                <small><i class="fas fa-info-circle"></i> When do you need the project completed?</small>
                            </div>
                        </div>
                    </div>

 
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Proposal
                        </button>
                        <a href="client_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const descriptionField = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        
        if (descriptionField && charCount) {
            descriptionField.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }

        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    endDateInput.min = this.value;
                    if (endDateInput.value && endDateInput.value < this.value) {
                        endDateInput.value = '';
                    }
                }
            });

            endDateInput.addEventListener('change', function() {
                if (startDateInput.value && this.value) {
                    if (this.value <= startDateInput.value) {
                        alert('End date must be after start date.');
                        this.value = '';
                    }
                }
            });
        }

        const form = document.getElementById('proposalForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title').value.trim();
                const description = document.getElementById('description').value.trim();
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                if (!title || !description) {
                    e.preventDefault();
                    alert('Please fill in all required fields (Project Title and Description).');
                    return false;
                }
                
                if (description.length < 50) {
                    e.preventDefault();
                    alert('Please provide a more detailed project description (at least 50 characters).');
                    return false;
                }

                if (startDate && endDate) {
                    const start = new Date(startDate);
                    const end = new Date(endDate);
                    
                    if (end <= start) {
                        e.preventDefault();
                        alert('End date must be after start date.');
                        return false;
                    }
                }
            });
        }
    </script>
</body>
</html>
