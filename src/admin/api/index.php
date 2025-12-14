<?php
session_start(); // ← إضافة بداية الجلسة

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'Database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = [];
}

function getStudents($db) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';

    $allowedSortFields = ['name', 'student_id', 'email'];
    if (!in_array($sort, $allowedSortFields, true)) {
        $sort = 'name';
    }

    $allowedOrders = ['asc', 'desc'];
    if (!in_array($order, $allowedOrders, true)) {
        $order = 'asc';
    }

    $sql = 'SELECT id, student_id, name, email, created_at FROM students';
    $params = [];

    if (!empty($search)) {
        $sql .= ' WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $students
    ]);
}

function getStudentById($db, $studentId) {
    if (empty($studentId)) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required'
        ], 400);
    }

    $stmt = $db->prepare('SELECT id, student_id, name, email, created_at FROM students WHERE student_id = :student_id');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    sendResponse([
        'success' => true,
        'data' => $student
    ]);
}

function createStudent($db, $data) {
    if (empty($data['student_id']) || empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'student_id, name, email, and password are required'
        ], 400);
    }

    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    if (!validateEmail($email)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid email format'
        ], 400);
    }

    $stmt = $db->prepare('SELECT id FROM students WHERE student_id = :student_id OR email = :email');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        sendResponse([
            'success' => false,
            'message' => 'student_id or email already exists'
        ], 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare('INSERT INTO students (student_id, name, email, password, created_at) VALUES (:student_id, :name, :email, :password, NOW())');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $hashedPassword);
    $stmt->execute();

    $id = $db->lastInsertId();

    $stmt2 = $db->prepare('SELECT id, student_id, name, email, created_at FROM students WHERE id = :id');
    $stmt2->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt2->execute();
    $student = $stmt2->fetch(PDO::FETCH_ASSOC);

    // مثال استخدام $_SESSION بعد إنشاء الطالب
    $_SESSION['last_created_student'] = $student;

    sendResponse([
        'success' => true,
        'message' => 'Student created successfully',
        'data' => $student
    ], 201);
}

// باقي الدوال updateStudent, deleteStudent, changePassword بدون تغيير
// لأن المشكلة كانت فقط بعدم وجود session_start()

// ... (يمكنك نسخ باقي الكود كما هو)

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    echo json_encode($data);
    exit;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>
