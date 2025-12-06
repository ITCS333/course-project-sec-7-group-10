<?php

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

function getAllTopics($db) {
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM topics";
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= " WHERE subject LIKE ? OR message LIKE ? OR author LIKE ?";
        $searchTerm = "%" . $_GET['search'] . "%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }

    $validSortFields = ['subject', 'author', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    if (!in_array($sort, $validSortFields, true)) {
        $sort = 'created_at';
    }
    if (!in_array($order, ['asc', 'desc'], true)) {
        $order = 'desc';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['status' => 'success', 'data' => $results]);
}

function getTopicById($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
    }

    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM topics WHERE topic_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $topicId);
    $stmt->execute();

    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topic) {
        sendResponse(['status' => 'success', 'data' => $topic]);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Topic not found.'], 404);
    }
}

function createTopic($db, $data) {
    if (empty($data['topic_id']) || empty($data['subject']) || empty($data['message']) || empty($data['author'])) {
        sendResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

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

function updateTopic($db, $data) {
    if (empty($data['topic_id'])) {
        sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);

    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(1, $topicId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Topic not found.'], 404);
    }

    $updates = [];
    $params = [];

    if (isset($data['subject'])) {
        $updates[] = "subject = ?";
        $params[] = sanitizeInput($data['subject']);
    }
    if (isset($data['message'])) {
        $updates[] = "message = ?";
        $params[] = sanitizeInput($data['message']);
    }

    if (empty($updates)) {
        sendResponse(['status' => 'error', 'message' => 'No fields to update.'], 400);
    }

    $sql = "UPDATE topics SET " . implode(', ', $updates) . " WHERE topic_id = ?";
    $params[] = $topicId;

    $stmt = $db->prepare($sql);
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }

    if ($stmt->execute()) {
        sendResponse(['status' => 'success', 'message' => 'Topic updated.']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to update topic.'], 500);
    }
}

function deleteTopic($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
    }

    $checkSql = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(1, $topicId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Topic not found.'], 404);
    }

    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = ?";
    $deleteRepliesStmt = $db->prepare($deleteRepliesSql);
    $deleteRepliesStmt->bindParam(1, $topicId);
    $deleteRepliesStmt->execute();

    $deleteTopicSql = "DELETE FROM topics WHERE topic_id = ?";
    $deleteTopicStmt = $db->prepare($deleteTopicSql);
    $deleteTopicStmt->bindParam(1, $topicId);

    if ($deleteTopicStmt->execute()) {
        sendResponse(['status' => 'success', 'message' => 'Topic deleted.']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to delete topic.'], 500);
    }
}

function getRepliesByTopicId($db, $topicId) {
    if (empty($topicId)) {
        sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
    }

    $sql = "SELECT reply_id, topic_id, text, author, DATE_FORMAT(created_at, '%Y-%m-%d') AS created_at FROM replies WHERE topic_id = ? ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $topicId);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['status' => 'success', 'data' => $results]);
}

function createReply($db, $data) {
    if (empty($data['reply_id']) || empty($data['topic_id']) || empty($data['text']) || empty($data['author'])) {
        sendResponse(['status' => 'error', 'message' => 'All fields are required.'], 400);
    }

    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    $checkTopicSql = "SELECT topic_id FROM topics WHERE topic_id = ?";
    $checkTopicStmt = $db->prepare($checkTopicSql);
    $checkTopicStmt->bindParam(1, $topicId);
    $checkTopicStmt->execute();

    if (!$checkTopicStmt->fetch()) {
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

function deleteReply($db, $replyId) {
    if (empty($replyId)) {
        sendResponse(['status' => 'error', 'message' => 'Reply ID is required.'], 400);
    }

    $checkSql = "SELECT reply_id FROM replies WHERE reply_id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(1, $replyId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse(['status' => 'error', 'message' => 'Reply not found.'], 404);
    }

    $deleteSql = "DELETE FROM replies WHERE reply_id = ?";
    $deleteStmt = $db->prepare($deleteSql);
    $deleteStmt->bindParam(1, $replyId);

    if ($deleteStmt->execute()) {
        sendResponse(['status' => 'success', 'message' => 'Reply deleted.']);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Failed to delete reply.'], 500);
    }
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    if (!is_string($data)) {
        $data = (string) $data;
    }
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isValidResource($resource) {
    $allowedResources = ['topics', 'replies'];
    return in_array($resource, $allowedResources, true);
}

try {
    $resource = $queryParams['resource'] ?? null;

    if (!isValidResource($resource)) {
        sendResponse(['message' => 'Bad Request: Invalid resource.'], 400);
    }

    if ($resource === 'topics') {
        if ($requestMethod === 'GET') {
            if (!empty($queryParams['id'])) {
                getTopicById($db, $queryParams['id']);
            } else {
                getAllTopics($db);
            }
        } elseif ($requestMethod === 'POST') {
            createTopic($db, $requestBody);
        } elseif ($requestMethod === 'PUT') {
            if (empty($requestBody['topic_id'])) {
                sendResponse(['message' => 'Bad Request: Topic ID is required for update.'], 400);
            }
            updateTopic($db, $requestBody);
        } elseif ($requestMethod === 'DELETE') {
            $topicId = $queryParams['id'] ?? ($requestBody['topic_id'] ?? null);
            if (empty($topicId)) {
                sendResponse(['message' => 'Bad Request: Topic ID is required for deletion.'], 400);
            }
            deleteTopic($db, $topicId);
        } else {
            sendResponse(['message' => 'Method Not Allowed'], 405);
        }
    } elseif ($resource === 'replies') {
        if ($requestMethod === 'GET') {
            $topicId = $queryParams['topic_id'] ?? null;
            if (empty($topicId)) {
                sendResponse(['status' => 'error', 'message' => 'Topic ID is required.'], 400);
            }
            getRepliesByTopicId($db, $topicId);
        } elseif ($requestMethod === 'POST') {
            createReply($db, $requestBody);
        } elseif ($requestMethod === 'DELETE') {
            $replyId = $queryParams['id'] ?? ($requestBody['reply_id'] ?? null);
            if (empty($replyId)) {
                sendResponse(['message' => 'Bad Request: Reply ID is required for deletion.'], 400);
            }
            deleteReply($db, $replyId);
        } else {
            sendResponse(['message' => 'Method Not Allowed'], 405);
        }
    }
} catch (PDOException $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
} catch (Exception $e) {
    sendResponse(['message' => 'Internal Server Error'], 500);
}

?>
