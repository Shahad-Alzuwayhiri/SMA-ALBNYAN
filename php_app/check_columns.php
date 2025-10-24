<?php
$pdo = new PDO('sqlite:database/contracts.db');

echo "=== أعمدة جدول العقود ===\n";
$result = $pdo->query('PRAGMA table_info(contracts)');
while ($row = $result->fetch()) {
    echo "- {$row['name']} ({$row['type']})\n";
}

echo "\n=== أعمدة جدول المستخدمين ===\n";
$result = $pdo->query('PRAGMA table_info(users)');
while ($row = $result->fetch()) {
    echo "- {$row['name']} ({$row['type']})\n";
}