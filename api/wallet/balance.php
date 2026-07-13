<?php
// api/wallet/balance.php - Get Wallet Balance

use PakPaisa\Includes\{authenticateUser, jsonResponse};

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = authenticateUser();
if (!$user) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

jsonResponse([
    'success' => true,
    'balance' => (float)$user['wallet_balance'],
    'limit' => (float)$user['wallet_limit'],
    'currency' => 'PKR'
]);
