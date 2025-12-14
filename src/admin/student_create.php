<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/auth.php";
checkAdmin();
require_once "includes/db.php";
require "includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $sid  = $_POST['student_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO students (name, student_id, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $sid, $email, $password]);

    header("Location: students.php");
    exit;
}
?>

<h2>Add New Student</h2>

<form method="POST">
    <label>Name</label>
    <input type="text" name="name" required>

    <label>Student ID</label>
    <input type="text" name="student_id" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Create</button>
</form>

<?php require "footer.php"; ?>
