<?php
// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
require_once '../config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Request method
$method = $_SERVER['REQUEST_METHOD'];
// Request body for POST/PUT
$input = json_decode(file_get_contents('php://input'), true);
// Query parameters
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resource_id = $_GET['resource_id'] ?? null;
$comment_id = $_GET['comment_id'] ?? null;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES);
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    return ['valid' => count($missing) === 0, 'missing' => $missing];
}

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================
function getAllResources($db) {
    $query = "SELECT id, title, description, link, created_at FROM resources ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $resources]);
}

function getResourceById($db, $resourceId) {
    if (!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id=?");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($resource) {
        sendResponse(['success'=>true,'data'=>$resource]);
    } else {
        sendResponse(['success'=>false,'message'=>'Resource not found'],404);
    }
}

function createResource($db, $data) {
    $check = validateRequiredFields($data, ['title','link']);
    if (!$check['valid']) sendResponse(['success'=>false,'message'=>'Missing fields: '.implode(', ',$check['missing'])],400);

    $title = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link = sanitizeInput($data['link']);
    if (!validateUrl($link)) sendResponse(['success'=>false,'message'=>'Invalid URL'],400);

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?,?,?)");
    if ($stmt->execute([$title,$description,$link])) {
        sendResponse(['success'=>true,'message'=>'Resource created','id'=>$db->lastInsertId()],201);
    } else {
        sendResponse(['success'=>false,'message'=>'Failed to create resource'],500);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================
function getCommentsByResourceId($db, $resourceId) {
    if (!is_numeric($resourceId)) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments WHERE resource_id=? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$comments]);
}

function createComment($db, $data) {
    $check = validateRequiredFields($data,['resource_id','author','text']);
    if (!$check['valid']) sendResponse(['success'=>false,'message'=>'Missing fields: '.implode(', ',$check['missing'])],400);

    if (!is_numeric($data['resource_id'])) sendResponse(['success'=>false,'message'=>'Invalid resource ID'],400);

    // check resource exists
    $stmt = $db->prepare("SELECT id FROM resources WHERE id=?");
    $stmt->execute([$data['resource_id']]);
    if (!$stmt->fetch()) sendResponse(['success'=>false,'message'=>'Resource not found'],404);

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    $stmt = $db->prepare("INSERT INTO comments (resource_id, author, text) VALUES (?,?,?)");
    if ($stmt->execute([$data['resource_id'],$author,$text])) {
        sendResponse(['success'=>true,'message'=>'Comment created','id'=>$db->lastInsertId()],201);
    } else {
        sendResponse(['success'=>false,'message'=>'Failed to create comment'],500);
    }
}

// ============================================================================
// REQUEST ROUTER
// ============================================================================
try {
    if ($method === 'GET') {
        if ($action==='comments' && $resource_id) {
            getCommentsByResourceId($db,$resource_id);
        } elseif ($id) {
            getResourceById($db,$id);
        } else {
            getAllResources($db);
        }
    } elseif ($method==='POST') {
        if ($action==='comment') {
            createComment($db,$input);
        } else {
            createResource($db,$input);
        }
    } else {
        sendResponse(['success'=>false,'message'=>'Method not allowed'],405);
    }
} catch (PDOException $e) {
    sendResponse(['success'=>false,'message'=>'Database error'],500);
} catch (Exception $e) {
    sendResponse(['success'=>false,'message'=>'Server error'],500);
}
?>
