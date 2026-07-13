<?php
// config/config.php - Application Configuration

namespace PakPaisa\Config;

use Dotenv\Dotenv;

class Config {
    private static $instance = null;
    private $settings = [];
    
    private function __construct() {
        // Load .env file
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }
        
        $this->settings = [
            'app_name' => $_ENV['APP_NAME'] ?? 'Pak Paisa',
            'app_env' => $_ENV['APP_ENV'] ?? 'development',
            'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost:5000',
            
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-key',
            'jwt_expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 604800),
            
            'testing_mode' => filter_var($_ENV['TESTING_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'mock_balance' => (float)($_ENV['MOCK_BALANCE'] ?? 125750.00),
            
            'bank_account' => $_ENV['BANK_ACCOUNT'] ?? '',
            'bank_name' => $_ENV['BANK_NAME'] ?? '',
            'bank_iban' => $_ENV['BANK_IBAN'] ?? '',
            'bank_api_key' => $_ENV['BANK_API_KEY'] ?? '',
            'bank_api_secret' => $_ENV['BANK_API_SECRET'] ?? '',
            'bank_api_url' => $_ENV['BANK_API_URL'] ?? '',
            
            'twilio_sid' => $_ENV['TWILIO_SID'] ?? '',
            'twilio_token' => $_ENV['TWILIO_TOKEN'] ?? '',
            'twilio_phone' => $_ENV['TWILIO_PHONE'] ?? '',
            
            'allowed_origins' => explode(',', $_ENV['ALLOWED_ORIGINS'] ?? 'http://localhost:3000')
        ];
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    
    public function isTesting() {
        return $this->get('testing_mode', true);
    }
}
