<?php
// Database parameters
$host = 'localhost';
$user = 'root';
$password = '';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL to create database
    $sql = "CREATE DATABASE IF NOT EXISTS discussion_board";
    $pdo->exec($sql);
    echo "Database created successfully.\n";

    // Select the database
    $pdo->exec("USE discussion_board");

    // Create tables
    $sqlTopics = "CREATE TABLE IF NOT EXISTS topics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        topic_id VARCHAR(50) UNIQUE NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        author VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlTopics);
    
    $sqlReplies = "CREATE TABLE IF NOT EXISTS replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reply_id VARCHAR(50) UNIQUE NOT NULL,
        topic_id VARCHAR(50) NOT NULL,
        text TEXT NOT NULL,
        author VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlReplies);

    echo "Tables created successfully.\n";
} catch (PDOException $e) {
    die "An error occurred: " . $e->getMessage();
}
?>
