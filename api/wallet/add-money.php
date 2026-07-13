<?php
// api/wallet/add-money.php - Add Money

use PakPaisa\Config\Database;
use PakPaisa\Config\Config;
use PakPaisa\Includes\{authenticateUser, jsonResponse, generateReference};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = authenticateUser();
if (!$user) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$amount = (float)($data['amount'] ?? 0);
$method = $data['method'] ?? 'Bank Transfer';

if ($amount < 100) {
    jsonResponse(['error' => 'Minimum amount is Rs. 100'], 400);
}

if ($user['wallet_balance'] + $amount > $user['wallet_limit']) {
    jsonResponse(['error' => 'Wallet limit exceeded'], 400);
}

$db = Database::getInstance()->getConnection();
$config = Config::getInstance();

// Begin transaction
$db->beginTransaction();

try {
    // Simulate bank transfer (testing mode)
    if ($config->isTesting()) {
        // Just log it
        error_log("💳 TEST: Adding Rs. $amount to user {$user['id']}");
    }
    
    // Update user balance
    $newBalance = $user['wallet_balance'] + $amount;
    $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $user['id']]);
    
    // Create transaction record
    $reference = generateReference();
    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, type, amount, status, reference, description)
        VALUES (?, ?, ?, 'SUCCESS', ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        'ADD_MONEY',
        $amount,
        $reference,
        "Added money via $method"
    ]);
    
    $transactionId = $db->lastInsertId();
    
    $db->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Money added successfully!',
        'newBalance' => $newBalance,
        'transaction' => [
            'id' => $transactionId,
            'type' => 'ADD_MONEY',
            'amount' => $amount,
            'status' => 'SUCCESS',
            'reference' => $reference,
            'description' => "Added money via $method"
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => $e->getMessage()], 500);
}
