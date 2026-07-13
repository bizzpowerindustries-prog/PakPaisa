<?php
// includes/functions.php - Helper Functions

namespace PakPaisa\Includes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PakPaisa\Config\Config;

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function generateOTP() {
    return sprintf("%06d", random_int(100000, 999999));
}

function generateReference() {
    return 'TXN-' . date('Ymd') . '-' . uniqid();
}

function validateMobile($mobile) {
    // Remove spaces and special characters
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    
    // Add country code if missing (Pakistan)
    if (strlen($mobile) === 10) {
        $mobile = '92' . $mobile;
    }
    
    // Check if starts with 92 and has 12 digits
    if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '92') {
        return $mobile;
    }
    
    return false;
}

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData($data, $key) {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

function generateToken($mobile, $userId) {
    $config = Config::getInstance();
    $payload = [
        'userId' => $userId,
        'mobile' => $mobile,
        'iat' => time(),
        'exp' => time() + $config->get('jwt_expiry', 604800)
    ];
    
    return JWT::encode($payload, $config->get('jwt_secret'), 'HS256');
}

function verifyToken($token) {
    try {
        $config = Config::getInstance();
        $decoded = JWT::decode($token, new Key($config->get('jwt_secret'), 'HS256'));
        return (array)$decoded;
    } catch (Exception $e) {
        return false;
    }
}

function getBearerToken() {
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

function authenticate() {
    $token = getBearerToken();
    
    if (!$token) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $decoded = verifyToken($token);
    
    if (!$decoded) {
        jsonResponse(['error' => 'Invalid token'], 401);
    }
    
    return $decoded;
}

function sendSMS($mobile, $message) {
    $config = Config::getInstance();
    
    // Testing mode - log OTP
    if ($config->isTesting()) {
        error_log("📱 TEST MODE - SMS to $mobile: $message");
        return true;
    }
    
    try {
        $sid = $config->get('twilio_sid');
        $token = $config->get('twilio_token');
        $phone = $config->get('twilio_phone');
        
        if (empty($sid) || empty($token)) {
            return false;
        }
        
        $client = new \Twilio\Rest\Client($sid, $token);
        $client->messages->create(
            $mobile,
            [
                'from' => $phone,
                'body' => $message
            ]
        );
        
        return true;
    } catch (Exception $e) {
        error_log("❌ SMS Error: " . $e->getMessage());
        return false;
    }
}

function hashPin($pin) {
    return password_hash($pin, PASSWORD_BCRYPT);
}

function verifyPin($pin, $hash) {
    return password_verify($pin, $hash);
}
