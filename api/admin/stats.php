<?php
// api/admin/stats.php - Admin Stats

use PakPaisa\Config\Database;
use PakPaisa\Includes\{jsonResponse};

$db = Database::getInstance()->getConnection();

// Get stats
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as active FROM users WHERE card_active = 1");
$activeUsers = $stmt->fetch()['active'];

$stmt = $db->query("SELECT COUNT(*) as total FROM transactions");
$totalTransactions = $stmt->fetch()['total'];

$stmt = $db->query("SELECT SUM(amount) as revenue FROM transactions");
$revenue = $stmt->fetch()['revenue'] ?? 0;

jsonResponse([
    'success' => true,
    'stats' => [
        'totalUsers' => (int)$totalUsers,
        'activeUsers' => (int)$activeUsers,
        'totalTransactions' => (int)$totalTransactions,
        'totalRevenue' => (float)$revenue
    ]
]);
