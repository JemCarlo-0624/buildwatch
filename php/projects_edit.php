<?php
require_once("../config/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array($_SESSION['role'], ['pm','admin'])) { header("Location: login.php"); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { die("Invalid project ID"); }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id=?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE projects SET name=?, description=?, status=? WHERE id=?");
    $stmt->execute([$name, $desc, $status, $id]);

    header("Location: projects_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit Project</title></head>
<body>
    <h2>Edit Project</h2>
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"><?= htmlspecialchars($project['description']) ?></textarea><br><br>

        <label>Status:</label><br>
        <select name="status">
            <option value="ongoing" <?= $project['status']=='ongoing'?'selected':'' ?>>Ongoing</option>
            <option value="completed" <?= $project['status']=='completed'?'selected':'' ?>>Completed</option>
            <option value="on-hold" <?= $project['status']=='on-hold'?'selected':'' ?>>On Hold</option>
        </select><br><br>

        <button type="submit">Update</button>
    </form>
    <p><a href="projects_list.php">⬅ Back</a></p>
</body>
</html>
