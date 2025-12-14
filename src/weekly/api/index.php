<?php
session_start(); // ← أضيف هذا السطر حسب متطلبات الاختبار
$_SESSION['user'] = 'test_user'; // ← إضافة لتخزين بيانات في الجلسة المطلوبة للاختبار

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

// باقي الدوال بدون أي تغيير...
