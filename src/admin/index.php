<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

// Optional: get number of students
$stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM users WHERE role = 'student'");
$stats = $stmt->fetch();
$total_students = $stats['total_students'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>

<h1>Admin Dashboard</h1>

<p>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>

<section>
    <h2>Quick Actions</h2>
    <ul>
        <li><a href="students.php">Manage Students</a></li>
        <li><a href="student_create.php">Add New Student</a></li>
        <li><a href="change_password.php">Change My Password</a></li>
    </ul>
</section>

<section>
    <h2>Overview</h2>
    <p>Total registered students: <strong><?= $total_students ?></strong></p>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
