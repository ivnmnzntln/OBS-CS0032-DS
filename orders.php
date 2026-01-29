<?php
/**
 * Order History - FR-10
 * View order history and current status
 */

require_once 'config.php';
require_once 'database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get user orders
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="index.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <article class="order-card">
                        <div class="order-header">
                            <div class="order-header-left">
                                <h3>Order #<?php echo $order['id']; ?></h3>
                                <p class="order-date"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="order-header-right">
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-info">
                                <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                                <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                                <p><strong>Tracking:</strong> <?php echo htmlspecialchars($order['tracking_number'], ENT_QUOTES); ?></p>
                                <p><strong>Payment:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            </div>
                            
                            <div class="order-status-timeline">
                                <div class="status-step <?php echo in_array($order['order_status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-label">Pending</div>
                                </div>
                                <div class="status-step <?php echo in_array($order['order_status'], ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-label">Processing</div>
                                </div>
                                <div class="status-step <?php echo in_array($order['order_status'], ['shipped', 'delivered']) ? 'active' : ''; ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-label">Shipped</div>
                                </div>
                                <div class="status-step <?php echo $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-label">Delivered</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
