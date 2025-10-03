<?php
// --- CONFIGURATION ---
// Set the relative path to your project homepage (adjust as needed)
$homepage = '/buildwatch/php/frontpage.php';

// --- HANDLE QUERY STRINGS ---
// Preserve any query parameters from the original request
$queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';

// --- BUILD FULL URL ---
// Detect protocol (HTTP or HTTPS)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
// Build the full URL for redirection
$host = $_SERVER['HTTP_HOST'];
$destination = $protocol . $host . $homepage . $queryString;

// --- ERROR HANDLING ---
// Check if the destination file exists on the server
$localPath = $_SERVER['DOCUMENT_ROOT'] . $homepage;
if (!file_exists($localPath)) {
    // If not found, show a user-friendly error
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    echo "<p>The homepage could not be found. Please contact the administrator.</p>";
    exit;
}

// --- REDIRECT ---
// Use a 302 Temporary Redirect (change to 301 for permanent)
header("Location: $destination", true, 302);
exit;
?>

<!-- Fallback HTML for non-PHP environments -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BuildWatch - Redirecting...</title>
</head>
<body>
    <h1>Redirecting to BuildWatch homepage...</h1>
    <p>If you are not redirected automatically, <a href="<?php echo htmlspecialchars($homepage . $queryString); ?>">click here</a>.</p>
</body>
</html>
