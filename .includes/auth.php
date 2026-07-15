<?php
// includes/auth.php - JWT Authentication Middleware

namespace PakPaisa\Includes;

use PakPaisa\Config\Database;

function authenticateUser() {
    $decoded = authenticate();
    
    if (!$decoded) {
        return null;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND mobile = ?");
    $stmt->execute([$decoded['userId'], $decoded['mobile']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    return $user;
}

function isAdmin($user) {
    // Simple admin check - can be expanded
    return $user['id'] == 1; // User ID 1 is admin
}
