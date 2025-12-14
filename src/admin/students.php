<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/auth.php";
checkAdmin();
require_once "includes/db.php";
require "includes/header.php";

try {
    $students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $students = [];
}
?>

<h2>Manage Students</h2>

<a href="student_create.php" style="color:#2d455f; text-decoration:none; font-weight:bold;">+ Add New Student</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Student ID</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['id']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['student_id']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td>
                    <a href="student_edit.php?id=<?= htmlspecialchars($s['id']) ?>">Edit</a> |
                    <a href="student_delete.php?id=<?= htmlspecialchars($s['id']) ?>" style="color:red;">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require "footer.php"; ?>
