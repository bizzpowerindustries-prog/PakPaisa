<?php
// api/wallet/profile.php - Get User Profile

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
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'mobile' => $user['mobile'],
        'walletBalance' => (float)$user['wallet_balance'],
        'kycVerified' => (bool)$user['kyc_verified'],
        'cardActive' => (bool)$user['card_active'],
        'cardNumber' => $user['card_number'],
        'cardExpiry' => $user['card_expiry']
    ]
]);
