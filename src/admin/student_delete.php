<?php
require_once "includes/auth.php";
checkAdmin();
require_once "includes/db.php";

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM students WHERE id=?");
$stmt->execute([$id]);

header("Location: students.php");
exit;
