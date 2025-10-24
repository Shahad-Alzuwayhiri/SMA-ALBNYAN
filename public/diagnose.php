<?php
// Simple diagnostic page to confirm Apache + PHP serving
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');

echo "Diagnostic page for ContractSama\n";
echo "REQUEST_URI=" . ($_SERVER['REQUEST_URI'] ?? '<missing>') . "\n";
echo "REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? '<missing>') . "\n";
echo "PHP_VERSION=" . PHP_VERSION . "\n";
echo "SCRIPT_FILENAME=" . ($_SERVER['SCRIPT_FILENAME'] ?? '<missing>') . "\n";
