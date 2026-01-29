<?php
/**
 * Admin Dashboard - FR-11, FR-12
 * Manage books and orders
 */

require_once '../config.php';
require_once '../database.php';

// Require admin login
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Total users
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['users'] = $stmt->fetch()['total'];

// Total books
$stmt = $conn->query("SELECT COUNT(*) as total FROM books");
$stats['books'] = $stmt->fetch()['total'];

// Total orders
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Recent orders
$stmt = $conn->query("
    SELECT o.*, u.email, u.full_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 10
");
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php $pageTitle = 'Dashboard'; include 'header.php'; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info">
                        <h3>Total Books</h3>
                        <p class="stat-value"><?php echo $stats['books']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p class="stat-value"><?php echo $stats['orders']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p class="stat-value"><?php echo $stats['users']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="recent-section">
                <h2>Recent Orders</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name'], ENT_QUOTES); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php include 'footer.php'; ?>
