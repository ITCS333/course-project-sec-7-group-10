<?php

function getDBConnection() {
    $host = 'localhost';
    $db   = 'course';
    $username = 'admin';
    $password = 'password123';  
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

?>
