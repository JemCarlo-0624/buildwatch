<?php
session_start();
require_once("../config/db.php");

header('Content-Type: application/json');

error_log("[v0] Report generation request received");
error_log("[v0] Session data: " . print_r($_SESSION, true));
error_log("[v0] GET parameters: " . print_r($_GET, true));

// Check authentication
if (!isset($_SESSION['client_id'])) {
    error_log("[v0] Authentication failed - no client_id in session");
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get parameters
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

error_log("[v0] Project ID: $projectId, Format: $format");

// Validate format
$validFormats = ['html', 'json', 'txt', 'pdf'];
if (!in_array($format, $validFormats)) {
    error_log("[v0] Invalid format: $format");
    echo json_encode(['success' => false, 'error' => 'Invalid format']);
    exit;
}

if ($projectId <= 0) {
    error_log("[v0] Invalid project ID: $projectId");
    echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
    exit;
}

// Verify project belongs to client
$stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ? AND client_id = ?");
$stmt->execute([$projectId, $_SESSION['client_id']]);
$project = $stmt->fetch();

if (!$project) {
    error_log("[v0] Project not found or access denied for project $projectId and client " . $_SESSION['client_id']);
    echo json_encode(['success' => false, 'error' => 'Project not found or access denied']);
    exit;
}

error_log("[v0] Project verified successfully");

$javaDir = dirname(__DIR__) . '/java';
$reportsDir = dirname(__DIR__) . '/reports';

error_log("[v0] Java directory: $javaDir");
error_log("[v0] Reports directory: $reportsDir");

// Create reports directory if it doesn't exist
if (!file_exists($reportsDir)) {
    mkdir($reportsDir, 0755, true);
    error_log("[v0] Created reports directory");
}

// Build classpath: java directory (for .class files) + lib directory (for JARs)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows uses semicolon as path separator
    $classpath = $javaDir . ';' . $javaDir . '\\lib\\*';
} else {
    // Unix/Linux uses colon as path separator
    $classpath = $javaDir . ':' . $javaDir . '/lib/*';
}

error_log("[v0] Classpath: $classpath");

$originalDir = getcwd();
chdir($javaDir);
error_log("[v0] Changed working directory to: " . getcwd());

// Build Java command with database configuration
$javaCommand = sprintf(
    'java -cp "%s" -Ddb.host=%s -Ddb.port=%s -Ddb.name=%s -Ddb.user=%s -Ddb.password=%s ReportGenerator %d %s "%s" 2>&1',
    $classpath,
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_USER,
    DB_PASSWORD,
    $projectId,
    $format,
    $reportsDir
);

$sanitizedCommand = str_replace(DB_PASSWORD, '****', $javaCommand);
error_log("[v0] Executing Java command: $sanitizedCommand");

// Execute the Java command
$output = [];
$returnCode = 0;
exec($javaCommand, $output, $returnCode);

chdir($originalDir);

// Log the execution for debugging
error_log("[v0] Java execution completed");
error_log("[v0] Return Code: " . $returnCode);
error_log("[v0] Output: " . implode("\n", $output));

if ($returnCode !== 0) {
    error_log("[v0] Java execution failed with return code: $returnCode");
    echo json_encode([
        'success' => false,
        'error' => 'Report generation failed',
        'details' => implode("\n", $output),
        'returnCode' => $returnCode
    ]);
    exit;
}

// Find the generated report file
$reportPattern = $reportsDir . DIRECTORY_SEPARATOR . "project_{$projectId}_report_*." . $format;
error_log("[v0] Looking for report files matching: $reportPattern");

$reportFiles = glob($reportPattern);
error_log("[v0] Found " . count($reportFiles) . " report files");

if (empty($reportFiles)) {
    error_log("[v0] No report files found after generation");
    echo json_encode([
        'success' => false,
        'error' => 'Report file not found after generation',
        'output' => implode("\n", $output),
        'pattern' => $reportPattern
    ]);
    exit;
}

// Get the most recent report file
usort($reportFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$reportFile = $reportFiles[0];
$fileName = basename($reportFile);

error_log("[v0] Report generated successfully: $fileName");

// Return success with report URL
echo json_encode([
    'success' => true,
    'reportUrl' => '/buildwatch/reports/' . $fileName,
    'fileName' => $fileName,
    'format' => $format,
    'message' => 'Report generated successfully'
]);
?>
