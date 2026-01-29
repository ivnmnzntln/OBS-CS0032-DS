<?php
/**
 * Checkout Page - FR-08
 * Place orders with shipping and payment details
 * NFR-04: Payment gateway integration ready
 * NFR-09: Transaction logging
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
$error = '';
$success = '';

// Get cart items
$stmt = $conn->prepare("
    SELECT c.*, b.title, b.author, b.price, b.stock_quantity
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * TAX_RATE;
$total = $subtotal + $tax;

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $payment_method = htmlspecialchars($_POST['payment_method'] ?? '', ENT_QUOTES, 'UTF-8');
    
    if (empty($shipping_address) || empty($payment_method)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $conn->beginTransaction();
            
            // Verify stock availability
            $stock_ok = true;
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("SELECT stock_quantity FROM books WHERE id = ? FOR UPDATE");
                $stmt->execute([$item['book_id']]);
                $current_stock = $stmt->fetch()['stock_quantity'];
                
                if ($current_stock < $item['quantity']) {
                    $stock_ok = false;
                    $error = "Insufficient stock for: " . $item['title'];
                    break;
                }
            }
            
            if ($stock_ok) {
                // Create order
                $tracking_number = 'TRK' . time() . rand(1000, 9999);
                $stmt = $conn->prepare("
                    INSERT INTO orders (user_id, total_amount, tax_amount, shipping_address, payment_method, tracking_number, order_status, payment_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')
                ");
                $stmt->execute([$user_id, $total, $tax, $shipping_address, $payment_method, $tracking_number]);
                $order_id = $conn->lastInsertId();
                
                // Create order items and update stock
                foreach ($cart_items as $item) {
                    // Add to order items
                    $stmt = $conn->prepare("
                        INSERT INTO order_items (order_id, book_id, quantity, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$order_id, $item['book_id'], $item['quantity'], $item['price']]);
                    
                    // Update stock
                    $stmt = $conn->prepare("
                        UPDATE books SET stock_quantity = stock_quantity - ? WHERE id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['book_id']]);
                }
                
                // Clear cart
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Log transaction
                $db->logTransaction($user_id, 'order_placed', "Order #$order_id placed - Total: $" . number_format($total, 2), 'success');
                
                $conn->commit();
                
                // Redirect to order confirmation
                header("Location: order_confirmation.php?order_id=$order_id");
                exit();
            } else {
                $conn->rollBack();
            }
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = 'Order placement failed. Please try again.';
            $db->logTransaction($user_id, 'order_failed', $e->getMessage(), 'failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Checkout</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <h2>Shipping & Payment Information</h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="shipping_address">Shipping Address <span class="required">*</span></label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required><?php echo htmlspecialchars($user['address'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                        <small>Note: Payment gateway integration (Stripe/PayPal) ready for production (NFR-04)</small>
                    </div>
                    
                    <div class="form-note">
                        <p><strong>Note:</strong> In production, this would integrate with a PCI-DSS compliant payment gateway like Stripe or PayPal for secure payment processing (NFR-04).</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">Place Order</button>
                </form>
            </div>
            
            <div class="checkout-summary">
                <h2>Order Summary</h2>
                
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="order-item-info">
                                <h4><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></h4>
                                <p>Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            <div class="order-item-price">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (<?php echo (TAX_RATE * 100); ?>%):</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
