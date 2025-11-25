<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: students.php');
    exit;
}

$error = '';
$success = '';

// Get current student data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'student'");
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($name && $student_id && $email) {
        // Build UPDATE query
        if ($password !== '') {
            // Update password as well
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "
                UPDATE users
                SET name = :name,
                    student_id = :student_id,
                    email = :email,
                    password_hash = :password_hash
                WHERE id = :id AND role = 'student'
            ";
            $params = [
                ':name'         => $name,
                ':student_id'   => $student_id,
                ':email'        => $email,
                ':password_hash'=> $password_hash,
                ':id'           => $id
            ];
        } else {
            // Keep old password
            $sql = "
                UPDATE users
                SET name = :name,
                    student_id = :student_id,
                    email = :email
                WHERE id = :id AND role = 'student'
            ";
            $params = [
                ':name'       => $name,
                ':student_id' => $student_id,
                ':email'      => $email,
                ':id'         => $id
            ];
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success = "Student updated successfully.";
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'student'");
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating student: " . $e->getMessage();
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Edit Student</h2>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<form method="post">
    <label>Name:<br>
        <input type="text" name="name"
               value="<?= htmlspecialchars($student['name']) ?>" required>
    </label><br><br>

    <label>Student ID:<br>
        <input type="text" name="student_id"
               value="<?= htmlspecialchars($student['student_id']) ?>" required>
    </label><br><br>

    <label>Email:<br>
        <input type="email" name="email"
               value="<?= htmlspecialchars($student['email']) ?>" required>
    </label><br><br>

    <label>New Password (optional):<br>
        <input type="text" name="password"
               placeholder="Leave empty to keep current password">
    </label><br><br>

    <button type="submit">Save Changes</button>
    <a href="students.php">Back to Students</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
