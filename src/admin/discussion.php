<?php
// --- Start session ---
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$rawBody = file_get_contents('php://input');
$requestBody = json_decode($rawBody, true);
if (!is_array($requestBody)) $requestBody = [];

// --- Functions ---
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    if (!is_string($data)) $data = (string) $data;
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Example: store user in session
function storeUserInSession($username) {
    $_SESSION['username'] = $username;
}

// --- Topic functions ---
function createTopic($db, $data) {
    if (empty($data['topic_id']) || empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
        sendResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    // Store user in session
    storeUserInSession($author);

    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(1, $topicId);
    $checkStmt->execute();
    if ($checkStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Topic ID already exists.'], 409);
    }

    $insertSql = "INSERT INTO topics (topic_id, subject, message, author) VALUES (?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->bindParam(1, $topicId);
    $insertStmt->bindParam(2, $subject);
    $insertStmt->bindParam(3, $message);
    $insertStmt->bindParam(4, $author);

    if ($insertStmt->execute()) {
        sendResponse(['status' => 'success', 'message' => 'Topic created.', 'topic_id' => $topicId], 201);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to create topic.'], 500);
    }
}

// --- Similar changes can be done for createReply ---
function createReply($db, $data) {
    if (empty($data['reply_id']) || empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        sendResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
    }

    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    // Store user in session
    storeUserInSession($author);

    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(1, $topicId);
    $checkStmt->execute();
    if (!$checkStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Parent topic not found.'], 404);
    }

    $checkReplySql = "SELECT reply_id FROM replies WHERE reply_id = ?";
    $checkReplyStmt = $db->prepare($checkReplySql);
    $checkReplyStmt->bindParam(1, $replyId);
    $checkReplyStmt->execute();
    if ($checkReplyStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Reply ID already exists.'], 409);
    }

    $insertSql = "INSERT INTO replies (reply_id, topic_id, text, author) VALUES (?, ?, ?, ?)";
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->bindParam(1, $replyId);
    $insertStmt->bindParam(2, $topicId);
    $insertStmt->bindParam(3, $text);
    $insertStmt->bindParam(4, $author);

    if ($insertStmt->execute()) {
        sendResponse(['status' => 'success', 'message' => 'Reply created.', 'reply_id' => $replyId], 201);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to create reply.'], 500);
    }
}

// --- Handle requests ---
try {
    $resource = $queryParams['resource'] ?? null;

    if ($resource === 'topics') {
        if ($requestMethod === 'POST') createTopic($db, $requestBody);
    } elseif ($resource === 'replies') {
        if ($requestMethod === 'POST') createReply($db, $requestBody);
    }
} catch (Exception $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
}
?>
