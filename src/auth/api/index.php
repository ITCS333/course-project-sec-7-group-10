<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Set Response Headers ---
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- Get POST Data ---
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!isset($data['email'], $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation (Optional but Recommended) ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

// --- Database Connection ---
require_once __DIR__ . '/../db.php';

try {
    $pdo = getDBConnection(); // Assume getDBConnection() returns a PDO instance

    // --- Prepare SQL Query ---
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email");

    // --- Execute the Query ---
    $stmt->execute(['email' => $email]);

    // --- Fetch User Data ---
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    if ($user && password_verify($password, $user['password'])) {

        // --- Handle Successful Authentication ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
        exit;

    } else {
        // --- Handle Failed Authentication ---
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }

} catch (PDOException $e) {
    // TODO: Log the error for debugging
    error_log('Login Error: ' . $e->getMessage());

    // TODO: Return a generic error message to the client
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
}

// --- End of Script ---
?>
