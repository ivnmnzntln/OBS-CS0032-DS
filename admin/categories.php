<?php
/**
 * Admin - Category Management
 * FR-11: Admin functions
 * NFR-13: Scalability - Easy category management
 */
require_once '../config.php';
require_once '../database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$pageTitle = 'Manage Categories';
$message = '';
$messageType = '';

// Get all distinct categories with book counts
$stmt = $db->query("
    SELECT category, COUNT(*) as book_count, 
           SUM(stock_quantity) as total_stock,
           AVG(price) as avg_price
    FROM books 
    GROUP BY category 
    ORDER BY category ASC
");
$categories = $stmt->fetchAll();

include 'header.php';
?>

<div class="admin-page-header">
    <h2>Category Management</h2>
    <div class="page-actions">
        <span class="category-count">Total Categories: <?php echo count($categories); ?></span>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="info-box">
    <p><strong>Note:</strong> Categories are automatically managed based on the books you add. 
    To add a new category, simply add a book with that category name.</p>
</div>

<!-- Categories Grid -->
<div class="categories-grid">
    <?php foreach ($categories as $cat): ?>
        <div class="category-card">
            <div class="category-header">
                <h3><?php echo htmlspecialchars($cat['category']); ?></h3>
                <span class="badge badge-primary"><?php echo $cat['book_count']; ?> books</span>
            </div>
            <div class="category-stats">
                <div class="stat">
                    <label>Total Stock:</label>
                    <span><?php echo $cat['total_stock']; ?> units</span>
                </div>
                <div class="stat">
                    <label>Avg Price:</label>
                    <span>$<?php echo number_format($cat['avg_price'], 2); ?></span>
                </div>
            </div>
            <div class="category-actions">
                <a href="books.php?category=<?php echo urlencode($cat['category']); ?>" 
                   class="btn btn-sm btn-primary">View Books</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add New Category Instructions -->
<div class="card mt-4">
    <h3>How to Add New Categories</h3>
    <ol>
        <li>Go to <a href="books.php">Manage Books</a></li>
        <li>Click "Add New Book"</li>
        <li>Enter a new category name in the Category field</li>
        <li>Save the book - the category will be created automatically</li>
    </ol>
    <p class="text-muted">This design ensures scalability (NFR-13) as categories grow with your catalog.</p>
</div>

<style>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.category-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.category-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #333;
}

.category-stats {
    margin: 15px 0;
}

.category-stats .stat {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.category-stats label {
    font-weight: 500;
    color: #666;
}

.category-actions {
    margin-top: 15px;
    text-align: center;
}

.info-box {
    background: #e7f3ff;
    border-left: 4px solid #2196F3;
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
}

.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card h3 {
    margin-top: 0;
    color: #333;
}

.card ol {
    padding-left: 20px;
}

.card li {
    margin: 10px 0;
}

.mt-4 {
    margin-top: 30px;
}

.text-muted {
    color: #666;
    font-style: italic;
}
</style>

<?php include 'footer.php'; ?>
