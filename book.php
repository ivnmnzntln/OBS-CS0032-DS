<?php
/**
 * Book Details Page - FR-05
 * Display detailed book information
 */

require_once 'config.php';
require_once 'database.php';

$book_id = intval($_GET['id'] ?? 0);

if (!$book_id) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(substr($book['description'], 0, 160), ENT_QUOTES); ?>">
    <title><?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <nav aria-label="Breadcrumb" class="breadcrumb">
            <a href="index.php">Home</a> &gt; 
            <a href="index.php?category=<?php echo urlencode($book['category']); ?>">
                <?php echo htmlspecialchars($book['category'], ENT_QUOTES); ?>
            </a> &gt; 
            <span><?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?></span>
        </nav>
        
        <article class="book-details">
            <div class="book-details-grid">
                <div class="book-details-image">
                    <img src="images/<?php echo htmlspecialchars($book['cover_image'], ENT_QUOTES); ?>" 
                         alt="Cover of <?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>"
                         onerror="this.src='images/placeholder.jpg'">
                </div>
                
                <div class="book-details-content">
                    <h1><?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?></h1>
                    <p class="book-author">by <strong><?php echo htmlspecialchars($book['author'], ENT_QUOTES); ?></strong></p>
                    
                    <div class="book-meta">
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category'], ENT_QUOTES); ?></p>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'], ENT_QUOTES); ?></p>
                        <p><strong>Availability:</strong> 
                            <?php if ($book['stock_quantity'] > 0): ?>
                                <span class="in-stock">In Stock (<?php echo $book['stock_quantity']; ?> available)</span>
                            <?php else: ?>
                                <span class="out-of-stock">Out of Stock</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="book-description">
                        <h2>Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($book['description'], ENT_QUOTES)); ?></p>
                    </div>
                    
                    <div class="book-purchase">
                        <p class="book-price-large">$<?php echo number_format($book['price'], 2); ?></p>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($book['stock_quantity'] > 0): ?>
                                <form method="POST" action="cart.php" class="add-to-cart-form">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <label for="quantity">Quantity:</label>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $book['stock_quantity']; ?>">
                                    <button type="submit" class="btn btn-primary btn-large">Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-disabled btn-large" disabled>Out of Stock</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-large">Login to Purchase</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
