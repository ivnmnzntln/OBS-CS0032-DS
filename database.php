<?php
/**
 * Database Connection Class
 * MLP: Minimum Lovable Product
 * NFR-02: Performance - Connection pooling
 * NFR-09: Reliability - Error logging
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true // Connection pooling for performance
                ]
            );
        } catch(PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
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
    
    /**
     * Log errors to file (NFR-09)
     */
    private function logError($message) {
        $logFile = __DIR__ . '/logs/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        if (!file_exists(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Log transaction (NFR-09)
     */
    public function logTransaction($userId, $action, $details, $status = 'success') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO transaction_logs (user_id, action, details, status, ip_address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $details,
                $status,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch(PDOException $e) {
            $this->logError("Transaction log failed: " . $e->getMessage());
        }
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
