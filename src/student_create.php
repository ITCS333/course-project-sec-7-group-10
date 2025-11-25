<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? 'student123'; // default

    if ($name && $student_id && $email) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, student_id, email, password_hash, role)
            VALUES (:name, :student_id, :email, :password_hash, 'student')
        ");
        try {
            $stmt->execute([
                ':name' => $name,
                ':student_id' => $student_id,
                ':email' => $email,
                ':password_hash' => $password_hash
            ]);
            header('Location: students.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h2>Add New Student</h2>

<?php if ($error): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>Name:
        <input type="text" name="name" required>
    </label><br><br>
    <label>Student ID:
        <input type="text" name="student_id" required>
    </label><br><br>
    <label>Email:
        <input type="email" name="email" required>
    </label><br><br>
    <label>Initial Password:
        <input type="text" name="password" placeholder="student123">
    </label><br><br>
    <button type="submit">Create</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
