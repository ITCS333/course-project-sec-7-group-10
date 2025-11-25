<?php
// Start session on all pages that include this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ITCS333 – Course Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Basic styling -->
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        header {
            background: #222;
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-size: 15px;
        }
        header nav a:hover {
            text-decoration: underline;
        }
        main {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<header>
    <div><strong>ITCS333 Portal</strong></div>
    <nav>
        <a href="/index.php">Home</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="/login.php">Login</a>
        <?php else: ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/admin/index.php">Admin Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'student'): ?>
                <a href="/student/index.php">Student Dashboard</a>
            <?php endif; ?>
            <a href="/logout.php">Logout</a>
        <?php endif; ?>
    </nav>
</header>

<main>
