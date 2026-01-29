<?php
/**
 * Shopping Cart - FR-06, FR-07
 * Add/Remove items, Calculate total with tax
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
$message = '';
$error = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $book_id = intval($_POST['book_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    try {
        if ($action === 'add' && $book_id > 0) {
            // Check if book exists and has stock
            $stmt = $conn->prepare("SELECT stock_quantity FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if ($book && $book['stock_quantity'] >= $quantity) {
                // Check if item already in cart
                $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$user_id, $book_id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update quantity
                    $new_quantity = $existing['quantity'] + $quantity;
                    if ($new_quantity <= $book['stock_quantity']) {
                        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
                        $stmt->execute([$new_quantity, $user_id, $book_id]);
                        $message = 'Cart updated successfully!';
                    } else {
                        $error = 'Not enough stock available.';
                    }
                } else {
                    // Add new item
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $book_id, $quantity]);
                    $message = 'Item added to cart!';
                }
                
                $db->logTransaction($user_id, 'cart_add', "Added book $book_id to cart", 'success');
            } else {
                $error = 'Item not available or insufficient stock.';
            }
        } elseif ($action === 'update' && $book_id > 0) {
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$quantity, $user_id, $book_id]);
                $message = 'Cart updated!';
            } else {
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
                $stmt->execute([$user_id, $book_id]);
                $message = 'Item removed from cart!';
            }
        } elseif ($action === 'remove' && $book_id > 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$user_id, $book_id]);
            $message = 'Item removed from cart!';
            $db->logTransaction($user_id, 'cart_remove', "Removed book $book_id from cart", 'success');
        }
    } catch(PDOException $e) {
        $error = 'Operation failed. Please try again.';
        $db->logTransaction($user_id, 'cart_error', $e->getMessage(), 'failed');
    }
}

// Get cart items
$stmt = $conn->prepare("
    SELECT c.*, b.title, b.author, b.price, b.cover_image, b.stock_quantity
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate totals (FR-07)
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * TAX_RATE;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Shopping Cart</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty.</p>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-item-info">
                                            <img src="images/<?php echo htmlspecialchars($item['cover_image'], ENT_QUOTES); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?>"
                                                 onerror="this.src='images/placeholder.jpg'">
                                            <div>
                                                <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES); ?></h3>
                                                <p><?php echo htmlspecialchars($item['author'], ENT_QUOTES); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form method="POST" action="" class="quantity-form">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="0" max="<?php echo $item['stock_quantity']; ?>" 
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td>
                                        <form method="POST" action="" class="inline-form">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <h2>Order Summary</h2>
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
                    <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                    <a href="index.php" class="btn btn-outline btn-block">Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
