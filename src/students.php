<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2>Students</h2>
<a href="student_create.php">+ Add New Student</a>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Student ID</th>
        <th>Email</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($students as $s): ?>
    <tr>
        <td><?= $s['id'] ?></td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= htmlspecialchars($s['student_id']) ?></td>
        <td><?= htmlspecialchars($s['email']) ?></td>
        <td>
            <a href="student_edit.php?id=<?= $s['id'] ?>">Edit</a> |
            <a href="student_delete.php?id=<?= $s['id'] ?>"
               onclick="return confirm('Delete this student?');">
               Delete
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
