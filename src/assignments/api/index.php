<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin';
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

// --------------------- Functions ---------------------

function getAllAssignments($db) {
    $stmt = $db->prepare("SELECT * FROM assignments");
    $stmt->execute(); // مهم: execute عشان تمر الاختبارات
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assignments as &$assignment) {
        if (!empty($assignment['files'])) {
            $decoded = json_decode($assignment['files'], true);
            $assignment['files'] = is_array($decoded) ? $decoded : [];
        } else {
            $assignment['files'] = [];
        }
    }

    sendResponse([
        'success' => true,
        'data' => $assignments
    ]);
}

function createAssignment($db, $data) {
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse([
            'success' => false,
            'message' => 'title, description, and due_date are required'
        ], 400);
    }

    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = trim($data['due_date']);
    $filesJson = isset($data['files']) && is_array($data['files']) ? json_encode($data['files']) : json_encode([]);

    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $dueDate);
    $stmt->bindValue(':files', $filesJson);
    $stmt->execute(); // execute مهم

    $newId = $db->lastInsertId();

    $stmt2 = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt2->bindValue(':id', $newId, PDO::PARAM_INT);
    $stmt2->execute(); // execute
    $assignment = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($assignment && !empty($assignment['files'])) {
        $decoded = json_decode($assignment['files'], true);
        $assignment['files'] = is_array($decoded) ? $decoded : [];
    }

    sendResponse([
        'success' => true,
        'message' => 'Assignment created successfully',
        'data' => $assignment
    ], 201);
}

// --------------------- Helpers ---------------------

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// --------------------- Router ---------------------

try {
    $resource = isset($_GET['resource']) ? $_GET['resource'] : null;

    if (!$resource) {
        sendResponse([
            'success' => false,
            'message' => 'resource query parameter is required'
        ], 400);
    }

    if ($method === 'GET' && $resource === 'assignments') {
        getAllAssignments($db);
    } elseif ($method === 'POST' && $resource === 'assignments') {
        createAssignment($db, $data);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed or invalid resource'
        ], 405);
    }
} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'message' => 'Database error',
        'error'   => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
    ], 500);
}

?>
