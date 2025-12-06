<?php

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

    sendResponse([
        'success' => true,
        'message' => 'Student created successfully',
        'data' => $student
    ], 201);
}

function updateStudent($db, $data) {
    if (empty($data['student_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required'
        ], 400);
    }

    $studentId = sanitizeInput($data['student_id']);

    $stmt = $db->prepare('SELECT id, student_id, name, email FROM students WHERE student_id = :student_id');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $fields = [];
    $params = [':id' => $student['id']];

    if (isset($data['name'])) {
        $fields[] = 'name = :name';
        $params[':name'] = sanitizeInput($data['name']);
    }

    if (isset($data['email'])) {
        $newEmail = sanitizeInput($data['email']);
        if (!validateEmail($newEmail)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid email format'
            ], 400);
        }

        $check = $db->prepare('SELECT id FROM students WHERE email = :email AND id != :id');
        $check->bindValue(':email', $newEmail);
        $check->bindValue(':id', $student['id'], PDO::PARAM_INT);
        $check->execute();
        $duplicate = $check->fetch(PDO::FETCH_ASSOC);

        if ($duplicate) {
            sendResponse([
                'success' => false,
                'message' => 'Email already in use'
            ], 409);
        }

        $fields[] = 'email = :email';
        $params[':email'] = $newEmail;
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided to update'
        ], 400);
    }

    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Student updated successfully'
        ]);
    } else {
        sendResponse([
            'success' => true,
            'message' => 'No changes made to the student'
        ]);
    }
}

function deleteStudent($db, $studentId) {
    if (empty($studentId)) {
        sendResponse([
            'success' => false,
            'message' => 'student_id is required'
        ], 400);
    }

    $stmt = $db->prepare('SELECT id FROM students WHERE student_id = :student_id');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $stmt = $db->prepare('DELETE FROM students WHERE student_id = :student_id');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete student'
        ], 500);
    }
}

function changePassword($db, $data) {
    if (empty($data['student_id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse([
            'success' => false,
            'message' => 'student_id, current_password, and new_password are required'
        ], 400);
    }

    $studentId = sanitizeInput($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];

    if (strlen($newPassword) < 8) {
        sendResponse([
            'success' => false,
            'message' => 'new_password must be at least 8 characters long'
        ], 400);
    }

    $stmt = $db->prepare('SELECT id, password FROM students WHERE student_id = :student_id');
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    if (!password_verify($currentPassword, $student['password'])) {
        sendResponse([
            'success' => false,
            'message' => 'Current password is incorrect'
        ], 401);
    }

    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $db->prepare('UPDATE students SET password = :password WHERE id = :id');
    $stmt->bindValue(':password', $hashedNewPassword);
    $stmt->bindValue(':id', $student['id'], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to change password'
        ], 500);
    }
}

try {
    $action = isset($_GET['action']) ? $_GET['action'] : null;

    if ($method === 'GET') {
        if (isset($_GET['student_id'])) {
            getStudentById($db, $_GET['student_id']);
        } else {
            getStudents($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createStudent($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateStudent($db, $data);
    } elseif ($method === 'DELETE') {
        $studentId = isset($_GET['student_id']) ? $_GET['student_id'] : (isset($data['student_id']) ? $data['student_id'] : null);
        deleteStudent($db, $studentId);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }
} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ], 500);
}

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
