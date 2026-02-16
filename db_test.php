<?php
require_once 'config.php';

header('Content-Type: text/plain');

$port = defined('DB_PORT') ? DB_PORT : 3306;
$dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "DB connection: OK\n";
    echo "Host: " . DB_HOST . "\n";
    echo "DB: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Port: " . $port . "\n";
} catch (PDOException $e) {
    error_log("DB test failed: " . $e->getMessage());
    echo "DB connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Host: " . DB_HOST . "\n";
    echo "DB: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Port: " . $port . "\n";
}
