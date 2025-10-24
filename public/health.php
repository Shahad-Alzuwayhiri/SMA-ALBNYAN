<?php
header('Content-Type: application/json; charset=utf-8');

$status = [
    'status' => 'online',
    'server' => 'PHP ' . phpversion(),
    'time' => date('Y-m-d H:i:s'),
    'port' => '8086',
    'message' => 'النظام يعمل بشكل طبيعي'
];

try {
    $database_path = __DIR__ . '/../database/contracts.db';
    $pdo = new PDO("sqlite:$database_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // اختبار الاتصال بقاعدة البيانات
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $status['database'] = 'connected';
    $status['users_count'] = $user_count;
    
} catch (Exception $e) {
    $status['database'] = 'error';
    $status['database_error'] = $e->getMessage();
}

echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>