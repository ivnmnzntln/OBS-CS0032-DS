<?php
/**
 * Admin Book Management - FR-11
 * Add, edit, remove books from catalog
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
$message = '';
$error = '';

// Handle book actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $title = htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($_POST['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $isbn = htmlspecialchars($_POST['isbn'] ?? '', ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
        $price = floatval($_POST['price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $cover_image = htmlspecialchars($_POST['cover_image'] ?? 'placeholder.jpg', ENT_QUOTES, 'UTF-8');
        
        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("
                    INSERT INTO books (title, author, isbn, category, description, price, stock_quantity, cover_image)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $author, $isbn, $category, $description, $price, $stock_quantity, $cover_image]);
                $message = 'Book added successfully!';
                $db->logTransaction($_SESSION['user_id'], 'book_added', "Added: $title", 'success');
            } elseif ($action === 'edit') {
                $book_id = intval($_POST['book_id']);
                $stmt = $conn->prepare("
                    UPDATE books SET title=?, author=?, isbn=?, category=?, description=?, price=?, stock_quantity=?, cover_image=?
                    WHERE id=?
                ");
                $stmt->execute([$title, $author, $isbn, $category, $description, $price, $stock_quantity, $cover_image, $book_id]);
                $message = 'Book updated successfully!';
                $db->logTransaction($_SESSION['user_id'], 'book_updated', "Updated: $title", 'success');
            }
        } catch(PDOException $e) {
            $error = 'Operation failed: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $book_id = intval($_POST['book_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $message = 'Book deleted successfully!';
            $db->logTransaction($_SESSION['user_id'], 'book_deleted', "Deleted book ID: $book_id", 'success');
        } catch(PDOException $e) {
            $error = 'Delete failed: ' . $e->getMessage();
        }
    }
}

// Get all books
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term];
}

if ($category_filter) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT * FROM books $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories
$categories = $conn->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php $pageTitle = 'Manage Books'; include 'header.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Manage Books</h1>
                <button onclick="showAddModal()" class="btn btn-primary">Add New Book</button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="filters">
                <form method="GET" action="" class="search-form">
                    <input type="search" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES); ?>" 
                                <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat, ENT_QUOTES); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($book['author'], ENT_QUOTES); ?></td>
                                <td><?php echo htmlspecialchars($book['category'], ENT_QUOTES); ?></td>
                                <td>$<?php echo number_format($book['price'], 2); ?></td>
                                <td><?php echo $book['stock_quantity']; ?></td>
                                <td>
                                    <button onclick='editBook(<?php echo json_encode($book); ?>)' class="btn btn-sm btn-outline">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="bookModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Book</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="book_id" id="bookId">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn">
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <input type="text" id="category" name="category" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Image Filename</label>
                    <input type="text" id="cover_image" name="cover_image" value="placeholder.jpg">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Book</button>
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Book';
            document.getElementById('formAction').value = 'add';
            document.getElementById('bookModal').style.display = 'block';
            document.querySelector('form').reset();
        }
        
        function editBook(book) {
            document.getElementById('modalTitle').textContent = 'Edit Book';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('bookId').value = book.id;
            document.getElementById('title').value = book.title;
            document.getElementById('author').value = book.author;
            document.getElementById('isbn').value = book.isbn;
            document.getElementById('category').value = book.category;
            document.getElementById('description').value = book.description;
            document.getElementById('price').value = book.price;
            document.getElementById('stock_quantity').value = book.stock_quantity;
            document.getElementById('cover_image').value = book.cover_image;
            document.getElementById('bookModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('bookModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('bookModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

<?php include 'footer.php'; ?>
