import java.io.*;
import java.sql.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;
import java.util.*;

/**
 * Comprehensive Report Generator for BuildWatch
 * Generates detailed project reports with metrics, insights, and analytics
 * Simplified version without external dependencies (PDF library removed)
 */
public class ReportGenerator {
    
    // Database configuration - reads from environment or uses defaults
    private static String DB_HOST = System.getProperty("db.host", "localhost");
    private static String DB_PORT = System.getProperty("db.port", "3306");
    private static String DB_NAME = System.getProperty("db.name", "buildwatch");
    private static String DB_USER = System.getProperty("db.user", "root");
    private static String DB_PASS = System.getProperty("db.pass", "");
    
    private static final String DB_URL = "jdbc:mysql://" + DB_HOST + ":" + DB_PORT + "/" + DB_NAME + "?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true";
    
    // Report configuration
    private static String REPORTS_DIR = "../reports/";
    private static final DateTimeFormatter DATE_FORMATTER = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    private static final DateTimeFormatter FILE_DATE_FORMATTER = DateTimeFormatter.ofPattern("yyyyMMdd_HHmmss");
    
    // Color scheme for PDF reports - REMOVED as PDF generation is removed
    // private static final BaseColor PRIMARY_COLOR = new BaseColor(10, 66, 117);
    // private static final BaseColor SECONDARY_COLOR = new BaseColor(46, 204, 113);
    // private static final BaseColor ACCENT_COLOR = new BaseColor(212, 47, 19);
    // private static final BaseColor GRAY_COLOR = new BaseColor(128, 128, 128);
    
    /**
     * Main entry point for report generation
     * Usage: java ReportGenerator <project_id> <format> [output_path]
     */
    public static void main(String[] args) {
        if (args.length < 2) {
            System.err.println("Usage: java ReportGenerator <project_id> <format> [output_path]");
            System.err.println("Formats: html, json, txt");
            System.exit(1);
        }
        
        try {
            int projectId = Integer.parseInt(args[0]);
            String format = args[1].toLowerCase();
            String outputPath = args.length > 2 ? args[2] : null;
            
            if (outputPath != null && !outputPath.contains("/") && !outputPath.contains("\\")) {
                // If outputPath is just a directory name, use it as REPORTS_DIR
                REPORTS_DIR = outputPath.endsWith("/") || outputPath.endsWith("\\") ? outputPath : outputPath + "/";
                outputPath = null;
            } else if (outputPath != null && (outputPath.endsWith("/") || outputPath.endsWith("\\"))) {
                // If outputPath ends with a separator, it's a directory
                REPORTS_DIR = outputPath;
                outputPath = null;
            } else if (outputPath != null) {
                // Check if it's a directory path (contains path separators but no file extension)
                File f = new File(outputPath);
                if (f.isDirectory() || (!outputPath.contains(".") && (outputPath.contains("/") || outputPath.contains("\\")))) {
                    REPORTS_DIR = outputPath.endsWith("/") || outputPath.endsWith("\\") ? outputPath : outputPath + File.separator;
                    outputPath = null;
                }
            }
            
            // Create reports directory if it doesn't exist
            File reportsDir = new File(REPORTS_DIR);
            if (!reportsDir.exists()) {
                reportsDir.mkdirs();
            }
            
            System.err.println("[DEBUG] Starting report generation...");
            System.err.println("[DEBUG] Project ID: " + projectId);
            System.err.println("[DEBUG] Format: " + format);
            System.err.println("[DEBUG] Reports Directory: " + REPORTS_DIR);
            System.err.println("[DEBUG] Database URL: " + DB_URL);
            System.err.println("[DEBUG] Database User: " + DB_USER);
            
            ReportGenerator generator = new ReportGenerator();
            String reportPath = generator.generateReport(projectId, format, outputPath);
            
            System.out.println("SUCCESS:" + reportPath);
            
        } catch (NumberFormatException e) {
            System.err.println("ERROR: Invalid project ID. Must be a number.");
            e.printStackTrace();
            System.exit(1);
        } catch (Exception e) {
            System.err.println("ERROR: " + e.getMessage());
            e.printStackTrace();
            System.exit(1);
        }
    }
    
    /**
     * Generate a comprehensive report for the specified project
     */
    public String generateReport(int projectId, String format, String outputPath) throws Exception {
        // Load JDBC driver
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            // Try older driver
            try {
                Class.forName("com.mysql.jdbc.Driver");
            } catch (ClassNotFoundException e2) {
                throw new Exception("MySQL JDBC driver not found. Please ensure mysql-connector-java.jar is in the classpath.");
            }
        }
        
        // Fetch project data
        ProjectData projectData = fetchProjectData(projectId);
        
        if (projectData == null) {
            throw new Exception("Project not found with ID: " + projectId);
        }
        
        // Generate report based on format
        String reportPath;
        switch (format) {
            case "html":
                reportPath = generateHTMLReport(projectData, outputPath);
                break;
            case "json":
                reportPath = generateJSONReport(projectData, outputPath);
                break;
            case "txt":
                reportPath = generateTextReport(projectData, outputPath);
                break;
            case "pdf":
                // PDF generation requires external library - fallback to HTML
                System.err.println("WARNING: PDF generation requires iText library. Generating HTML instead.");
                reportPath = generateHTMLReport(projectData, outputPath);
                break;
            default:
                throw new Exception("Unsupported format: " + format + ". Supported formats: html, json, txt");
        }
        
        return reportPath;
    }
    
    /**
     * Fetch comprehensive project data from database
     */
    private ProjectData fetchProjectData(int projectId) throws Exception {
        Connection conn = null;
        ProjectData data = new ProjectData();
        
        try {
            System.err.println("[DEBUG] Connecting to database: " + DB_URL);
            conn = DriverManager.getConnection(DB_URL, DB_USER, DB_PASS);
            System.err.println("[DEBUG] Database connection successful");
            
            data = fetchProjectData(conn, projectId);
            
        } catch (SQLException e) {
            System.err.println("[DEBUG] SQL Error: " + e.getMessage());
            throw e;
        } finally {
            if (conn != null) {
                conn.close();
            }
        }
        
        return data;
    }

    private static ProjectData fetchProjectData(Connection conn, int projectId) throws Exception {
        String query = "SELECT p.id, p.name, p.description, p.status, " +
                      "p.completion_percentage, p.priority, p.budget, p.timeline, " +
                      "p.start_date, p.end_date, p.category, p.created_at, " +
                      "p.last_activity_at, p.total_hours_spent, p.estimated_hours, " +
                      "p.created_by, p.client_id, " +
                      "u.name as created_by_name " +
                      "FROM projects p " +
                      "LEFT JOIN users u ON p.created_by = u.id " +
                      "WHERE p.id = ?";
        
        try (PreparedStatement stmt = conn.prepareStatement(query)) {
            stmt.setInt(1, projectId);
            ResultSet rs = stmt.executeQuery();
            
            if (!rs.next()) {
                throw new Exception("Project not found with ID: " + projectId);
            }
            
            System.err.println("[DEBUG] Project found: " + rs.getString("name"));
            
            ProjectData data = new ProjectData();
            data.id = rs.getInt("id");
            data.name = rs.getString("name");
            data.description = rs.getString("description");
            data.status = rs.getString("status");
            data.createdByName = rs.getString("created_by_name");
            data.clientId = rs.getInt("client_id");
            
            data.priority = rs.getString("priority");
            data.category = rs.getString("category");
            data.timeline = rs.getString("timeline");
            data.completionPercentage = rs.getInt("completion_percentage");
            
            // Date fields
            data.startDate = rs.getDate("start_date");
            data.endDate = rs.getDate("end_date");
            data.createdAt = rs.getTimestamp("created_at");
            data.lastActivityAt = rs.getTimestamp("last_activity_at");
            
            // Budget and hours
            data.budget = rs.getDouble("budget");
            data.totalHoursSpent = rs.getDouble("total_hours_spent");
            data.estimatedHours = rs.getDouble("estimated_hours");
            
            fetchTasks(conn, data);
            
            fetchTeamMembers(conn, data);
            
            // Calculate metrics
            data.calculateMetrics();
            
            return data;
        }
    }
    
    private static void fetchTasks(Connection conn, ProjectData data) throws SQLException {
        String taskQuery = "SELECT t.id, t.title, t.description, t.progress, t.due_date, t.created_at, " +
                          "u.name as assigned_to_name " +
                          "FROM tasks t " +
                          "LEFT JOIN users u ON t.assigned_to = u.id " +
                          "WHERE t.project_id = ? " +
                          "ORDER BY t.created_at DESC";
        
        try (PreparedStatement stmt = conn.prepareStatement(taskQuery)) {
            stmt.setInt(1, data.id);
            ResultSet rs = stmt.executeQuery();
            
            while (rs.next()) {
                TaskData task = new TaskData();
                task.id = rs.getInt("id");
                task.title = rs.getString("title");
                task.description = rs.getString("description");
                task.progress = rs.getInt("progress");
                task.assignedTo = rs.getString("assigned_to_name");
                task.dueDate = rs.getTimestamp("due_date");
                
                // Determine status based on progress
                if (task.progress == 100) {
                    task.status = "Completed";
                } else if (task.progress > 0) {
                    task.status = "In Progress";
                } else {
                    task.status = "Pending";
                }
                
                task.priority = "Normal"; // Default priority
                
                data.tasks.add(task);
            }
        }
    }
    
    private static void fetchTeamMembers(Connection conn, ProjectData data) throws SQLException {
        String teamQuery = "SELECT u.id, u.name, u.email, u.role, pa.assigned_at " +
                          "FROM project_assignments pa " +
                          "JOIN users u ON pa.user_id = u.id " +
                          "WHERE pa.project_id = ? " +
                          "ORDER BY pa.assigned_at ASC";
        
        try (PreparedStatement stmt = conn.prepareStatement(teamQuery)) {
            stmt.setInt(1, data.id);
            ResultSet rs = stmt.executeQuery();
            
            while (rs.next()) {
                TeamMember member = new TeamMember();
                member.id = rs.getInt("id");
                member.name = rs.getString("name");
                member.email = rs.getString("email");
                member.role = rs.getString("role");
                member.assignedAt = rs.getTimestamp("assigned_at");
                
                data.teamMembers.add(member);
            }
        }
    }

    private String getHTMLStyles() {
        return "* { margin: 0; padding: 0; box-sizing: border-box; }\n" +
               "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f5f5; color: #333; line-height: 1.6; }\n" +
               ".header { background: linear-gradient(135deg, #0a4275 0%, #084980 100%); color: white; padding: 40px 20px; text-align: center; }\n" +
               ".header h1 { margin: 0; font-size: 32px; }\n" +
               ".subtitle { margin: 10px 0 0 0; opacity: 0.9; font-size: 14px; }\n" +
               ".project-banner { background: #0a4275; color: white; padding: 20px; text-align: center; margin-bottom: 30px; }\n" +
               ".project-banner h2 { margin: 0; font-size: 24px; }\n" +
               ".container { max-width: 1200px; margin: 0 auto; padding: 20px; }\n" +
               ".section { background: white; padding: 25px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n" +
               ".section h3 { color: #0a4275; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #0a4275; padding-bottom: 10px; }\n" +
               ".overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }\n" +
               ".metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 20px; }\n" +
               ".metric-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }\n" +
               ".metric-card.success { border-left: 4px solid #2ecc71; }\n" +
               ".metric-card.primary { border-left: 4px solid #0a4275; }\n" +
               ".metric-card.warning { border-left: 4px solid #f39c12; }\n" +
               ".metric-card.info { border-left: 4px solid #3498db; }\n" +
               ".metric-card.danger { border-left: 4px solid #e74c3c; }\n" +
               ".metric-label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; }\n" +
               ".metric-value { font-size: 28px; font-weight: bold; color: #333; }\n" +
               ".team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }\n" +
               ".team-card { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 3px solid #0a4275; }\n" +
               ".team-card .name { font-weight: 600; color: #0a4275; margin-bottom: 5px; }\n" +
               ".team-card .role { font-size: 12px; color: #666; text-transform: uppercase; }\n" +
               ".team-card .email { font-size: 13px; color: #888; margin-top: 5px; }\n" +
               "table { width: 100%; border-collapse: collapse; margin-top: 15px; }\n" +
               "th { background: #0a4275; color: white; padding: 12px; text-align: left; font-weight: 600; }\n" +
               "td { padding: 10px; border-bottom: 1px solid #ddd; }\n" +
               "tr:hover { background: #f9f9f9; }\n" +
               ".status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }\n" +
               ".status-completed { background: #d4edda; color: #155724; }\n" +
               ".status-in-progress { background: #fff3cd; color: #856404; }\n" +
               ".status-pending { background: #d1ecf1; color: #0c5460; }\n" +
               ".status-blocked { background: #f8d7da; color: #721c24; }\n" +
               ".status-ongoing { background: #d1ecf1; color: #0c5460; }\n" +
               ".status-on-hold { background: #fff3cd; color: #856404; }\n" +
               ".status-planning { background: #e2e3e5; color: #383d41; }\n" +
               ".priority-high { color: #e74c3c; font-weight: 600; }\n" +
               ".priority-medium { color: #f39c12; font-weight: 600; }\n" +
               ".priority-low { color: #95a5a6; font-weight: 600; }\n" +
               ".insights { background: #e8f4f8; border-left: 4px solid #3498db; }\n" +
               ".footer { background: #333; color: white; text-align: center; padding: 20px; margin-top: 40px; font-size: 12px; }\n" +
               "@media print { body { background: white; } .section { box-shadow: none; page-break-inside: avoid; } }\n" +
               "@media (max-width: 768px) { .metrics-grid, .overview-grid, .team-grid { grid-template-columns: 1fr; } .container { padding: 10px; } }\n";
    }
    
    private String createMetricCard(String label, String value, String type) {
        return "<div class=\"metric-card " + type + "\">\n" +
               "<div class=\"metric-label\">" + label + "</div>\n" +
               "<div class=\"metric-value\">" + value + "</div>\n" +
               "</div>\n";
    }
    
    private String createTaskTable(ProjectData data) {
        if (data.tasks.isEmpty()) {
            return "<p style=\"color: #888; font-style: italic;\">No tasks have been created for this project yet.</p>";
        }
        
        StringBuilder table = new StringBuilder();
        table.append("<div style=\"overflow-x: auto;\">\n");
        table.append("<table>\n<thead>\n<tr>\n");
        table.append("<th>Task</th><th>Assigned To</th><th>Status</th><th>Progress</th><th>Due Date</th>\n");
        table.append("</tr>\n</thead>\n<tbody>\n");
        
        for (TaskData task : data.tasks) {
            table.append("<tr>\n");
            table.append("<td>").append(escapeHtml(task.title)).append("</td>\n");
            table.append("<td>").append(escapeHtml(task.assignedTo != null ? task.assignedTo : "Unassigned")).append("</td>\n");
            table.append("<td><span class=\"status-badge status-").append(task.status.toLowerCase().replace(" ", "-")).append("\">")
                 .append(escapeHtml(task.status)).append("</span></td>\n");
            table.append("<td>").append(task.progress).append("%</td>\n");
            table.append("<td>").append(task.dueDate != null ? 
                task.dueDate.toLocalDateTime().format(DateTimeFormatter.ofPattern("MMM dd, yyyy")) : "N/A").append("</td>\n");
            table.append("</tr>\n");
        }
        
        table.append("</tbody>\n</table>\n");
        table.append("</div>\n");
        return table.toString();
    }
    
    /**
     * Generate HTML report for web display
     */
    private String generateHTMLReport(ProjectData data, String outputPath) throws IOException {
        String fileName = outputPath != null ? outputPath : 
            REPORTS_DIR + "project_" + data.id + "_report_" + 
            LocalDateTime.now().format(FILE_DATE_FORMATTER) + ".html";
        
        StringBuilder html = new StringBuilder();
        html.append("<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n");
        html.append("<meta charset=\"UTF-8\">\n");
        html.append("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n");
        html.append("<title>Project Report - ").append(escapeHtml(data.name)).append("</title>\n");
        html.append("<style>\n");
        html.append(getHTMLStyles());
        html.append("</style>\n</head>\n<body>\n");
        
        // Header
        html.append("<div class=\"header\">\n");
        html.append("<h1>BuildWatch Project Report</h1>\n");
        html.append("<p class=\"subtitle\">Generated: ").append(LocalDateTime.now().format(DATE_FORMATTER)).append("</p>\n");
        html.append("</div>\n");
        
        // Project banner
        html.append("<div class=\"project-banner\">\n");
        html.append("<h2>").append(escapeHtml(data.name)).append("</h2>\n");
        html.append("</div>\n");
        
        html.append("<div class=\"container\">\n");
        
        // Overview section
        html.append("<div class=\"section\">\n");
        html.append("<h3>Project Overview</h3>\n");
        html.append("<div class=\"overview-grid\">\n");
        html.append("<div><strong>Project Manager:</strong> ").append(escapeHtml(data.createdByName)).append("</div>\n");
        html.append("<div><strong>Status:</strong> <span class=\"status-badge status-").append(data.status.toLowerCase()).append("\">")
            .append(escapeHtml(data.status)).append("</span></div>\n");
        html.append("<div><strong>Priority:</strong> <span class=\"priority-").append(data.priority.toLowerCase()).append("\">")
            .append(escapeHtml(data.priority.toUpperCase())).append("</span></div>\n");
        html.append("<div><strong>Category:</strong> ").append(escapeHtml(data.category != null ? data.category : "N/A")).append("</div>\n");
        html.append("<div><strong>Timeline:</strong> ").append(escapeHtml(data.timeline != null ? data.timeline : "N/A")).append("</div>\n");
        html.append("<div><strong>Budget:</strong> $").append(String.format("%.2f", data.budget)).append("</div>\n");
        html.append("</div>\n");
        html.append("<p style=\"margin-top: 15px;\"><strong>Description:</strong> ").append(escapeHtml(data.description)).append("</p>\n");
        html.append("</div>\n");
        
        // Key metrics cards
        html.append("<div class=\"metrics-grid\">\n");
        html.append(createMetricCard("Completion", data.completionPercentage + "%", "success"));
        html.append(createMetricCard("Total Tasks", String.valueOf(data.totalTasks), "primary"));
        html.append(createMetricCard("Completed", String.valueOf(data.completedTasks), "success"));
        html.append(createMetricCard("In Progress", String.valueOf(data.inProgressTasks), "warning"));
        html.append(createMetricCard("Pending", String.valueOf(data.pendingTasks), "info"));
        html.append(createMetricCard("Hours Spent", String.format("%.1f", data.totalHoursSpent), "primary"));
        html.append(createMetricCard("Est. Hours", String.format("%.1f", data.estimatedHours), "info"));
        html.append(createMetricCard("Team Size", String.valueOf(data.teamMembers.size()), "primary"));
        html.append("</div>\n");
        
        if (!data.teamMembers.isEmpty()) {
            html.append("<div class=\"section\">\n");
            html.append("<h3>Team Members</h3>\n");
            html.append("<div class=\"team-grid\">\n");
            for (TeamMember member : data.teamMembers) {
                html.append("<div class=\"team-card\">\n");
                html.append("<div class=\"name\">").append(escapeHtml(member.name)).append("</div>\n");
                html.append("<div class=\"role\">").append(escapeHtml(member.role)).append("</div>\n");
                html.append("<div class=\"email\">").append(escapeHtml(member.email)).append("</div>\n");
                html.append("</div>\n");
            }
            html.append("</div>\n");
            html.append("</div>\n");
        }
        
        // Timeline analysis
        html.append("<div class=\"section\">\n");
        html.append("<h3>Timeline Analysis</h3>\n");
        html.append("<pre style=\"white-space: pre-wrap; font-family: inherit;\">").append(escapeHtml(data.getTimelineAnalysis())).append("</pre>\n");
        html.append("</div>\n");
        
        // Budget analysis
        html.append("<div class=\"section\">\n");
        html.append("<h3>Budget & Hours Analysis</h3>\n");
        html.append("<pre style=\"white-space: pre-wrap; font-family: inherit;\">").append(escapeHtml(data.getBudgetAnalysis())).append("</pre>\n");
        html.append("</div>\n");
        
        // Task table
        html.append("<div class=\"section\">\n");
        html.append("<h3>Task Details</h3>\n");
        html.append(createTaskTable(data));
        html.append("</div>\n");
        
        // Insights
        html.append("<div class=\"section insights\">\n");
        html.append("<h3>Insights & Recommendations</h3>\n");
        html.append("<pre style=\"white-space: pre-wrap; font-family: inherit;\">").append(escapeHtml(data.getInsights())).append("</pre>\n");
        html.append("</div>\n");
        
        html.append("</div>\n");
        
        // Footer
        html.append("<div class=\"footer\">\n");
        html.append("<p>This report was automatically generated by BuildWatch Report Generator.</p>\n");
        html.append("</div>\n");
        
        html.append("</body>\n</html>");
        
        // Write to file
        try (FileWriter writer = new FileWriter(fileName)) {
            writer.write(html.toString());
        }
        
        return fileName;
    }
    
    /**
     * Generate JSON report for API consumption
     */
    private String generateJSONReport(ProjectData data, String outputPath) throws IOException {
        String fileName = outputPath != null ? outputPath : 
            REPORTS_DIR + "project_" + data.id + "_report_" + 
            LocalDateTime.now().format(FILE_DATE_FORMATTER) + ".json";
        
        StringBuilder json = new StringBuilder();
        json.append("{\n");
        json.append("  \"reportMetadata\": {\n");
        json.append("    \"generatedAt\": \"").append(LocalDateTime.now().format(DATE_FORMATTER)).append("\",\n");
        json.append("    \"reportType\": \"comprehensive\",\n");
        json.append("    \"version\": \"1.0\"\n");
        json.append("  },\n");
        json.append("  \"project\": {\n");
        json.append("    \"id\": ").append(data.id).append(",\n");
        json.append("    \"name\": \"").append(escapeJson(data.name)).append("\",\n");
        json.append("    \"description\": \"").append(escapeJson(data.description)).append("\",\n");
        json.append("    \"status\": \"").append(escapeJson(data.status)).append("\",\n");
        json.append("    \"clientName\": \"").append(escapeJson(data.clientName)).append("\",\n");
        json.append("    \"clientEmail\": \"").append(escapeJson(data.clientEmail)).append("\",\n");
        json.append("    \"managerName\": \"").append(escapeJson(data.createdByName)).append("\",\n");
        json.append("    \"completionPercentage\": ").append(data.completionPercentage).append(",\n");
        json.append("    \"budget\": ").append(data.budget).append(",\n");
        json.append("    \"actualCost\": ").append(data.actualCost).append(",\n");
        json.append("    \"location\": \"").append(escapeJson(data.location)).append("\"\n");
        json.append("  },\n");
        json.append("  \"metrics\": {\n");
        json.append("    \"totalTasks\": ").append(data.totalTasks).append(",\n");
        json.append("    \"completedTasks\": ").append(data.completedTasks).append(",\n");
        json.append("    \"inProgressTasks\": ").append(data.inProgressTasks).append(",\n");
        json.append("    \"pendingTasks\": ").append(data.pendingTasks).append(",\n");
        json.append("    \"blockedTasks\": ").append(data.blockedTasks).append(",\n");
        json.append("    \"averageTaskProgress\": ").append(String.format("%.2f", data.averageTaskProgress)).append(",\n");
        json.append("    \"budgetUtilization\": ").append(String.format("%.2f", data.getBudgetUtilizationPercentage())).append(",\n");
        json.append("    \"daysElapsed\": ").append(data.daysElapsed).append(",\n");
        json.append("    \"daysRemaining\": ").append(data.daysRemaining).append("\n");
        json.append("  },\n");
        json.append("  \"tasks\": [\n");
        
        for (int i = 0; i < data.tasks.size(); i++) {
            TaskData task = data.tasks.get(i);
            json.append("    {\n");
            json.append("      \"id\": ").append(task.id).append(",\n");
            json.append("      \"title\": \"").append(escapeJson(task.title)).append("\",\n");
            json.append("      \"description\": \"").append(escapeJson(task.description)).append("\",\n");
            json.append("      \"status\": \"").append(escapeJson(task.status)).append("\",\n");
            json.append("      \"progress\": ").append(task.progress).append(",\n");
            json.append("      \"priority\": \"").append(escapeJson(task.priority)).append("\",\n");
            json.append("      \"assignedTo\": \"").append(escapeJson(task.assignedTo)).append("\"\n");
            json.append("    }").append(i < data.tasks.size() - 1 ? "," : "").append("\n");
        }
        
        json.append("  ],\n");
        json.append("  \"insights\": \"").append(escapeJson(data.getInsights())).append("\"\n");
        json.append("}\n");
        
        try (FileWriter writer = new FileWriter(fileName)) {
            writer.write(json.toString());
        }
        
        return fileName;
    }
    
    /**
     * Generate plain text report
     */
    private String generateTextReport(ProjectData data, String outputPath) throws IOException {
        String fileName = outputPath != null ? outputPath : 
            REPORTS_DIR + "project_" + data.id + "_report_" + 
            LocalDateTime.now().format(FILE_DATE_FORMATTER) + ".txt";
        
        StringBuilder text = new StringBuilder();
        text.append("================================================================================\n");
        text.append("                      BUILDWATCH PROJECT REPORT\n");
        text.append("================================================================================\n");
        text.append("Generated: ").append(LocalDateTime.now().format(DATE_FORMATTER)).append("\n\n");
        
        text.append("PROJECT: ").append(data.name).append("\n");
        text.append("================================================================================\n\n");
        
        text.append("PROJECT OVERVIEW\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getOverviewText()).append("\n\n");
        
        text.append("KEY METRICS\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getMetricsText()).append("\n\n");
        
        text.append("TIMELINE ANALYSIS\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getTimelineAnalysis()).append("\n\n");
        
        text.append("BUDGET ANALYSIS\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getBudgetAnalysis()).append("\n\n");
        
        text.append("TASK SUMMARY\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getTaskSummary()).append("\n\n");
        
        text.append("DETAILED TASK LIST\n");
        text.append("--------------------------------------------------------------------------------\n");
        if (data.tasks.isEmpty()) {
            text.append("No tasks have been created for this project yet.\n");
        } else {
            for (TaskData task : data.tasks) {
                text.append(String.format("• %s [%s] - %d%% - %s\n", 
                    task.title, task.status, task.progress, 
                    task.assignedTo != null ? task.assignedTo : "Unassigned"));
            }
        }
        text.append("\n");
        
        text.append("INSIGHTS & RECOMMENDATIONS\n");
        text.append("--------------------------------------------------------------------------------\n");
        text.append(data.getInsights()).append("\n\n");
        
        text.append("================================================================================\n");
        text.append("Report generated by BuildWatch Report Generator\n");
        text.append("================================================================================\n");
        
        try (FileWriter writer = new FileWriter(fileName)) {
            writer.write(text.toString());
        }
        
        return fileName;
    }
    
    // Utility methods
    private String escapeHtml(String text) {
        if (text == null) return "";
        return text.replace("&", "&amp;")
                   .replace("<", "&lt;")
                   .replace(">", "&gt;")
                   .replace("\"", "&quot;")
                   .replace("'", "&#39;");
    }
    
    private String escapeJson(String text) {
        if (text == null) return "";
        return text.replace("\\", "\\\\")
                   .replace("\"", "\\\"")
                   .replace("\n", "\\n")
                   .replace("\r", "\\r")
                   .replace("\t", "\\t");
    }
}

/**
 * Data class to hold project information
 */
class ProjectData {
    int id;
    String name;
    String description;
    String status;
    String createdByName;
    int clientId;
    
    String priority;
    String category;
    String timeline;
    int completionPercentage;
    
    java.sql.Date startDate;
    java.sql.Date endDate;
    Timestamp createdAt;
    Timestamp lastActivityAt;
    
    double budget;
    double totalHoursSpent;
    double estimatedHours;
    
    // Legacy fields for compatibility
    String clientName = "N/A";
    String clientEmail = "N/A";
    double actualCost = 0.0;
    String location = "N/A";
    
    List<TaskData> tasks = new ArrayList<>();
    List<TeamMember> teamMembers = new ArrayList<>();
    
    // Calculated metrics
    int totalTasks;
    int completedTasks;
    int inProgressTasks;
    int pendingTasks;
    int blockedTasks;
    double averageTaskProgress;
    long daysElapsed;
    long daysRemaining;
    
    void calculateMetrics() {
        totalTasks = tasks.size();
        completedTasks = 0;
        inProgressTasks = 0;
        pendingTasks = 0;
        blockedTasks = 0;
        int totalProgress = 0;
        
        for (TaskData task : tasks) {
            totalProgress += task.progress;
            
            if (task.status != null) {
                switch (task.status.toLowerCase()) {
                    case "completed":
                        completedTasks++;
                        break;
                    case "in progress":
                        inProgressTasks++;
                        break;
                    case "pending":
                        pendingTasks++;
                        break;
                    case "blocked":
                        blockedTasks++;
                        break;
                }
            }
        }
        
        averageTaskProgress = totalTasks > 0 ? (double) totalProgress / totalTasks : 0;
        
        if (startDate != null) {
            daysElapsed = ChronoUnit.DAYS.between(
                startDate.toLocalDate(),
                LocalDateTime.now().toLocalDate()
            );
        }
        
        if (endDate != null) {
            daysRemaining = ChronoUnit.DAYS.between(
                LocalDateTime.now().toLocalDate(),
                endDate.toLocalDate()
            );
        }
    }
    
    String getOverviewText() {
        return String.format(
            "Project Name: %s\n" +
            "Project Manager: %s\n" +
            "Status: %s\n" +
            "Priority: %s\n" +
            "Category: %s\n" +
            "Description: %s",
            name, createdByName, status, priority, 
            category != null ? category : "N/A", description
        );
    }
    
    String getMetricsText() {
        return String.format(
            "Overall Completion: %d%%\n" +
            "Total Tasks: %d\n" +
            "Completed Tasks: %d\n" +
            "In Progress: %d\n" +
            "Pending: %d\n" +
            "Average Task Progress: %.1f%%\n" +
            "Total Hours Spent: %.1f\n" +
            "Estimated Hours: %.1f\n" +
            "Team Members: %d",
            completionPercentage, totalTasks, completedTasks, 
            inProgressTasks, pendingTasks, averageTaskProgress,
            totalHoursSpent, estimatedHours, teamMembers.size()
        );
    }
    
    String getTimelineAnalysis() {
        if (startDate == null && endDate == null) {
            return "Timeline information not available for this project.";
        }
        
        StringBuilder analysis = new StringBuilder();
        
        if (startDate != null) {
            analysis.append(String.format("Project started: %s\n", 
                startDate.toLocalDate().format(DateTimeFormatter.ofPattern("MMM dd, yyyy"))));
            analysis.append(String.format("Days elapsed: %d days\n", daysElapsed));
        }
        
        if (endDate != null) {
            analysis.append(String.format("Expected completion: %s\n", 
                endDate.toLocalDate().format(DateTimeFormatter.ofPattern("MMM dd, yyyy"))));
            analysis.append(String.format("Days remaining: %d days\n", daysRemaining));
            
            if (daysRemaining < 0) {
                analysis.append("⚠ WARNING: Project is overdue!\n");
            } else if (daysRemaining < 7) {
                analysis.append("⚠ ALERT: Project deadline is approaching soon!\n");
            }
        }
        
        if (timeline != null) {
            analysis.append(String.format("Timeline: %s\n", timeline));
        }
        
        return analysis.toString();
    }
    
    String getBudgetAnalysis() {
        StringBuilder analysis = new StringBuilder();
        analysis.append(String.format("Total Budget: $%.2f\n", budget));
        analysis.append(String.format("Total Hours Spent: %.1f hours\n", totalHoursSpent));
        analysis.append(String.format("Estimated Hours: %.1f hours\n", estimatedHours));
        
        if (estimatedHours > 0) {
            double hoursUtilization = (totalHoursSpent / estimatedHours) * 100;
            analysis.append(String.format("Hours Utilization: %.1f%%\n", hoursUtilization));
            
            if (hoursUtilization > 100) {
                analysis.append("⚠ WARNING: Project has exceeded estimated hours!\n");
            } else if (hoursUtilization > 90) {
                analysis.append("⚠ ALERT: Hours utilization is high. Monitor time carefully.\n");
            }
        }
        
        if (budget > 0 && completionPercentage > 0) {
            double expectedSpend = (budget * completionPercentage) / 100;
            analysis.append(String.format("\nExpected spend at %d%% completion: $%.2f\n", 
                completionPercentage, expectedSpend));
        }
        
        return analysis.toString();
    }
    
    String getTaskSummary() {
        if (totalTasks == 0) {
            return "No tasks have been created for this project yet.";
        }
        
        return String.format(
            "Task Distribution:\n" +
            "• Completed: %d (%.1f%%)\n" +
            "• In Progress: %d (%.1f%%)\n" +
            "• Pending: %d (%.1f%%)\n\n" +
            "The project has %d total tasks with an average progress of %.1f%%.",
            completedTasks, (completedTasks * 100.0 / totalTasks),
            inProgressTasks, (inProgressTasks * 100.0 / totalTasks),
            pendingTasks, (pendingTasks * 100.0 / totalTasks),
            totalTasks, averageTaskProgress
        );
    }
    
    String getInsights() {
        StringBuilder insights = new StringBuilder();
        
        // Project health assessment
        if (completionPercentage >= 75) {
            insights.append("✓ Project is in good shape with strong completion progress.\n\n");
        } else if (completionPercentage >= 50) {
            insights.append("→ Project is progressing steadily. Continue monitoring key milestones.\n\n");
        } else if (completionPercentage >= 25) {
            insights.append("⚠ Project is in early-mid stages. Ensure resources are allocated properly.\n\n");
        } else {
            insights.append("→ Project is in early stages. Focus on establishing momentum.\n\n");
        }
        
        // Priority-based insights
        if ("high".equalsIgnoreCase(priority)) {
            insights.append("⚠ HIGH PRIORITY PROJECT: Ensure adequate resources and attention.\n\n");
        }
        
        // Task-based insights
        if (blockedTasks > 0) {
            insights.append(String.format("⚠ ATTENTION: %d task(s) are blocked. " +
                "Immediate action required to unblock and maintain project velocity.\n\n", blockedTasks));
        }
        
        if (pendingTasks > totalTasks * 0.5) {
            insights.append("⚠ High number of pending tasks. Consider prioritizing and assigning resources.\n\n");
        }
        
        // Timeline insights
        if (daysRemaining > 0 && daysRemaining < 14) {
            insights.append("⚠ Project deadline is approaching within 2 weeks. " +
                "Prioritize critical path tasks.\n\n");
        }
        
        // Hours insights
        if (estimatedHours > 0 && totalHoursSpent > estimatedHours) {
            insights.append("⚠ Project has exceeded estimated hours. Review scope and timeline.\n\n");
        }
        
        // Team insights
        if (teamMembers.size() < 2 && totalTasks > 10) {
            insights.append("⚠ Consider adding more team members given the number of tasks.\n\n");
        }
        
        // Recommendations
        insights.append("RECOMMENDATIONS:\n");
        
        if (completedTasks < totalTasks * 0.3 && daysElapsed > 30) {
            insights.append("• Accelerate task completion rate to meet project timeline.\n");
        }
        
        if (blockedTasks > 0) {
            insights.append("• Resolve blocked tasks as highest priority.\n");
        }
        
        if (pendingTasks > 5) {
            insights.append("• Assign pending tasks to team members to maintain momentum.\n");
        }
        
        insights.append("• Maintain regular communication with stakeholders.\n");
        insights.append("• Continue monitoring progress and adjust plans as needed.\n");
        
        return insights.toString();
    }
    
    String getBudgetStatus() {
        if (budget == 0) return "N/A";
        if (estimatedHours > 0) {
            double hoursUtilization = (totalHoursSpent / estimatedHours) * 100;
            return String.format("%.1f%%", hoursUtilization);
        }
        return "N/A";
    }
    
    double getBudgetUtilizationPercentage() {
        if (estimatedHours == 0) return 0;
        return (totalHoursSpent / estimatedHours) * 100;
    }
}

/**
 * Data class to hold task information
 */
class TaskData {
    int id;
    String title;
    String description;
    String status;
    int progress;
    String priority;
    String assignedTo;
    Timestamp dueDate;
    Timestamp completedAt;
}

/**
 * Data class to hold team member information
 */
class TeamMember {
    int id;
    String name;
    String email;
    String role;
    Timestamp assignedAt;
}
