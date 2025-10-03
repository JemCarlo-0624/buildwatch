<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['pm','admin'])) {
    header("Location: login.php");
    exit;
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) die("❌ Project ID missing");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $stmt = $pdo->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
    $stmt->execute([$project_id, $user_id]);
}

// Get all workers
$workers = $pdo->query("SELECT * FROM users WHERE role='worker'")->fetchAll();

// Get assigned users
$stmt = $pdo->prepare("SELECT u.* FROM project_assignments pa JOIN users u ON pa.user_id=u.id WHERE pa.project_id=?");
$stmt->execute([$project_id]);
$assigned = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Assign Users</title></head>
<body>
    <h2>Assign Workers to Project</h2>

    <form method="post">
        <label>Choose Worker:
            <select name="user_id" required>
                <?php foreach ($workers as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">➕ Assign</button>
    </form>

    <h3>Assigned Workers</h3>
    <ul>
        <?php foreach ($assigned as $a): ?>
            <li><?= htmlspecialchars($a['name']) ?> (<?= $a['email'] ?>)</li>
        <?php endforeach; ?>
    </ul>

    <p><a href="projects_list.php">⬅ Back</a></p>
</body>
</html>
