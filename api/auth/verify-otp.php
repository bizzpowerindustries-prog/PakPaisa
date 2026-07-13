<?php
// api/auth/verify-otp.php - Verify OTP & Login

use PakPaisa\Config\Database;
use PakPaisa\Includes\{jsonResponse, validateMobile, generateToken};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$mobile = $data['mobile'] ?? '';
$otp = $data['otp'] ?? '';

if (empty($mobile) || empty($otp)) {
    jsonResponse(['error' => 'Mobile and OTP are required'], 400);
}

$validatedMobile = validateMobile($mobile);
if (!$validatedMobile) {
    jsonResponse(['error' => 'Invalid mobile number'], 400);
}

if (strlen($otp) !== 6 || !is_numeric($otp)) {
    jsonResponse(['error' => 'Invalid OTP format'], 400);
}

$db = Database::getInstance()->getConnection();

// Get stored OTP
$stmt = $db->prepare("SELECT otp, attempts FROM otp_cache WHERE mobile = ?");
$stmt->execute([$validatedMobile]);
$stored = $stmt->fetch();

if (!$stored) {
    jsonResponse(['error' => 'OTP expired. Please request new OTP'], 400);
}

// Check attempts
if ($stored['attempts'] >= 3) {
    $stmt = $db->prepare("DELETE FROM otp_cache WHERE mobile = ?");
    $stmt->execute([$validatedMobile]);
    jsonResponse(['error' => 'Too many attempts. Please request new OTP'], 400);
}

// Verify OTP
if ($stored['otp'] !== $otp) {
    // Increment attempts
    $stmt = $db->prepare("UPDATE otp_cache SET attempts = attempts + 1 WHERE mobile = ?");
    $stmt->execute([$validatedMobile]);
    jsonResponse(['error' => 'Invalid OTP. ' . (3 - ($stored['attempts'] + 1)) . ' attempts remaining'], 401);
}

// OTP verified - Get user
$stmt = $db->prepare("SELECT * FROM users WHERE mobile = ?");
$stmt->execute([$validatedMobile]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'User not found'], 404);
}

// Delete used OTP
$stmt = $db->prepare("DELETE FROM otp_cache WHERE mobile = ?");
$stmt->execute([$validatedMobile]);

// Generate JWT
$token = generateToken($validatedMobile, $user['id']);

jsonResponse([
    'success' => true,
    'message' => 'Login successful!',
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'mobile' => $user['mobile'],
        'walletBalance' => (float)$user['wallet_balance'],
        'walletLimit' => (float)$user['wallet_limit'],
        'kycVerified' => (bool)$user['kyc_verified'],
        'cardActive' => (bool)$user['card_active']
    ]
]);
