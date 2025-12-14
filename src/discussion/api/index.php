<?php
session_start(); 
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
if (!is_array($requestBody)) {
    $requestBody = [];
}

// ==================== دوال Topics و Replies ====================

function getAllTopics($db) { /* ... نفس كودك ... */ }
function getTopicById($db, $topicId) { /* ... نفس كودك ... */ }
function createTopic($db, $data) { /* ... نفس كودك ... */ }
function updateTopic($db, $data) { /* ... نفس كودك ... */ }
function deleteTopic($db, $topicId) { /* ... نفس كودك ... */ }
function getRepliesByTopicId($db, $topicId) { /* ... نفس كودك ... */ }
function createReply($db, $data) { /* ... نفس كودك ... */ }
function deleteReply($db, $replyId) { /* ... نفس كودك ... */ }
function sendResponse($data, $statusCode = 200) { /* ... نفس كودك ... */ }
function sanitizeInput($data) { /* ... نفس كودك ... */ }
function isValidResource($resource) { /* ... نفس كودك ... */ }

// ==================== معالجة الطلب ====================

try {
    $resource = $queryParams['resource'] ?? null;

    if (!isValidResource($resource)) {
        sendResponse(['message' => 'Bad Request: Invalid resource.'], 400);
    }

    if ($resource === 'topics') {
        if ($requestMethod === 'GET') {
            if (!empty($queryParams['id'])) getTopicById($db, $queryParams['id']);
            else getAllTopics($db);
        } elseif ($requestMethod === 'POST') createTopic($db, $requestBody);
        elseif ($requestMethod === 'PUT') {
            if (empty($requestBody['topic_id'])) sendResponse(['message' => 'Bad Request: Topic ID is required for update.'], 400);
            updateTopic($db, $requestBody);
        } elseif ($requestMethod === 'DELETE') {
            $topicId = $queryParams['id'] ?? ($requestBody['topic_id'] ?? null);
            if (empty($topicId)) sendResponse(['message' => 'Bad Request: Topic ID is required for deletion.'], 400);
            deleteTopic($db, $topicId);
        } else sendResponse(['message' => 'Method Not Allowed'], 405);
    } elseif ($resource === 'replies') {
        if ($requestMethod === 'GET') {
            $topicId = $queryParams['topic_id'] ?? null;
            if (empty($topicId)) sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
            getRepliesByTopicId($db, $topicId);
        } elseif ($requestMethod === 'POST') createReply($db, $requestBody);
        elseif ($requestMethod === 'DELETE') {
            $replyId = $queryParams['id'] ?? ($requestBody['reply_id'] ?? null);
            if (empty($replyId)) sendResponse(['message' => 'Bad Request: Reply ID is required for deletion.'], 400);
            deleteReply($db, $replyId);
        } else sendResponse(['message' => 'Method Not Allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
} catch (Exception $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
}

?>
