<?php
/**
 * Test script to verify Java execution and database connectivity
 * Access this file directly to diagnose issues: http://yoursite.com/php/test_java.php
 */

header('Content-Type: text/plain');

echo "BuildWatch Java Report Generator - Diagnostic Test\n";
echo "==================================================\n\n";

// Test 1: Check Java installation
echo "Test 1: Java Installation\n";
echo "--------------------------\n";
exec("java -version 2>&1", $javaVersion, $javaReturnCode);
if ($javaReturnCode === 0) {
    echo "✓ Java is installed:\n";
    echo implode("\n", $javaVersion) . "\n\n";
} else {
    echo "✗ Java is NOT installed or not in PATH\n";
    echo "Please install Java JDK and ensure it's in your system PATH\n\n";
    exit(1);
}

// Test 2: Check javac (compiler)
echo "Test 2: Java Compiler\n";
echo "---------------------\n";
exec("javac -version 2>&1", $javacVersion, $javacReturnCode);
if ($javacReturnCode === 0) {
    echo "✓ Java compiler is available:\n";
    echo implode("\n", $javacVersion) . "\n\n";
} else {
    echo "✗ Java compiler (javac) is NOT available\n";
    echo "Please install Java JDK (not just JRE)\n\n";
}

// Test 3: Check file paths
echo "Test 3: File Paths\n";
echo "------------------\n";
$baseDir = dirname(__DIR__);
$javaDir = $baseDir . '/java';
$libDir = $javaDir . '/lib';
$reportsDir = $baseDir . '/reports';

echo "Base Directory: " . $baseDir . "\n";
echo "Java Directory: " . $javaDir . " - " . (file_exists($javaDir) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "Lib Directory: " . $libDir . " - " . (file_exists($libDir) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "Reports Directory: " . $reportsDir . " - " . (file_exists($reportsDir) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n\n";

// Test 4: Check MySQL JDBC driver
echo "Test 4: MySQL JDBC Driver\n";
echo "-------------------------\n";
$jdbcPathOld = $libDir . '/mysql-connector-java.jar';
$jdbcPathNew = $libDir . '/mysql-connector-j.jar';
$jdbcPath = null;

if (file_exists($jdbcPathNew)) {
    $jdbcPath = $jdbcPathNew;
    echo "✓ MySQL JDBC driver found at: " . $jdbcPath . "\n";
    echo "File size: " . filesize($jdbcPath) . " bytes\n\n";
} elseif (file_exists($jdbcPathOld)) {
    $jdbcPath = $jdbcPathOld;
    echo "✓ MySQL JDBC driver found at: " . $jdbcPath . " (old version)\n";
    echo "File size: " . filesize($jdbcPath) . " bytes\n\n";
} else {
    echo "✗ MySQL JDBC driver NOT found\n";
    echo "Expected location: " . $jdbcPathNew . "\n";
    echo "Please run: cd java && compile.bat (Windows) or bash compile.sh (Linux/Mac)\n\n";
}

// Test 5: Check Java source and class files
echo "Test 5: Java Files\n";
echo "------------------\n";
$javaFile = $javaDir . '/ReportGenerator.java';
$classFile = $javaDir . '/ReportGenerator.class';

echo "Source file: " . $javaFile . " - " . (file_exists($javaFile) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
echo "Class file: " . $classFile . " - " . (file_exists($classFile) ? "✓ EXISTS" : "✗ NOT FOUND") . "\n\n";

// Test 6: Try compilation
if (file_exists($javaFile) && file_exists($jdbcPath)) {
    echo "Test 6: Compilation Test\n";
    echo "------------------------\n";
    
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $separator = $isWindows ? ';' : ':';
    $classpath = $javaDir . $separator . $libDir . '/*';
    
    $compileCommand = sprintf(
        "javac -cp %s %s 2>&1",
        escapeshellarg($classpath),
        escapeshellarg($javaFile)
    );
    
    echo "Compile command: " . $compileCommand . "\n";
    exec($compileCommand, $compileOutput, $compileReturnCode);
    
    if ($compileReturnCode === 0) {
        echo "✓ Compilation successful\n\n";
    } else {
        echo "✗ Compilation failed:\n";
        echo implode("\n", $compileOutput) . "\n\n";
    }
}

// Test 7: Database configuration
echo "Test 7: Database Configuration\n";
echo "------------------------------\n";
$configPath = dirname(__DIR__) . '/config/db.php';
if (file_exists($configPath)) {
    require_once($configPath);
    echo "✓ Config file loaded from: " . $configPath . "\n";
} else {
    echo "✗ Config file not found at: " . $configPath . "\n";
}

echo "Database Host: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "Database Port: " . (defined('DB_PORT') ? DB_PORT : '3306 (default)') . "\n";
echo "Database Name: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
echo "Database User: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
echo "Database Password: " . (defined('DB_PASS') ? (DB_PASS ? '***SET***' : 'EMPTY') : 'NOT DEFINED') . "\n\n";

// Test 8: Database connection
echo "Test 8: Database Connection\n";
echo "---------------------------\n";
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
    try {
        $testPdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        echo "✓ PHP can connect to database successfully\n\n";
    } catch (PDOException $e) {
        echo "✗ PHP cannot connect to database\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ Database constants not defined, skipping connection test\n\n";
}

// Test 9: Test Java execution with database connection
if (file_exists($classFile) && file_exists($jdbcPath)) {
    echo "Test 9: Java Execution Test\n";
    echo "---------------------------\n";
    
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $separator = $isWindows ? ';' : ':';
    $classpath = $javaDir . $separator . $libDir . '/*';
    
    // Try to run with a test project ID (1)
    $testCommand = sprintf(
        "java -cp %s -Ddb.host=%s -Ddb.port=%s -Ddb.name=%s -Ddb.user=%s -Ddb.pass=%s ReportGenerator 1 json %s 2>&1",
        escapeshellarg($classpath),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_PORT ?? '3306'),
        escapeshellarg(DB_NAME),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg($reportsDir)
    );
    
    echo "Test command (with sensitive data hidden):\n";
    echo "java -cp [CLASSPATH] -Ddb.host=" . DB_HOST . " -Ddb.name=" . DB_NAME . " ReportGenerator 1 json " . $reportsDir . "\n\n";
    
    exec($testCommand, $testOutput, $testReturnCode);
    
    echo "Output:\n";
    echo implode("\n", $testOutput) . "\n\n";
    
    if ($testReturnCode === 0) {
        echo "✓ Java execution successful\n";
    } else {
        echo "✗ Java execution failed with return code: " . $testReturnCode . "\n";
    }
}

echo "\n==================================================\n";
echo "Diagnostic test complete\n";
?>
