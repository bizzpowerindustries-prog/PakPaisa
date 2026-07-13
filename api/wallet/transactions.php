<?php
// api/wallet/transactions.php - Get Transactions

use PakPaisa\Config\Database;
use PakPaisa\Includes\{authenticateUser, jsonResponse};

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = authenticateUser();
if (!$user) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'transactions' => $transactions
]);
