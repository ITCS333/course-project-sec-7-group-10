<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: students.php');
    exit;
}

// Delete only if user is a student
$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'student'");
$stmt->execute([':id' => $id]);

header('Location: students.php');
exit;
