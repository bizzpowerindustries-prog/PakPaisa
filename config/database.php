<?php
// config/database.php - Database Configuration

namespace PakPaisa\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $conn;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'pakpaisa';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
        $this->port = $_ENV['DB_PORT'] ?? 3306;
        
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Create tables if not exists
    public function setupTables() {
        $sqls = [
            // Users Table
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                mobile VARCHAR(15) UNIQUE NOT NULL,
                pin VARCHAR(255) NOT NULL,
                wallet_balance DECIMAL(15,2) DEFAULT 0.00,
                wallet_limit DECIMAL(15,2) DEFAULT 500000.00,
                kyc_verified BOOLEAN DEFAULT FALSE,
                card_active BOOLEAN DEFAULT TRUE,
                card_number VARCHAR(20),
                card_expiry VARCHAR(10),
                card_cvv VARCHAR(10),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Transactions Table
            "CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                type VARCHAR(50) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                status VARCHAR(20) DEFAULT 'PENDING',
                reference VARCHAR(50) UNIQUE NOT NULL,
                description TEXT,
                recipient_id BIGINT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (recipient_id) REFERENCES users(id)
            )",
            
            // OTP Cache Table
            "CREATE TABLE IF NOT EXISTS otp_cache (
                mobile VARCHAR(15) PRIMARY KEY,
                otp VARCHAR(6) NOT NULL,
                attempts INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($sqls as $sql) {
            $this->conn->exec($sql);
        }
    }
}
