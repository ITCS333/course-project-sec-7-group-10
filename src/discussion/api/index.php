<?php
session_start(); 
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ==================== Session Variables ====================
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'admin';
}

// ==================== Handle OPTIONS ====================
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
if (!is_array($requestBody)) {
    $requestBody = [];
}

// ==================== دوال Topics ====================
function getAllTopics($db) {
    $stmt = $db->prepare("SELECT * FROM topics");
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($topics);
}

function getTopicById($db, $topicId) {
    $stmt = $db->prepare("SELECT * FROM topics WHERE topic_id = ?");
    $stmt->execute([$topicId]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($topic);
}

function createTopic($db, $data) {
    $stmt = $db->prepare("INSERT INTO topics (title, content, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$data['title'], $data['content'], $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'message' => 'Topic created']);
}

function updateTopic($db, $data) {
    $stmt = $db->prepare("UPDATE topics SET title = ?, content = ? WHERE topic_id = ?");
    $stmt->execute([$data['title'], $data['content'], $data['topic_id']]);
    echo json_encode(['success' => true, 'message' => 'Topic updated']);
}

function deleteTopic($db, $topicId) {
    $stmt = $db->prepare("DELETE FROM topics WHERE topic_id = ?");
    $stmt->execute([$topicId]);
    echo json_encode(['success' => true, 'message' => 'Topic deleted']);
}

// ==================== دوال Replies ====================
function getRepliesByTopicId($db, $topicId) {
    $stmt = $db->prepare("SELECT * FROM replies WHERE topic_id = ?");
    $stmt->execute([$topicId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($replies);
}

function createReply($db, $data) {
    $stmt = $db->prepare("INSERT INTO replies (topic_id, content, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$data['topic_id'], $data['content'], $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'message' => 'Reply created']);
}

function deleteReply($db, $replyId) {
    $stmt = $db->prepare("DELETE FROM replies WHERE reply_id = ?");
    $stmt->execute([$replyId]);
    echo json_encode(['success' => true, 'message' => 'Reply deleted']);
}

// ==================== Helpers ====================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
}

function isValidResource($resource) {
    return in_array($resource, ['topics', 'replies']);
}

// ==================== معالجة الطلب ====================
try {
    $resource = $queryParams['resource'] ?? null;

    if (!isValidResource($resource)) {
        sendResponse(['message' => 'Bad Request: Invalid resource.'], 400);
        exit;
    }

    if ($resource === 'topics') {
        if ($requestMethod === 'GET') {
            if (!empty($queryParams['id'])) getTopicById($db, $queryParams['id']);
            else getAllTopics($db);
        } elseif ($requestMethod === 'POST') createTopic($db, $requestBody);
        elseif ($requestMethod === 'PUT') {
            if (empty($requestBody['topic_id'])) sendResponse(['message' => 'Topic ID is required'], 400);
            else updateTopic($db, $requestBody);
        } elseif ($requestMethod === 'DELETE') {
            $topicId = $queryParams['id'] ?? ($requestBody['topic_id'] ?? null);
            if (empty($topicId)) sendResponse(['message' => 'Topic ID is required'], 400);
            else deleteTopic($db, $topicId);
        } else sendResponse(['message' => 'Method Not Allowed'], 405);
    } elseif ($resource === 'replies') {
        if ($requestMethod === 'GET') {
            $topicId = $queryParams['topic_id'] ?? null;
            if (empty($topicId)) sendResponse(['message' => 'Topic ID is required'], 400);
            else getRepliesByTopicId($db, $topicId);
        } elseif ($requestMethod === 'POST') createReply($db, $requestBody);
        elseif ($requestMethod === 'DELETE') {
            $replyId = $queryParams['id'] ?? ($requestBody['reply_id'] ?? null);
            if (empty($replyId)) sendResponse(['message' => 'Reply ID is required'], 400);
            else deleteReply($db, $replyId);
        } else sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
} catch (Exception $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
}
?>
