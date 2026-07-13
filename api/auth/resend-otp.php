<?php
// api/auth/resend-otp.php - Resend OTP

use PakPaisa\Config\Database;
use PakPaisa\Includes\{jsonResponse, generateOTP, validateMobile, sendSMS};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$mobile = $data['mobile'] ?? '';

if (empty($mobile)) {
    jsonResponse(['error' => 'Mobile number is required'], 400);
}

$validatedMobile = validateMobile($mobile);
if (!$validatedMobile) {
    jsonResponse(['error' => 'Invalid mobile number'], 400);
}

$db = Database::getInstance()->getConnection();

// Check if user exists
$stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$validatedMobile]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'User not found'], 404);
}

// Generate new OTP
$otp = generateOTP();

// Store OTP
$stmt = $db->prepare("
    INSERT INTO otp_cache (mobile, otp, attempts) 
    VALUES (?, ?, 0) 
    ON DUPLICATE KEY UPDATE 
    otp = VALUES(otp), 
    attempts = 0,
    updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([$validatedMobile, $otp]);

// Send OTP
$message = "Your Pak Paisa OTP is: " . $otp . ". Valid for 5 minutes.";
$sent = sendSMS($validatedMobile, $message);

$response = [
    'success' => $sent,
    'message' => $sent ? 'New OTP sent!' : 'Failed to send OTP'
];

if ($sent && defined('TESTING_MODE') && TESTING_MODE) {
    $response['testOtp'] = $otp;
}

jsonResponse($response, $sent ? 200 : 500);
