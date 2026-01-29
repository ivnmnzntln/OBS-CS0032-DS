<?php
/**
 * Homepage - Book Browsing
 * FR-03: Display books in categories
 * FR-04: Search functionality
 * NFR-01: Load under 2 seconds
 */

require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Build query
$where_conditions = [];
$params = [];

if ($category) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if ($search) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM books $where_clause";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_books = $stmt->fetch()['total'];
$total_pages = ceil($total_books / ITEMS_PER_PAGE);

// Get books
$sql = "SELECT * FROM books $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = $offset;
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories
$categories_stmt = $conn->query("SELECT DISTINCT category FROM books ORDER BY category");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our collection of books across various categories">
    <title><?php echo SITE_NAME; ?> - Browse Books</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>Browse Books</h1>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="filters-section">
            <form method="GET" action="" class="search-form">
                <div class="search-group">
                    <label for="search" class="sr-only">Search books by title, author, or ISBN</label>
                    <input type="search" 
                           id="search" 
                           name="search" 
                           placeholder="Search by title, author, or ISBN..." 
                           value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>"
                           aria-label="Search books">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
            
            <div class="category-filter">
                <label for="category-select">Filter by category:</label>
                <div class="category-buttons">
                    <a href="index.php" class="btn <?php echo !$category ? 'btn-active' : 'btn-outline'; ?>">All</a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?php echo urlencode($cat); ?>" 
                           class="btn <?php echo $category === $cat ? 'btn-active' : 'btn-outline'; ?>">
                            <?php echo htmlspecialchars($cat, ENT_QUOTES); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Results Summary -->
        <div class="results-summary">
            <p>Showing <?php echo count($books); ?> of <?php echo $total_books; ?> books</p>
        </div>
        
        <!-- Books Grid -->
        <div class="books-grid">
            <?php if (empty($books)): ?>
                <div class="no-results">
                    <p>No books found. Try adjusting your search or filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <article class="book-card">
                        <div class="book-image">
                            <img src="images/<?php echo htmlspecialchars($book['cover_image'], ENT_QUOTES); ?>" 
                                 alt="Cover of <?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>"
                                 onerror="this.src='images/placeholder.jpg'">
                        </div>
                        <div class="book-info">
                            <h3 class="book-title">
                                <a href="book.php?id=<?php echo $book['id']; ?>">
                                    <?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>
                                </a>
                            </h3>
                            <p class="book-author">by <?php echo htmlspecialchars($book['author'], ENT_QUOTES); ?></p>
                            <p class="book-category"><?php echo htmlspecialchars($book['category'], ENT_QUOTES); ?></p>
                            <p class="book-price">$<?php echo number_format($book['price'], 2); ?></p>
                            <div class="book-actions">
                                <a href="book.php?id=<?php echo $book['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <form method="POST" action="cart.php" class="inline-form">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Add to Cart</button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary btn-sm">Login to Buy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Page navigation">
                <ul>
                    <?php if ($page > 1): ?>
                        <li><a href="?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Previous</a></li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li><a href="?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
