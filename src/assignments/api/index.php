<?php

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

function getAllAssignments($db) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort   = isset($_GET['sort'])   ? $_GET['sort']         : 'created_at';
    $order  = isset($_GET['order'])  ? strtolower($_GET['order']) : 'asc';

    $allowedSortFields = ['title', 'due_date', 'created_at'];
    if (!validateAllowedValue($sort, $allowedSortFields)) {
        $sort = 'created_at';
    }

    $allowedOrders = ['asc', 'desc'];
    if (!validateAllowedValue($order, $allowedOrders)) {
        $order = 'asc';
    }

    $sql = "SELECT * FROM assignments";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
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
        'data'    => $assignments
    ]);
}

function getAssignmentById($db, $assignmentId) {
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID is required'
        ], 400);
    }

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found'
        ], 404);
    }

    if (!empty($assignment['files'])) {
        $decoded = json_decode($assignment['files'], true);
        $assignment['files'] = is_array($decoded) ? $decoded : [];
    } else {
        $assignment['files'] = [];
    }

    sendResponse([
        'success' => true,
        'data'    => $assignment
    ]);
}

function createAssignment($db, $data) {
    if (
        empty($data['title']) ||
        empty($data['description']) ||
        empty($data['due_date'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'title, description, and due_date are required'
        ], 400);
    }

    $title       = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate     = trim($data['due_date']);

    if (!validateDate($dueDate)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid due_date format. Expected YYYY-MM-DD'
        ], 400);
    }

    $filesJson = json_encode([]);
    if (isset($data['files'])) {
        if (is_array($data['files'])) {
            $filesJson = json_encode($data['files']);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'files must be an array'
            ], 400);
        }
    }

    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':description', $description);
    $stmt->bindValue(':due_date', $dueDate);
    $stmt->bindValue(':files', $filesJson);

    $stmt->execute();

    $newId = $db->lastInsertId();

    $stmt2 = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt2->bindValue(':id', $newId, PDO::PARAM_INT);
    $stmt2->execute();
    $assignment = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($assignment) {
        if (!empty($assignment['files'])) {
            $decoded = json_decode($assignment['files'], true);
            $assignment['files'] = is_array($decoded) ? $decoded : [];
        } else {
            $assignment['files'] = [];
        }
    }

    sendResponse([
        'success' => true,
        'message' => 'Assignment created successfully',
        'data'    => $assignment
    ], 201);
}

function updateAssignment($db, $data) {
    if (empty($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID (id) is required'
        ], 400);
    }

    $assignmentId = (int) $data['id'];

    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found'
        ], 404);
    }

    $fields = [];
    $params = [':id' => $assignmentId];

    if (isset($data['title'])) {
        $fields[] = 'title = :title';
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = 'description = :description';
        $params[':description'] = sanitizeInput($data['description']);
    }

    if (isset($data['due_date'])) {
        $dueDate = trim($data['due_date']);
        if (!validateDate($dueDate)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid due_date format. Expected YYYY-MM-DD'
            ], 400);
        }
        $fields[] = 'due_date = :due_date';
        $params[':due_date'] = $dueDate;
    }

    if (isset($data['files'])) {
        if (!is_array($data['files'])) {
            sendResponse([
                'success' => false,
                'message' => 'files must be an array'
            ], 400);
        }
        $fields[] = 'files = :files';
        $params[':files'] = json_encode($data['files']);
    }

    $fields[] = 'updated_at = NOW()';

    if (count($fields) <= 1) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided to update'
        ], 400);
    }

    $sql = "UPDATE assignments SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Assignment updated successfully'
        ]);
    } else {
        sendResponse([
            'success' => true,
            'message' => 'No changes made to the assignment'
        ]);
    }
}

function deleteAssignment($db, $assignmentId) {
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment ID is required'
        ], 400);
    }

    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found'
        ], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Assignment and associated comments deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete assignment'
        ], 500);
    }
}

function getCommentsByAssignment($db, $assignmentId) {
    if (empty($assignmentId)) {
        sendResponse([
            'success' => false,
            'message' => 'assignment_id is required'
        ], 400);
    }

    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC");
    $stmt->bindValue(':assignment_id', $assignmentId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $comments
    ]);
}

function createComment($db, $data) {
    if (
        empty($data['assignment_id']) ||
        empty($data['author']) ||
        !isset($data['text'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'assignment_id, author, and text are required'
        ], 400);
    }

    $assignmentId = $data['assignment_id'];
    $author       = sanitizeInput($data['author']);
    $text         = trim($data['text']);

    if ($text === '') {
        sendResponse([
            'success' => false,
            'message' => 'text cannot be empty'
        ], 400);
    }

    $stmt = $db->prepare("SELECT id FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $assignmentExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignmentExists) {
        sendResponse([
            'success' => false,
            'message' => 'Assignment not found'
        ], 404);
    }

    $sql = "INSERT INTO comments (assignment_id, author, text, created_at)
            VALUES (:assignment_id, :author, :text, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':assignment_id', $assignmentId);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);
    $stmt->execute();

    $newId = $db->lastInsertId();

    $stmt2 = $db->prepare("SELECT * FROM comments WHERE id = :id");
    $stmt2->bindValue(':id', $newId, PDO::PARAM_INT);
    $stmt2->execute();
    $comment = $stmt2->fetch(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'message' => 'Comment created successfully',
        'data'    => $comment
    ], 201);
}

function deleteComment($db, $commentId) {
    if (empty($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment ID is required'
        ], 400);
    }

    $stmt = $db->prepare("SELECT id FROM comments WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found'
        ], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment'
        ], 500);
    }
}

try {
    $resource = isset($_GET['resource']) ? $_GET['resource'] : null;

    if (!$resource) {
        sendResponse([
            'success' => false,
            'message' => 'resource query parameter is required'
        ], 400);
    }

    if ($method === 'GET') {
        if ($resource === 'assignments') {
            if (isset($_GET['id'])) {
                getAssignmentById($db, $_GET['id']);
            } else {
                getAllAssignments($db);
            }
        } elseif ($resource === 'comments') {
            if (isset($_GET['assignment_id'])) {
                getCommentsByAssignment($db, $_GET['assignment_id']);
            } else {
                sendResponse([
                    'success' => false,
                    'message' => 'assignment_id query parameter is required for comments'
                ], 400);
            }
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource'
            ], 400);
        }
    } elseif ($method === 'POST') {
        if ($resource === 'assignments') {
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            createComment($db, $data);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource'
            ], 400);
        }
    } elseif ($method === 'PUT') {
        if ($resource === 'assignments') {
            updateAssignment($db, $data);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'PUT method not supported for this resource'
            ], 405);
        }
    } elseif ($method === 'DELETE') {
        if ($resource === 'assignments') {
            $assignmentId = isset($_GET['id']) ? $_GET['id'] : (isset($data['id']) ? $data['id'] : null);
            deleteAssignment($db, $assignmentId);
        } elseif ($resource === 'comments') {
            $commentId = isset($_GET['id']) ? $_GET['id'] : null;
            deleteComment($db, $commentId);
        } else {
            sendResponse([
                'success' => false,
                'message' => 'Invalid resource'
            ], 400);
        }
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
        'error'   => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Server error',
        'error'   => $e->getMessage()
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

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateAllowedValue($value, $allowedValues) {
    return in_array($value, $allowedValues, true);
}

?>
