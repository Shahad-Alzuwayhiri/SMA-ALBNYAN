<?php
$pdo = new PDO('sqlite:database/contracts.db');
$result = $pdo->query('SELECT name FROM sqlite_master WHERE type="table"');
echo "الجداول الموجودة:\n";
while ($row = $result->fetch()) {
    echo "- " . $row['name'] . "\n";
}