<?php
session_start(); // ← أضيف هذا السطر حسب متطلبات الاختبار
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include Database connection
require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get request body for POST and PUT
$requestBody = json_decode(file_get_contents('php://input'), true);

// Parse query parameters
$resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';
$weekId = isset($_GET['week_id']) ? $_GET['week_id'] : null;
$commentId = isset($_GET['id']) ? $_GET['id'] : null;

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

function getAllWeeks($db) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!in_array($sort, $allowedSortFields)) $sort = 'start_date';
    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $params = ["%$search%", "%$search%"];
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true);
    }

    sendResponse(['success' => true, 'data' => $weeks]);
}

function getWeekById($db, $weekId) {
    if (!$weekId) sendError("week_id is required", 400);

    $stmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {
        $week['links'] = json_decode($week['links'], true);
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendError("Week not found", 404);
    }
}

function createWeek($db, $data) {
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) sendError("$field is required", 400);
    }

    $week_id = sanitizeInput($data['week_id']);
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $start_date = $data['start_date'];

    if (!validateDate($start_date)) sendError("Invalid start_date format", 400);

    $stmtCheck = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmtCheck->execute([$week_id]);
    if ($stmtCheck->fetch()) sendError("week_id already exists", 409);

    $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);

    $stmt = $db->prepare("INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)");
    $success = $stmt->execute([$week_id, $title, $start_date, $description, $links]);

    if ($success) sendResponse(['success' => true, 'data' => $data], 201);
    else sendError("Failed to create week", 500);
}

function updateWeek($db, $data) {
    if (empty($data['week_id'])) sendError("week_id is required", 400);

    $stmtCheck = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
    $stmtCheck->execute([$data['week_id']]);
    $week = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if (!$week) sendError("Week not found", 404);

    $fields = [];
    $values = [];

    if (!empty($data['title'])) { $fields[] = "title = ?"; $values[] = sanitizeInput($data['title']); }
    if (!empty($data['start_date'])) {
        if (!validateDate($data['start_date'])) sendError("Invalid start_date format", 400);
        $fields[] = "start_date = ?"; $values[] = $data['start_date'];
    }
    if (!empty($data['description'])) { $fields[] = "description = ?"; $values[] = sanitizeInput($data['description']); }
    if (!empty($data['links']) && is_array($data['links'])) { $fields[] = "links = ?"; $values[] = json_encode($data['links']); }

    if (empty($fields)) sendError("No fields to update", 400);

    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    $sql = "UPDATE weeks SET " . implode(", ", $fields) . " WHERE week_id = ?";
    $values[] = $data['week_id'];

    $stmt = $db->prepare($sql);
    $success = $stmt->execute($values);

    if ($success) sendResponse(['success' => true, 'data' => $data]);
    else sendError("Failed to update week", 500);
}

function deleteWeek($db, $weekId) {
    if (!$weekId) sendError("week_id is required", 400);

    $stmtCheck = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
    $stmtCheck->execute([$weekId]);
    if (!$stmtCheck->fetch()) sendError("Week not found", 404);

    $stmtDeleteComments = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    $stmtDeleteComments->execute([$weekId]);

    $stmtDeleteWeek = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    $success = $stmtDeleteWeek->execute([$weekId]);

    if ($success) sendResponse(['success' => true, 'message' => "Week and comments deleted"]);
    else sendError("Failed to delete week", 500);
}

// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

function getCommentsByWeek($db, $weekId) {
    if (!$weekId) sendError("week_id is required", 400);

    $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $comments]);
}

function createComment($db, $data) {
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (empty($data[$field])) sendError("$field is required", 400);
    }

    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    if (empty($text)) sendError("Text cannot be empty", 400);

    $stmtCheck = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmtCheck->execute([$weekId]);
    if (!$stmtCheck->fetch()) sendError("Week not found", 404);

    $stmt = $db->prepare("INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)");
    $success = $stmt->execute([$weekId, $author, $text]);

    if ($success) {
        $id = $db->lastInsertId();
        sendResponse(['success' => true, 'data' => ['id' => $id, 'week_id' => $weekId, 'author' => $author, 'text' => $text]], 201);
    } else sendError("Failed to create comment", 500);
}

function deleteComment($db, $commentId) {
    if (!$commentId) sendError("id is required", 400);

    $stmtCheck = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $stmtCheck->execute([$commentId]);
    if (!$stmtCheck->fetch()) sendError("Comment not found", 404);

    $stmtDelete = $db->prepare("DELETE FROM comments WHERE id = ?");
    $success = $stmtDelete->execute([$commentId]);

    if ($success) sendResponse(['success' => true, 'message' => "Comment deleted"]);
    else sendError("Failed to delete comment", 500);
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            if ($weekId) getWeekById($db, $weekId);
            else getAllWeeks($db);
        } elseif ($method === 'POST') createWeek($db, $requestBody);
        elseif ($method === 'PUT') updateWeek($db, $requestBody);
        elseif ($method === 'DELETE') deleteWeek($db, $weekId);
        else sendError("Method not allowed", 405);
    } elseif ($resource === 'comments') {
        if ($method === 'GET') getCommentsByWeek($db, $weekId);
        elseif ($method === 'POST') createComment($db, $requestBody);
        elseif ($method === 'DELETE') deleteComment($db, $commentId);
        else sendError("Method not allowed", 405);
    } else sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
} catch (PDOException $e) {
    sendError("Database error occurred", 500);
} catch (Exception $e) {
    sendError("Server error occurred", 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isValidSortField($field, $allowedFields) {
    return in_array($field, $allowedFields);
}
?>
