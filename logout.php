<?php
/**
 * User Logout
 * NFR-03: Secure session management
 */

require_once 'config.php';
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $db->logTransaction($_SESSION['user_id'], 'user_logout', 'User logged out', 'success');
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
