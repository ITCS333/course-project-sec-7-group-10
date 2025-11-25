<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}

function require_student() {
    require_login();
    if ($_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}
