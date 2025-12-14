<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Store user data in session after login.
 * @param string $userId
 * @param string $role - 'admin' or 'student'
 */
function login_user($userId, $role) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
}

/**
 * Ensure the user is logged in.
 * Redirect to login page if not.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Ensure the user is an admin.
 */
function require_admin() {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

/**
 * Ensure the user is a student.
 */
function require_student() {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

/**
 * Log out the user by clearing the session.
 */
function logout_user() {
    session_unset();
    session_destroy();
}
?>
