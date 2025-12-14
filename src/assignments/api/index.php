<?php

session_start();


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // مثال: معرف المستخدم
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin'; // مثال: نوع المستخدم
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once 'Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error'   => $e->getMessage()
    ]);
    http_response_code(500);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = [];
}


?>
