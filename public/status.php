<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Server is running perfectly!',
    'time' => date('Y-m-d H:i:s'),
    'server' => 'localhost:8084',
    'php_version' => PHP_VERSION
]);
?>