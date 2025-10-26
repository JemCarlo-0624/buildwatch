<?php
session_start();

ob_start(); // ✅ capture any accidental output

require_once(__DIR__ . '/../../config/db.php');
require_once("report_generator.php");

header('Content-Type: application/json; charset=utf-8');

// ✅ Disable all visible errors and logs to browser
ini_set('display_errors', 0);
error_reporting(0);

try {
    error_log("[v1] Report generation request received");
    error_log("[v1] Session data: " . print_r($_SESSION, true));
    error_log("[v1] GET parameters: " . print_r($_GET, true));

    // --- Authentication ---
    if (!isset($_SESSION['client_id'])) {
        throw new Exception("Not authenticated");
    }

    // --- Input validation ---
    $projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    $format = $_GET['format'] ?? 'html';
    $validFormats = ['html', 'json', 'txt', 'pdf'];

    if (!in_array($format, $validFormats)) {
        throw new Exception("Invalid format: $format");
    }

    if ($projectId <= 0) {
        throw new Exception("Invalid project ID: $projectId");
    }

    // --- Verify ownership ---
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND client_id = ?");
    $stmt->execute([$projectId, $_SESSION['client_id']]);
    $project = $stmt->fetch();

    if (!$project) {
        throw new Exception("Project not found or access denied");
    }

    $reportsDir = dirname(__DIR__) . '/reports/';
    if (!file_exists($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }

    // --- Generate report ---
    $generator = new ReportGenerator($pdo, $reportsDir);
    $reportPath = $generator->generateReport($projectId, $format);
    $fileName = basename($reportPath);

    // --- Return success JSON ---
    $output = [
        'success' => true,
        'reportUrl' => '/buildwatch/reports/' . $fileName,
        'fileName' => $fileName,
        'format' => $format,
        'message' => 'Report generated successfully'
    ];
} catch (Exception $e) {
    error_log("[v1] Report generation failed: " . $e->getMessage());
    $output = [
        'success' => false,
        'error' => 'Report generation failed',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
}

// ✅ Clean up and remove any non-JSON output
ob_end_clean();
echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
?>
