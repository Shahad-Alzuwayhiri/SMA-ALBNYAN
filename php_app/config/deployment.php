<?php
// Environment configuration for deployment

// Database configuration - use environment variables in production
if (getenv('DATABASE_URL')) {
    // For Heroku PostgreSQL
    $url = parse_url(getenv('DATABASE_URL'));
    $host = $url['host'];
    $port = $url['port'];
    $username = $url['user'];
    $password = $url['pass'];
    $database = ltrim($url['path'], '/');
    
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
} else {
    // Local SQLite database
    $dbPath = __DIR__ . '/../contracts.db';
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>