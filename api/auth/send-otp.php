<?php
// api/auth/send-otp.php - Send OTP

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

// Check if user exists
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id FROM users WHERE mobile = ?");
$stmt->execute([$validatedMobile]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['error' => 'User not found'], 404);
}

// Generate OTP
$otp = generateOTP();

// Store OTP in database
$stmt = $db->prepare("
    INSERT INTO otp_cache (mobile, otp) 
    VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE 
    otp = VALUES(otp), 
    attempts = 0,
    updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([$validatedMobile, $otp]);

// Send OTP via SMS
$message = "Your Pak Paisa OTP is: " . $otp . ". Valid for 5 minutes.";
$sent = sendSMS($validatedMobile, $message);

// Response
$response = [
    'success' => $sent,
    'message' => $sent ? 'OTP sent successfully!' : 'Failed to send OTP',
    'mobile' => $validatedMobile
];

// Add test OTP in testing mode
if ($sent && defined('TESTING_MODE') && TESTING_MODE) {
    $response['testOtp'] = $otp;
}

jsonResponse($response, $sent ? 200 : 500);
