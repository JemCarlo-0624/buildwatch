<?php
/**
 * Comprehensive Report Generator for BuildWatch
 * Generates detailed project reports with metrics, insights, and analytics
 * PHP version of the Java ReportGenerator
 */

class ReportGenerator {
    private $pdo;
    private $reportsDir;
    private $dateFormatter = 'Y-m-d H:i:s';
    private $fileDateFormatter = 'Ymd_His';
    
    public function __construct($pdo, $reportsDir = '../reports/') {
        $this->pdo = $pdo;
        $this->reportsDir = $reportsDir;
        
        // Create reports directory if it doesn't exist
        if (!file_exists($this->reportsDir)) {
            mkdir($this->reportsDir, 0755, true);
        }
    }
    
    /**
     * Generate a comprehensive report for the specified project
     */
    public function generateReport($projectId, $format = 'html', $outputPath = null) {
        $format = strtolower($format);
        
        // Validate format
        $validFormats = ['html', 'json', 'txt', 'pdf'];
        if (!in_array($format, $validFormats)) {
            throw new Exception("Unsupported format: $format. Supported formats: html, json, txt");
        }
        
        // Fetch project data
        $projectData = $this->fetchProjectData($projectId);
        
        if (!$projectData) {
            throw new Exception("Project not found with ID: $projectId");
        }
        
        // Generate report based on format
        switch ($format) {
            case 'html':
                return $this->generateHTMLReport($projectData, $outputPath);
            case 'json':
                return $this->generateJSONReport($projectData, $outputPath);
            case 'txt':
                return $this->generateTextReport($projectData, $outputPath);
            case 'pdf':
                // PDF generation requires external library - fallback to HTML
                error_log("WARNING: PDF generation requires external library. Generating HTML instead.");
                return $this->generateHTMLReport($projectData, $outputPath);
            default:
                throw new Exception("Unsupported format: $format");
        }
    }
    
    /**
     * Fetch comprehensive project data from database
     */
    private function fetchProjectData($projectId) {
        try {
            // Determine which columns exist for projects table
            $cols = $this->getTableColumns('projects');
            
            // Base required columns
            $selectCols = [
                'p.id', 'p.name', 'p.description', 'p.status',
                'p.completion_percentage', 'p.priority',
                'p.start_date', 'p.end_date',
                'p.created_at', 'p.last_activity_at', 'p.created_by', 'p.client_id'
            ];
            
            $sql = "SELECT " . implode(", ", $selectCols) . ", u.name AS created_by_name, " .
                   "c.name AS client_name, c.email AS client_email, c.company AS client_company " .
                   "FROM projects p LEFT JOIN users u ON p.created_by = u.id " .
                   "LEFT JOIN clients c ON p.client_id = c.id WHERE p.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
$stmt->execute([$projectId]);
$row = $stmt->fetch();

if (!$row) {
    return null;
}

// ✅ Create ProjectData object
$data = new ProjectData();
$data->id = (int)$row['id'];
$data->name = $row['name'];
$data->description = $row['description'];
$data->status = $row['status'];
$data->priority = $row['priority'];
$data->startDate = $row['start_date'];
$data->endDate = $row['end_date'];
$data->category = $row['category'] ?? 'N/A';
$data->createdAt = $row['created_at'];
$data->lastActivityAt = $row['last_activity_at'];
$data->createdByName = $row['created_by_name'] ?? 'N/A';
$data->clientId = (int)$row['client_id'];

$data->clientName = $row['client_name'] ?? 'N/A';
$data->clientEmail = $row['client_email'] ?? 'N/A';
$data->clientCompany = $row['client_company'] ?? 'N/A';

// ✅ Calculate completionPercentage dynamically from tasks
try {
    $taskQuery = "
        SELECT 
            COUNT(*) AS total_tasks,
            SUM(CASE WHEN progress >= 100 THEN 1 ELSE 0 END) AS completed_tasks,
            ROUND(
                CASE WHEN COUNT(*) = 0 THEN 0
                     ELSE (SUM(CASE WHEN progress >= 100 THEN 1 ELSE 0 END) / COUNT(*)) * 100
                END
            , 0) AS completion_percentage
        FROM tasks
        WHERE project_id = ?
    ";
    $taskStmt = $this->pdo->prepare($taskQuery);
    $taskStmt->execute([$projectId]);
    $taskStats = $taskStmt->fetch();

    $data->totalTasks = (int)$taskStats['total_tasks'];
    $data->completedTasks = (int)$taskStats['completed_tasks'];
    $data->completionPercentage = (int)$taskStats['completion_percentage'];
} catch (Exception $e) {
    $data->completionPercentage = 0; // fallback if query fails
    error_log("Error calculating completion: " . $e->getMessage());
}

// ✅ Fetch related project data
$this->fetchTasks($data);
$this->fetchTeamMembers($data);
$this->fetchProposalData($data);
$this->fetchBudgetData($data);
$this->fetchBudgetBreakdown($data);

// ✅ Calculate other metrics
$data->calculateMetrics();

return $data;

            
        } catch (Exception $e) {
            error_log("Error fetching project data: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fetch proposal information for the project
     */
    private function fetchProposalData(&$data) {
        try {
            // Get the most recent proposal for this client
            $sql = "SELECT id, title, description, status, budget, start_date, end_date, client_decision " .
                   "FROM project_proposals WHERE client_id = ? ORDER BY submitted_at DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$data->clientId]);
            $proposal = $stmt->fetch();
            
            if ($proposal) {
                $data->proposalId = (int)$proposal['id'];
                $data->proposalTitle = $proposal['title'];
                $data->proposalDescription = $proposal['description'];
                $data->proposalStatus = $proposal['status'];
                $data->proposalBudget = (float)($proposal['budget'] ?? 0);
                $data->proposalStartDate = $proposal['start_date'];
                $data->proposalEndDate = $proposal['end_date'];
                $data->proposalClientDecision = $proposal['client_decision'];
            }
        } catch (Exception $e) {
            error_log("Error fetching proposal data: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch budget information for the project
     */
    private function fetchBudgetData(&$data) {
        try {
            // Get budget linked to the proposal we just fetched
            if (!isset($data->proposalId)) {
                return;
            }
            
            $sql = "SELECT pb.id, pb.proposal_id, pb.proposed_amount, pb.evaluated_amount, " .
                   "pb.status, pb.remarks, pb.admin_comment, pb.client_decision " .
                   "FROM project_budgets pb " .
                   "WHERE pb.proposal_id = ? ORDER BY pb.created_at DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$data->proposalId]);
            $budget = $stmt->fetch();
            
            if ($budget) {
                $data->budgetId = (int)$budget['id'];
                $data->proposedAmount = (float)$budget['proposed_amount'];
                $data->evaluatedAmount = (float)$budget['evaluated_amount'];
                $data->budgetStatus = $budget['status'];
                $data->budgetRemarks = $budget['remarks'];
                $data->adminComment = $budget['admin_comment'];
                $data->clientBudgetDecision = $budget['client_decision'];
                
                // Fetch budget reviews for this budget
                $this->fetchBudgetReviews($data, (int)$budget['proposal_id']);
            }
        } catch (Exception $e) {
            error_log("Error fetching budget data: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch budget breakdown items
     */
    private function fetchBudgetBreakdown(&$data) {
        try {
            if (!isset($data->budgetId)) {
                return;
            }
            
            $sql = "SELECT id, item_name, category, estimated_cost, created_at " .
                   "FROM budget_breakdowns WHERE budget_id = ? ORDER BY created_at ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$data->budgetId]);
            
            $data->budgetBreakdowns = [];
            $totalBreakdownCost = 0;
            
            while ($row = $stmt->fetch()) {
                $breakdown = new BudgetBreakdown();
                $breakdown->id = (int)$row['id'];
                $breakdown->itemName = $row['item_name'];
                $breakdown->category = $row['category'];
                $breakdown->estimatedCost = (float)$row['estimated_cost'];
                $breakdown->createdAt = $row['created_at'];
                
                $data->budgetBreakdowns[] = $breakdown;
                $totalBreakdownCost += $breakdown->estimatedCost;
            }
            
            $data->totalBreakdownCost = $totalBreakdownCost;
        } catch (Exception $e) {
            error_log("Error fetching budget breakdown: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch budget review history
     */
    private function fetchBudgetReviews(&$data, $proposalId) {
        try {
            $sql = "SELECT id, admin_id, evaluated_amount, status, remarks, created_at, u.name AS admin_name " .
                   "FROM budget_reviews br " .
                   "LEFT JOIN users u ON br.admin_id = u.id " .
                   "WHERE br.proposal_id = ? ORDER BY br.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$proposalId]);
            
            $data->budgetReviews = [];
            
            while ($row = $stmt->fetch()) {
                $review = new BudgetReview();
                $review->id = (int)$row['id'];
                $review->adminName = $row['admin_name'] ?? 'N/A';
                $review->evaluatedAmount = (float)$row['evaluated_amount'];
                $review->status = $row['status'];
                $review->remarks = $row['remarks'];
                $review->createdAt = $row['created_at'];
                
                $data->budgetReviews[] = $review;
            }
        } catch (Exception $e) {
            error_log("Error fetching budget reviews: " . $e->getMessage());
        }
    }
    
    /**
     * Get all column names for a table
     */
    private function getTableColumns($tableName) {
        $cols = [];
        try {
            $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tableName]);
            
            while ($row = $stmt->fetch()) {
                $cols[] = $row['COLUMN_NAME'];
            }
        } catch (Exception $e) {
            error_log("Error getting table columns: " . $e->getMessage());
        }
        return $cols;
    }
    
    /**
     * Fetch tasks for a project
     */
    private function fetchTasks(&$data) {
        $taskQuery = "SELECT t.id, t.title, t.description, t.progress, t.due_date, t.created_at, " .
                     "u.name as assigned_to_name " .
                     "FROM tasks t " .
                     "LEFT JOIN users u ON t.assigned_to = u.id " .
                     "WHERE t.project_id = ? " .
                     "ORDER BY t.created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($taskQuery);
            $stmt->execute([$data->id]);
            
            while ($row = $stmt->fetch()) {
                $task = new TaskData();
                $task->id = (int)$row['id'];
                $task->title = $row['title'];
                $task->description = $row['description'];
                $task->progress = (int)$row['progress'];
                $task->assignedTo = $row['assigned_to_name'];
                $task->dueDate = $row['due_date'];
                
                // Determine status based on progress
                if ($task->progress == 100) {
                    $task->status = "Completed";
                } elseif ($task->progress > 0) {
                    $task->status = "In Progress";
                } else {
                    $task->status = "Pending";
                }
                
                $task->priority = "Normal";
                
                $data->tasks[] = $task;
            }
        } catch (Exception $e) {
            error_log("Error fetching tasks: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch team members for a project
     */
    private function fetchTeamMembers(&$data) {
        $teamQuery = "SELECT u.id, u.name, u.email, u.role, pa.assigned_at " .
                     "FROM project_assignments pa " .
                     "JOIN users u ON pa.user_id = u.id " .
                     "WHERE pa.project_id = ? " .
                     "ORDER BY pa.assigned_at ASC";
        
        try {
            $stmt = $this->pdo->prepare($teamQuery);
            $stmt->execute([$data->id]);
            
            while ($row = $stmt->fetch()) {
                $member = new TeamMember();
                $member->id = (int)$row['id'];
                $member->name = $row['name'];
                $member->email = $row['email'];
                $member->role = $row['role'];
                $member->assignedAt = $row['assigned_at'];
                
                $data->teamMembers[] = $member;
            }
        } catch (Exception $e) {
            error_log("Error fetching team members: " . $e->getMessage());
        }
    }
    
    /**
     * Get HTML styles for reports
     */
    private function getHTMLStyles() {
        return <<<CSS
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f5f5; color: #333; line-height: 1.6; }
.header { background: linear-gradient(135deg, #0a4275 0%, #084980 100%); color: white; padding: 40px 20px; text-align: center; }
.header h1 { margin: 0; font-size: 32px; }
.subtitle { margin: 10px 0 0 0; opacity: 0.9; font-size: 14px; }
.project-banner { background: #0a4275; color: white; padding: 20px; text-align: center; margin-bottom: 30px; }
.project-banner h2 { margin: 0; font-size: 24px; }
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.section { background: white; padding: 25px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.section h3 { color: #0a4275; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #0a4275; padding-bottom: 10px; }
.overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 20px; }
.metric-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
.metric-card.success { border-left: 4px solid #2ecc71; }
.metric-card.primary { border-left: 4px solid #0a4275; }
.metric-card.warning { border-left: 4px solid #f39c12; }
.metric-card.info { border-left: 4px solid #3498db; }
.metric-card.danger { border-left: 4px solid #e74c3c; }
.metric-label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; }
.metric-value { font-size: 28px; font-weight: bold; color: #333; }
.team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
.team-card { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #0a4275; }
.team-card .name { font-weight: 600; color: #0a4275; margin-bottom: 5px; }
.team-card .role { font-size: 12px; color: #666; text-transform: uppercase; }
.team-card .email { font-size: 13px; color: #888; margin-top: 5px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th { background: #0a4275; color: white; padding: 12px; text-align: left; font-weight: 600; }
td { padding: 10px; border-bottom: 1px solid #ddd; }
tr:hover { background: #f9f9f9; }
.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
.status-completed { background: #d4edda; color: #155724; }
.status-in-progress { background: #fff3cd; color: #856404; }
.status-pending { background: #d1ecf1; color: #0c5460; }
.status-blocked { background: #f8d7da; color: #721c24; }
.status-ongoing { background: #d1ecf1; color: #0c5460; }
.status-on-hold { background: #fff3cd; color: #856404; }
.status-planning { background: #e2e3e5; color: #383d41; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending-client { background: #fff3cd; color: #856404; }
.priority-high { color: #e74c3c; font-weight: 600; }
.priority-medium { color: #f39c12; font-weight: 600; }
.priority-low { color: #95a5a6; font-weight: 600; }
.insights { background: #e8f4f8; border-left: 4px solid #3498db; }
.footer { background: #333; color: white; text-align: center; padding: 20px; margin-top: 40px; font-size: 12px; }
@media print { body { background: white; } .section { box-shadow: none; page-break-inside: avoid; } }
@media (max-width: 768px) { .metrics-grid, .overview-grid, .team-grid { grid-template-columns: 1fr; } .container { padding: 10px; } }
CSS;
    }
    
    /**
     * Create a metric card HTML
     */
    private function createMetricCard($label, $value, $type) {
        return "<div class=\"metric-card $type\">\n" .
               "<div class=\"metric-label\">$label</div>\n" .
               "<div class=\"metric-value\">$value</div>\n" .
               "</div>\n";
    }
    
    /**
     * Create task table HTML
     */
    private function createTaskTable($data) {
        if (empty($data->tasks)) {
            return "<p style=\"color: #888; font-style: italic;\">No tasks have been created for this project yet.</p>";
        }
        
        $table = "<div style=\"overflow-x: auto;\">\n";
        $table .= "<table>\n<thead>\n<tr>\n";
        $table .= "<th>Task</th><th>Assigned To</th><th>Status</th><th>Progress</th><th>Due Date</th>\n";
        $table .= "</tr>\n</thead>\n<tbody>\n";
        
        foreach ($data->tasks as $task) {
            $table .= "<tr>\n";
            $table .= "<td>" . $this->escapeHtml($task->title) . "</td>\n";
            $table .= "<td>" . $this->escapeHtml($task->assignedTo ?? "Unassigned") . "</td>\n";
            $table .= "<td><span class=\"status-badge status-" . str_replace(" ", "-", strtolower($task->status)) . "\">" .
                     $this->escapeHtml($task->status) . "</span></td>\n";
            $table .= "<td>" . $task->progress . "%</td>\n";
            
            if ($task->dueDate) {
                $dueDate = new DateTime($task->dueDate);
                $table .= "<td>" . $dueDate->format('M d, Y') . "</td>\n";
            } else {
                $table .= "<td>N/A</td>\n";
            }
            
            $table .= "</tr>\n";
        }
        
        $table .= "</tbody>\n</table>\n";
        $table .= "</div>\n";
        return $table;
    }
    
    /**
     * Create budget breakdown table HTML
     */
    private function createBudgetBreakdownTable($data) {
        if (empty($data->budgetBreakdowns)) {
            return "<p style=\"color: #888; font-style: italic;\">No budget breakdown items available.</p>";
        }
        
        $table = "<div style=\"overflow-x: auto;\">\n";
        $table .= "<table>\n<thead>\n<tr>\n";
        $table .= "<th>Item Name</th><th>Category</th><th>Estimated Cost</th>\n";
        $table .= "</tr>\n</thead>\n<tbody>\n";
        
        foreach ($data->budgetBreakdowns as $item) {
            $table .= "<tr>\n";
            $table .= "<td>" . $this->escapeHtml($item->itemName) . "</td>\n";
            $table .= "<td>" . $this->escapeHtml(ucfirst($item->category)) . "</td>\n";
            $table .= "<td>₱" . number_format($item->estimatedCost, 2) . "</td>\n";
            $table .= "</tr>\n";
        }
        
        $table .= "<tr style=\"background: #f0f0f0; font-weight: bold;\">\n";
        $table .= "<td colspan=\"2\">Total Breakdown Cost</td>\n";
        $table .= "<td>₱" . number_format($data->totalBreakdownCost, 2) . "</td>\n";
        $table .= "</tr>\n";
        
        $table .= "</tbody>\n</table>\n";
        $table .= "</div>\n";
        return $table;
    }
    
    /**
     * Generate HTML report
     */
    private function generateHTMLReport($data, $outputPath = null) {
        $fileName = $outputPath ?? 
            $this->reportsDir . "project_" . $data->id . "_report_" . 
            date($this->fileDateFormatter) . ".html";
        
        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "<meta charset=\"UTF-8\">\n";
        $html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "<title>Project Report - " . $this->escapeHtml($data->name) . "</title>\n";
        $html .= "<style>\n";
        $html .= $this->getHTMLStyles();
        $html .= "</style>\n</head>\n<body>\n";
        
        // Header
        $html .= "<div class=\"header\">\n";
        $html .= "<h1>BuildWatch Project Report</h1>\n";
        $html .= "<p class=\"subtitle\">Generated: " . date($this->dateFormatter) . "</p>\n";
        $html .= "</div>\n";
        
        // Project banner
        $html .= "<div class=\"project-banner\">\n";
        $html .= "<h2>" . $this->escapeHtml($data->name) . "</h2>\n";
        $html .= "</div>\n";
        
        $html .= "<div class=\"container\">\n";
        
        // Overview section
        $html .= "<div class=\"section\">\n";
        $html .= "<h3>Project Overview</h3>\n";
        $html .= "<div class=\"overview-grid\">\n";
        $html .= "<div><strong>Project Manager:</strong> " . $this->escapeHtml($data->createdByName) . "</div>\n";
        $html .= "<div><strong>Client:</strong> " . $this->escapeHtml($data->clientName) . "</div>\n";
        $html .= "<div><strong>Client Email:</strong> " . $this->escapeHtml($data->clientEmail) . "</div>\n";
        $html .= "<div><strong>Status:</strong> <span class=\"status-badge status-" . strtolower($data->status) . "\">" .
                $this->escapeHtml($data->status) . "</span></div>\n";
        $html .= "<div><strong>Priority:</strong> <span class=\"priority-" . strtolower($data->priority) . "\">" .
                $this->escapeHtml(strtoupper($data->priority)) . "</span></div>\n";
        $html .= "</div>\n";
        $html .= "<p style=\"margin-top: 15px;\"><strong>Description:</strong> " . $this->escapeHtml($data->description) . "</p>\n";
        $html .= "</div>\n";
        
        if (isset($data->proposalId)) {
            $html .= "<div class=\"section\">\n";
            $html .= "<h3>Proposal & Budget Information</h3>\n";
            $html .= "<div class=\"overview-grid\">\n";
            $html .= "<div><strong>Proposal Title:</strong> " . $this->escapeHtml($data->proposalTitle) . "</div>\n";
            $html .= "<div><strong>Proposal Status:</strong> <span class=\"status-badge status-" . strtolower($data->proposalStatus) . "\">" .
                    $this->escapeHtml($data->proposalStatus) . "</span></div>\n";
            $html .= "<div><strong>Proposed Amount:</strong> ₱" . number_format($data->proposedAmount, 2) . "</div>\n";
            $html .= "<div><strong>Evaluated Amount:</strong> ₱" . number_format($data->evaluatedAmount, 2) . "</div>\n";
            $html .= "<div><strong>Budget Status:</strong> <span class=\"status-badge status-" . strtolower($data->budgetStatus) . "\">" .
                    $this->escapeHtml($data->budgetStatus) . "</span></div>\n";
            $html .= "<div><strong>Client Decision:</strong> " . $this->escapeHtml($data->proposalClientDecision ?? "Pending") . "</div>\n";
            $html .= "</div>\n";
            
            if ($data->budgetRemarks) {
                $html .= "<p style=\"margin-top: 15px;\"><strong>Budget Remarks:</strong> " . $this->escapeHtml($data->budgetRemarks) . "</p>\n";
            }
            
            $html .= "</div>\n";
            
            // Budget breakdown section
            if (!empty($data->budgetBreakdowns)) {
                $html .= "<div class=\"section\">\n";
                $html .= "<h3>Budget Breakdown</h3>\n";
                $html .= $this->createBudgetBreakdownTable($data);
                $html .= "</div>\n";
            }
            
            // Budget review history
            if (!empty($data->budgetReviews)) {
                $html .= "<div class=\"section\">\n";
                $html .= "<h3>Budget Review History</h3>\n";
                $html .= "<div style=\"overflow-x: auto;\">\n";
                $html .= "<table>\n<thead>\n<tr>\n";
                $html .= "<th>Admin</th><th>Evaluated Amount</th><th>Status</th><th>Date</th>\n";
                $html .= "</tr>\n</thead>\n<tbody>\n";
                
                foreach ($data->budgetReviews as $review) {
                    $html .= "<tr>\n";
                    $html .= "<td>" . $this->escapeHtml($review->adminName) . "</td>\n";
                    $html .= "<td>₱" . number_format($review->evaluatedAmount, 2) . "</td>\n";
                    $html .= "<td><span class=\"status-badge status-" . strtolower($review->status) . "\">" .
                            $this->escapeHtml($review->status) . "</span></td>\n";
                    $html .= "<td>" . date('M d, Y H:i', strtotime($review->createdAt)) . "</td>\n";
                    $html .= "</tr>\n";
                }
                
                $html .= "</tbody>\n</table>\n";
                $html .= "</div>\n";
                $html .= "</div>\n";
            }
        }
        
        // Key metrics cards
        $html .= "<div class=\"metrics-grid\">\n";
        $html .= $this->createMetricCard("Completion", $data->completionPercentage . "%", "success");
        $html .= $this->createMetricCard("Total Tasks", (string)$data->totalTasks, "primary");
        $html .= $this->createMetricCard("Completed", (string)$data->completedTasks, "success");
        $html .= $this->createMetricCard("In Progress", (string)$data->inProgressTasks, "warning");
        $html .= $this->createMetricCard("Pending", (string)$data->pendingTasks, "info");
        $html .= $this->createMetricCard("Team Size", (string)count($data->teamMembers), "primary");
        $html .= "</div>\n";
        
        if (!empty($data->teamMembers)) {
            $html .= "<div class=\"section\">\n";
            $html .= "<h3>Team Members</h3>\n";
            $html .= "<div class=\"team-grid\">\n";
            foreach ($data->teamMembers as $member) {
                $html .= "<div class=\"team-card\">\n";
                $html .= "<div class=\"name\">" . $this->escapeHtml($member->name) . "</div>\n";
                $html .= "<div class=\"role\">" . $this->escapeHtml($member->role) . "</div>\n";
                $html .= "<div class=\"email\">" . $this->escapeHtml($member->email) . "</div>\n";
                $html .= "</div>\n";
            }
            $html .= "</div>\n";
            $html .= "</div>\n";
        }
        
        // Timeline analysis
        $html .= "<div class=\"section\">\n";
        $html .= "<h3>Timeline Analysis</h3>\n";
        $html .= "<pre style=\"white-space: pre-wrap; font-family: inherit;\">" . $this->escapeHtml($data->getTimelineAnalysis()) . "</pre>\n";
        $html .= "</div>\n";
    
        // Task table
        $html .= "<div class=\"section\">\n";
        $html .= "<h3>Task Details</h3>\n";
        $html .= $this->createTaskTable($data);
        $html .= "</div>\n";
        
        // Insights
        $html .= "<div class=\"section insights\">\n";
        $html .= "<h3>Insights & Recommendations</h3>\n";
        $html .= "<pre style=\"white-space: pre-wrap; font-family: inherit;\">" . $this->escapeHtml($data->getInsights()) . "</pre>\n";
        $html .= "</div>\n";
        
        $html .= "</div>\n";
        
        // Footer
        $html .= "<div class=\"footer\">\n";
        $html .= "<p>This report was automatically generated by BuildWatch Report Generator.</p>\n";
        $html .= "</div>\n";
        
        $html .= "</body>\n</html>";
        
        // Write to file
        file_put_contents($fileName, $html);
        
        return $fileName;
    }
    
    /**
     * Generate JSON report
     */
    private function generateJSONReport($data, $outputPath = null) {
        $fileName = $outputPath ?? 
            $this->reportsDir . "project_" . $data->id . "_report_" . 
            date($this->fileDateFormatter) . ".json";
        
        $json = [
            'reportMetadata' => [
                'generatedAt' => date($this->dateFormatter),
                'reportType' => 'comprehensive',
                'version' => '1.0'
            ],
            'project' => [
                'id' => $data->id,
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
                'clientName' => $data->clientName,
                'clientEmail' => $data->clientEmail,
                'clientCompany' => $data->clientCompany ?? 'N/A',
                'managerName' => $data->createdByName,
                'completionPercentage' => $data->completionPercentage,
                'budget' => $data->budget,
                'actualCost' => $data->actualCost,
                'location' => $data->location
            ],
            'proposal' => [
                'id' => $data->proposalId ?? null,
                'title' => $data->proposalTitle ?? null,
                'status' => $data->proposalStatus ?? null,
                'clientDecision' => $data->proposalClientDecision ?? null
            ],
            'budgetInfo' => [
                'proposedAmount' => $data->proposedAmount ?? 0,
                'evaluatedAmount' => $data->evaluatedAmount ?? 0,
                'status' => $data->budgetStatus ?? null,
                'totalBreakdownCost' => $data->totalBreakdownCost ?? 0,
                'breakdownItems' => []
            ],
            'metrics' => [
                'totalTasks' => $data->totalTasks,
                'completedTasks' => $data->completedTasks,
                'inProgressTasks' => $data->inProgressTasks,
                'pendingTasks' => $data->pendingTasks,
                'blockedTasks' => $data->blockedTasks,
                'averageTaskProgress' => round($data->averageTaskProgress, 2),
                'budgetUtilization' => round($data->getBudgetUtilizationPercentage(), 2),
                'daysElapsed' => $data->daysElapsed,
                'daysRemaining' => $data->daysRemaining
            ],
            'tasks' => [],
            'budgetReviews' => [],
            'insights' => $data->getInsights()
        ];
        
        // Add budget breakdown items
        if (!empty($data->budgetBreakdowns)) {
            foreach ($data->budgetBreakdowns as $item) {
                $json['budgetInfo']['breakdownItems'][] = [
                    'itemName' => $item->itemName,
                    'category' => $item->category,
                    'estimatedCost' => $item->estimatedCost
                ];
            }
        }
        
        // Add budget reviews
        if (!empty($data->budgetReviews)) {
            foreach ($data->budgetReviews as $review) {
                $json['budgetReviews'][] = [
                    'adminName' => $review->adminName,
                    'evaluatedAmount' => $review->evaluatedAmount,
                    'status' => $review->status,
                    'createdAt' => $review->createdAt
                ];
            }
        }
        
        foreach ($data->tasks as $task) {
            $json['tasks'][] = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'progress' => $task->progress,
                'priority' => $task->priority,
                'assignedTo' => $task->assignedTo
            ];
        }
        
        file_put_contents($fileName, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return $fileName;
    }
    
    /**
     * Generate text report
     */
    private function generateTextReport($data, $outputPath = null) {
        $fileName = $outputPath ?? 
            $this->reportsDir . "project_" . $data->id . "_report_" . 
            date($this->fileDateFormatter) . ".txt";
        
        $text = "================================================================================\n";
        $text .= "                      BUILDWATCH PROJECT REPORT\n";
        $text .= "================================================================================\n";
        $text .= "Generated: " . date($this->dateFormatter) . "\n\n";
        
        $text .= "PROJECT: " . $data->name . "\n";
        $text .= "================================================================================\n\n";
        
        $text .= "PROJECT OVERVIEW\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getOverviewText() . "\n\n";
        
        if (isset($data->proposalId)) {
            $text .= "PROPOSAL & BUDGET INFORMATION\n";
            $text .= "--------------------------------------------------------------------------------\n";
            $text .= sprintf("Proposal Title: %s\n", $data->proposalTitle);
            $text .= sprintf("Proposal Status: %s\n", $data->proposalStatus);
            $text .= sprintf("Proposed Amount: ₱%.2f\n", $data->proposedAmount);
            $text .= sprintf("Evaluated Amount: ₱%.2f\n", $data->evaluatedAmount);
            $text .= sprintf("Budget Status: %s\n", $data->budgetStatus);
            $text .= sprintf("Client Decision: %s\n\n", $data->proposalClientDecision ?? "Pending");
            
            if (!empty($data->budgetBreakdowns)) {
                $text .= "BUDGET BREAKDOWN\n";
                $text .= "--------------------------------------------------------------------------------\n";
                foreach ($data->budgetBreakdowns as $item) {
                    $text .= sprintf("• %s (%s): ₱%.2f\n", $item->itemName, ucfirst($item->category), $item->estimatedCost);
                }
                $text .= sprintf("Total Breakdown Cost: ₱%.2f\n\n", $data->totalBreakdownCost);
            }
            
            if (!empty($data->budgetReviews)) {
                $text .= "BUDGET REVIEW HISTORY\n";
                $text .= "--------------------------------------------------------------------------------\n";
                foreach ($data->budgetReviews as $review) {
                    $text .= sprintf("• %s - ₱%.2f (%s) - %s\n", 
                        $review->adminName, $review->evaluatedAmount, $review->status, 
                        date('M d, Y H:i', strtotime($review->createdAt)));
                }
                $text .= "\n";
            }
        }
        
        $text .= "KEY METRICS\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getMetricsText() . "\n\n";
        
        $text .= "TIMELINE ANALYSIS\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getTimelineAnalysis() . "\n\n";
        
        $text .= "BUDGET ANALYSIS\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getBudgetAnalysis() . "\n\n";
        
        $text .= "TASK SUMMARY\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getTaskSummary() . "\n\n";
        
        $text .= "DETAILED TASK LIST\n";
        $text .= "--------------------------------------------------------------------------------\n";
        if (empty($data->tasks)) {
            $text .= "No tasks have been created for this project yet.\n";
        } else {
            foreach ($data->tasks as $task) {
                $text .= sprintf("• %s [%s] - %d%% - %s\n", 
                    $task->title, $task->status, $task->progress, 
                    $task->assignedTo ?? "Unassigned");
            }
        }
        $text .= "\n";
        
        $text .= "INSIGHTS & RECOMMENDATIONS\n";
        $text .= "--------------------------------------------------------------------------------\n";
        $text .= $data->getInsights() . "\n\n";
        
        $text .= "================================================================================\n";
        $text .= "Report generated by BuildWatch Report Generator\n";
        $text .= "================================================================================\n";
        
        file_put_contents($fileName, $text);
        
        return $fileName;
    }
    
    /**
     * Escape HTML special characters
     */
    private function escapeHtml($text) {
        if ($text === null) return "";
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Data class to hold project information
 */
class ProjectData {
    public $id;
    public $name;
    public $description;
    public $status;
    public $createdByName;
    public $clientId;
    
    public $priority;
    public $category;
    public $timeline;
    public $completionPercentage;
    
    public $startDate;
    public $endDate;
    public $createdAt;
    public $lastActivityAt;
    
    public $budget = 0.0;
    public $totalHoursSpent = 0.0;
    public $estimatedHours = 0.0;
    
    public $clientName = "N/A";
    public $clientEmail = "N/A";
    public $clientCompany = "N/A";
    public $actualCost = 0.0;
    public $location = "N/A";
    
    public $proposalId;
    public $proposalTitle;
    public $proposalDescription;
    public $proposalStatus;
    public $proposalBudget = 0.0;
    public $proposalStartDate;
    public $proposalEndDate;
    public $proposalClientDecision;
    
    public $budgetId;
    public $proposedAmount = 0.0;
    public $evaluatedAmount = 0.0;
    public $budgetStatus;
    public $budgetRemarks;
    public $adminComment;
    public $clientBudgetDecision;
    
    public $budgetBreakdowns = [];
    public $totalBreakdownCost = 0.0;
    public $budgetReviews = [];
    
    public $tasks = [];
    public $teamMembers = [];
    
    // Calculated metrics
    public $totalTasks = 0;
    public $completedTasks = 0;
    public $inProgressTasks = 0;
    public $pendingTasks = 0;
    public $blockedTasks = 0;
    public $averageTaskProgress = 0.0;
    public $daysElapsed = 0;
    public $daysRemaining = 0;
    
    public function calculateMetrics() {
        $this->totalTasks = count($this->tasks);
        $this->completedTasks = 0;
        $this->inProgressTasks = 0;
        $this->pendingTasks = 0;
        $this->blockedTasks = 0;
        $totalProgress = 0;
        
        foreach ($this->tasks as $task) {
            $totalProgress += $task->progress;
            
            if ($task->status) {
                switch (strtolower($task->status)) {
                    case "completed":
                        $this->completedTasks++;
                        break;
                    case "in progress":
                        $this->inProgressTasks++;
                        break;
                    case "pending":
                        $this->pendingTasks++;
                        break;
                    case "blocked":
                        $this->blockedTasks++;
                        break;
                }
            }
        }
        
        $this->averageTaskProgress = $this->totalTasks > 0 ? $totalProgress / $this->totalTasks : 0;
        
        if ($this->startDate) {
            $startDateTime = new DateTime($this->startDate);
            $now = new DateTime();
            $this->daysElapsed = $now->diff($startDateTime)->days;
        }
        
        if ($this->endDate) {
            $endDateTime = new DateTime($this->endDate);
            $now = new DateTime();
            $this->daysRemaining = $endDateTime->diff($now)->days;
        }
    }
    
    public function getOverviewText() {
        return sprintf(
            "Project Name: %s\n" .
            "Project Manager: %s\n" .
            "Client: %s\n" .
            "Client Email: %s\n" .
            "Status: %s\n" .
            "Priority: %s\n" .
            "Category: %s\n" .
            "Description: %s",
            $this->name, $this->createdByName, $this->clientName, $this->clientEmail,
            $this->status, $this->priority, $this->category ?? "N/A", $this->description
        );
    }
    
    public function getMetricsText() {
        return sprintf(
            "Overall Completion: %d%%\n" .
            "Total Tasks: %d\n" .
            "Completed Tasks: %d\n" .
            "In Progress: %d\n" .
            "Pending: %d\n" .
            "Average Task Progress: %.1f%%\n" .
            "Total Hours Spent: %.1f\n" .
            "Estimated Hours: %.1f\n" .
            "Team Members: %d",
            $this->completionPercentage, $this->totalTasks, $this->completedTasks, 
            $this->inProgressTasks, $this->pendingTasks, $this->averageTaskProgress,
            $this->totalHoursSpent, $this->estimatedHours, count($this->teamMembers)
        );
    }
    
    public function getTimelineAnalysis() {
        if (!$this->startDate && !$this->endDate) {
            return "Timeline information not available for this project.";
        }
        
        $analysis = "";
        
        if ($this->startDate) {
            $startDateTime = new DateTime($this->startDate);
            $analysis .= sprintf("Project started: %s\n", $startDateTime->format('M d, Y'));
            $analysis .= sprintf("Days elapsed: %d days\n", $this->daysElapsed);
        }
        
        if ($this->endDate) {
            $endDateTime = new DateTime($this->endDate);
            $analysis .= sprintf("Expected completion: %s\n", $endDateTime->format('M d, Y'));
            $analysis .= sprintf("Days remaining: %d days\n", $this->daysRemaining);
            
            if ($this->daysRemaining < 0) {
                $analysis .= "⚠ WARNING: Project is overdue!\n";
            } elseif ($this->daysRemaining < 7) {
                $analysis .= "⚠ ALERT: Project deadline is approaching soon!\n";
            }
        }
        
        if ($this->timeline) {
            $analysis .= sprintf("Timeline: %s\n", $this->timeline);
        }
        
        return $analysis;
    }
    
    public function getBudgetAnalysis() {
        $analysis = sprintf("Total Budget: $%.2f\n", $this->budget);
        $analysis .= sprintf("Proposed Amount: ₱%.2f\n", $this->proposedAmount);
        $analysis .= sprintf("Evaluated Amount: ₱%.2f\n", $this->evaluatedAmount);
        $analysis .= sprintf("Budget Breakdown Total: ₱%.2f\n", $this->totalBreakdownCost);
        $analysis .= sprintf("Total Hours Spent: %.1f hours\n", $this->totalHoursSpent);
        $analysis .= sprintf("Estimated Hours: %.1f hours\n", $this->estimatedHours);
        
        if ($this->estimatedHours > 0) {
            $hoursUtilization = ($this->totalHoursSpent / $this->estimatedHours) * 100;
            $analysis .= sprintf("Hours Utilization: %.1f%%\n", $hoursUtilization);
            
            if ($hoursUtilization > 100) {
                $analysis .= "⚠ WARNING: Project has exceeded estimated hours!\n";
            } elseif ($hoursUtilization > 90) {
                $analysis .= "⚠ ALERT: Hours utilization is high. Monitor time carefully.\n";
            }
        }
        
        if ($this->budget > 0 && $this->completionPercentage > 0) {
            $expectedSpend = ($this->budget * $this->completionPercentage) / 100;
            $analysis .= sprintf("\nExpected spend at %d%% completion: $%.2f\n", 
                $this->completionPercentage, $expectedSpend);
        }
        
        return $analysis;
    }
    
    public function getTaskSummary() {
        if ($this->totalTasks == 0) {
            return "No tasks have been created for this project yet.";
        }
        
        return sprintf(
            "Task Distribution:\n" .
            "• Completed: %d (%.1f%%)\n" .
            "• In Progress: %d (%.1f%%)\n" .
            "• Pending: %d (%.1f%%)\n\n" .
            "The project has %d total tasks with an average progress of %.1f%%.",
            $this->completedTasks, ($this->completedTasks * 100.0 / $this->totalTasks),
            $this->inProgressTasks, ($this->inProgressTasks * 100.0 / $this->totalTasks),
            $this->pendingTasks, ($this->pendingTasks * 100.0 / $this->totalTasks),
            $this->totalTasks, $this->averageTaskProgress
        );
    }
    
    public function getInsights() {
        $insights = "";
        
        // Project health assessment
        if ($this->completionPercentage >= 75) {
            $insights .= "✓ Project is in good shape with strong completion progress.\n\n";
        } elseif ($this->completionPercentage >= 50) {
            $insights .= "→ Project is progressing steadily. Continue monitoring key milestones.\n\n";
        } elseif ($this->completionPercentage >= 25) {
            $insights .= "⚠ Project is in early-mid stages. Ensure resources are allocated properly.\n\n";
        } else {
            $insights .= "→ Project is in early stages. Focus on establishing momentum.\n\n";
        }
        
        // Priority-based insights
        if (strtolower($this->priority) === "high") {
            $insights .= "⚠ HIGH PRIORITY PROJECT: Ensure adequate resources and attention.\n\n";
        }
        
        // Task-based insights
        if ($this->blockedTasks > 0) {
            $insights .= sprintf("⚠ ATTENTION: %d task(s) are blocked. " .
                "Immediate action required to unblock and maintain project velocity.\n\n", $this->blockedTasks);
        }
        
        if ($this->pendingTasks > $this->totalTasks * 0.5) {
            $insights .= "⚠ High number of pending tasks. Consider prioritizing and assigning resources.\n\n";
        }
        
        // Timeline insights
        if ($this->daysRemaining > 0 && $this->daysRemaining < 14) {
            $insights .= "⚠ Project deadline is approaching within 2 weeks. " .
                "Prioritize critical path tasks.\n\n";
        }
        
        // Hours insights
        if ($this->estimatedHours > 0 && $this->totalHoursSpent > $this->estimatedHours) {
            $insights .= "⚠ Project has exceeded estimated hours. Review scope and timeline.\n\n";
        }
        
        // Budget insights
        if ($this->evaluatedAmount > $this->proposedAmount && $this->proposedAmount > 0) {
            $difference = $this->evaluatedAmount - $this->proposedAmount;
            $insights .= sprintf("⚠ Budget has been increased by ₱%.2f (%.1f%%) during evaluation.\n\n", 
                $difference, ($difference / $this->proposedAmount) * 100);
        }
        
        // Team insights
        if (count($this->teamMembers) < 2 && $this->totalTasks > 10) {
            $insights .= "⚠ Consider adding more team members given the number of tasks.\n\n";
        }
        
        // Recommendations
        $insights .= "RECOMMENDATIONS:\n";
        
        if ($this->completedTasks < $this->totalTasks * 0.3 && $this->daysElapsed > 30) {
            $insights .= "• Accelerate task completion rate to meet project timeline.\n";
        }
        
        if ($this->blockedTasks > 0) {
            $insights .= "• Resolve blocked tasks as highest priority.\n";
        }
        
        if ($this->pendingTasks > 5) {
            $insights .= "• Assign pending tasks to team members to maintain momentum.\n";
        }
        
        if ($this->budgetStatus === 'pending_client') {
            $insights .= "• Follow up with client on budget decision.\n";
        }
        
        $insights .= "• Maintain regular communication with stakeholders.\n";
        $insights .= "• Continue monitoring progress and adjust plans as needed.\n";
        
        return $insights;
    }
    
    public function getBudgetStatus() {
        if ($this->budget == 0) return "N/A";
        if ($this->estimatedHours > 0) {
            $hoursUtilization = ($this->totalHoursSpent / $this->estimatedHours) * 100;
            return sprintf("%.1f%%", $hoursUtilization);
        }
        return "N/A";
    }
    
    public function getBudgetUtilizationPercentage() {
        if ($this->estimatedHours == 0) return 0;
        return ($this->totalHoursSpent / $this->estimatedHours) * 100;
    }
}

/**
 * Data class to hold budget breakdown information
 */
class BudgetBreakdown {
    public $id;
    public $itemName;
    public $category;
    public $estimatedCost;
    public $createdAt;
}

/**
 * Data class to hold budget review information
 */
class BudgetReview {
    public $id;
    public $adminName;
    public $evaluatedAmount;
    public $status;
    public $remarks;
    public $createdAt;
}

/**
 * Data class to hold task information
 */
class TaskData {
    public $id;
    public $title;
    public $description;
    public $status;
    public $progress;
    public $priority;
    public $assignedTo;
    public $dueDate;
    public $completedAt;
}

/**
 * Data class to hold team member information
 */
class TeamMember {
    public $id;
    public $name;
    public $email;
    public $role;
    public $assignedAt;
}
