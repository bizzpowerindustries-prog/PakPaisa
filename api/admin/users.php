<?php
// api/admin/users.php - Get All Users (Admin)

use PakPaisa\Config\Database;
use PakPaisa\Includes\{jsonResponse};

$db = Database::getInstance()->getConnection();

$stmt = $db->query("
    SELECT id, name, mobile, wallet_balance, kyc_verified, card_active, card_number
    FROM users 
    ORDER BY id
");
$users = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'users' => array_map(function($user) {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'mobile' => $user['mobile'],
            'balance' => (float)$user['wallet_balance'],
            'kycVerified' => (bool)$user['kyc_verified'],
            'cardStatus' => $user['card_active'] ? 'Active' : 'Frozen',
            'cardNumber' => $user['card_number']
        ];
    }, $users)
]);
