<?php
/**
 * Configuration File - Online Bookstore
 * MLP: Minimum Lovable Product
 * Date: January 29, 2026
 * 
 * NFR-03: Security Configuration
 * NFR-05: HTTPS Enforcement
 * NFR-12: Maintainability
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookstore_db');

// Application Configuration
define('SITE_NAME', 'Online Bookstore');
define('SITE_URL', 'http://localhost/bookstore');
define('ADMIN_EMAIL', 'admin@bookstore.com');

// Security Configuration (NFR-03, NFR-05)
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_COST', 10); // bcrypt cost

// Tax Configuration (FR-07)
define('TAX_RATE', 0.08); // 8% tax rate

// Pagination
define('ITEMS_PER_PAGE', 12);

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration (NFR-03)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production
ini_set('session.cookie_samesite', 'Strict');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-logout after inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
$_SESSION['last_activity'] = time();

// Timezone
date_default_timezone_set('UTC');
?>
