<?php
require_once "includes/auth.php";
checkAdmin();
require_once "includes/db.php";
require "includes/header.php";

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $sid = $_POST['student_id'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("UPDATE students SET name=?, student_id=?, email=? WHERE id=?");
    $stmt->execute([$name, $sid, $email, $id]);

    header("Location: students.php");
    exit;
}
?>

<h2>Edit Student</h2>

<form method="POST">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>

    <label>Student ID</label>
    <input type="text" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>

    <button type="submit">Save</button>
</form>

<?php require "footer.php"; ?>
