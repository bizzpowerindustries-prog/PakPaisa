<?php
// api/wallet/send-money.php - Send Money

use PakPaisa\Config\Database;
use PakPaisa\Config\Config;
use PakPaisa\Includes\{authenticateUser, jsonResponse, validateMobile, generateReference};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = authenticateUser();
if (!$user) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$recipientMobile = $data['recipientMobile'] ?? '';
$amount = (float)($data['amount'] ?? 0);
$message = $data['message'] ?? '';

if (empty($recipientMobile)) {
    jsonResponse(['error' => 'Recipient mobile is required'], 400);
}

if ($amount < 100) {
    jsonResponse(['error' => 'Minimum amount is Rs. 100'], 400);
}

if ($user['wallet_balance'] < $amount) {
    jsonResponse(['error' => 'Insufficient balance'], 400);
}

$validatedMobile = validateMobile($recipientMobile);
if (!$validatedMobile) {
    jsonResponse(['error' => 'Invalid recipient mobile'], 400);
}

$db = Database::getInstance()->getConnection();
$config = Config::getInstance();

// Check recipient exists
$stmt = $db->prepare("SELECT * FROM users WHERE mobile = ?");
$stmt->execute([$validatedMobile]);
$recipient = $stmt->fetch();

if (!$recipient) {
    jsonResponse(['error' => 'Recipient not found'], 404);
}

// Begin transaction
$db->beginTransaction();

try {
    // Simulate bank transfer (testing mode)
    if ($config->isTesting()) {
        error_log("💸 TEST: Sending Rs. $amount from user {$user['id']} to {$recipient['id']}");
    }
    
    // Update sender balance
    $newSenderBalance = $user['wallet_balance'] - $amount;
    $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->execute([$newSenderBalance, $user['id']]);
    
    // Update recipient balance
    $newRecipientBalance = $recipient['wallet_balance'] + $amount;
    $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
    $stmt->execute([$newRecipientBalance, $recipient['id']]);
    
    // Create transaction record
    $reference = generateReference();
    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, type, amount, status, reference, description, recipient_id)
        VALUES (?, ?, ?, 'SUCCESS', ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        'SEND_MONEY',
        $amount,
        $reference,
        $message ?: "To " . $recipient['name'],
        $recipient['id']
    ]);
    
    $transactionId = $db->lastInsertId();
    
    $db->commit();
    
    jsonResponse([
        'success' => true,
        'message' => 'Money sent successfully!',
        'newBalance' => $newSenderBalance,
        'transaction' => [
            'id' => $transactionId,
            'type' => 'SEND_MONEY',
            'amount' => $amount,
            'status' => 'SUCCESS',
            'reference' => $reference,
            'description' => $message ?: "To " . $recipient['name']
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    jsonResponse(['error' => $e->getMessage()], 500);
}
