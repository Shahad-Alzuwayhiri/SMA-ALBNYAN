<?php
$pdo = new PDO('sqlite:database/contracts.db');
try {
    $pdo->exec('ALTER TABLE contracts ADD COLUMN contract_number TEXT');
    echo 'تم إضافة contract_number';
} catch (Exception $e) {
    echo 'العمود موجود أو خطأ: ' . $e->getMessage();
}