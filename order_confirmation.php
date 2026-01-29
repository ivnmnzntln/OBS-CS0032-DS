<?php
/**
 * Order Confirmation Page - FR-09
 * Display order confirmation after successful purchase
 * Note: In production, send email confirmation (FR-09)
 */

require_once 'config.php';
require_once 'database.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.email, u.full_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, b.title, b.author
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Note: In production, send email confirmation here (FR-09)
// mail($order['email'], 'Order Confirmation', $email_body);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="order-confirmation">
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <p class="confirmation-message">Thank you for your order. We'll send you an email confirmation shortly.</p>
            
            <div class="order-details-box">
                <h2>Order Details</h2>
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <strong>Order Number:</strong>
                        <span>#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="order-info-item">
                        <strong>Order Date:</strong>
                        <span><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="order-info-item">
                        <strong>Tracking Number:</strong>
                        <span><?php echo htmlspecialchars($order['tracking_number'], ENT_QUOTES); ?></span>
                    </div>
                    <div class="order-info-item">
                        <strong>Order Status:</strong>
                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="shipping-info">
                    <h3>Shipping Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'], ENT_QUOTES)); ?></p>
                </div>
                
                <div class="order-items-list">
                    <h3>Items Ordered</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></strong><br>
                                        <small>by <?php echo htmlspecialchars($item['author'], ENT_QUOTES); ?></small>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($order['total_amount'] - $order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="total-row total-final">
                        <span>Total:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-actions">
                <a href="orders.php" class="btn btn-primary">View My Orders</a>
                <a href="index.php" class="btn btn-outline">Continue Shopping</a>
            </div>
            
            <div class="email-note">
                <p><strong>Note:</strong> In production, an order confirmation email would be sent to <?php echo htmlspecialchars($order['email'], ENT_QUOTES); ?> (FR-09)</p>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
