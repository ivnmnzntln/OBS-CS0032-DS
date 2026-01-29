<?php
/**
 * Admin - User Management
 * FR-11: Admin functions
 * NFR-03: Security
 */
require_once '../config.php';
require_once '../database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$pageTitle = 'Manage Users';
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_admin':
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $isAdmin = filter_input(INPUT_POST, 'is_admin', FILTER_VALIDATE_INT);
                
                if ($userId && $userId != $_SESSION['user_id']) {
                    $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
                    if ($stmt->execute([!$isAdmin, $userId])) {
                        $message = 'User privileges updated successfully';
                        $messageType = 'success';
                        Database::getInstance()->logTransaction($_SESSION['user_id'], 'toggle_admin', "User ID: $userId");
                    }
                }
                break;
                
            case 'delete_user':
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                
                if ($userId && $userId != $_SESSION['user_id']) {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
                    if ($stmt->execute([$userId])) {
                        $message = 'User deleted successfully';
                        $messageType = 'success';
                        Database::getInstance()->logTransaction($_SESSION['user_id'], 'delete_user', "User ID: $userId");
                    }
                }
                break;
        }
    }
}

// Get all users
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

$whereClause = '';
$params = [];
if ($searchTerm) {
    $whereClause = "WHERE email LIKE ? OR full_name LIKE ?";
    $searchPattern = "%$searchTerm%";
    $params = [$searchPattern, $searchPattern];
}

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM users $whereClause");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / ITEMS_PER_PAGE);

// Get users
$stmt = $db->prepare("
    SELECT id, email, full_name, phone, is_admin, created_at,
           (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as order_count
    FROM users
    $whereClause
    ORDER BY created_at DESC
    LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

include 'header.php';
?>

<div class="admin-page-header">
    <h2>User Management</h2>
    <div class="page-actions">
        <span class="user-count">Total Users: <?php echo $totalUsers; ?></span>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Search -->
<div class="search-box">
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search by name or email..." 
               value="<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Search users">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($searchTerm): ?>
            <a href="users.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Orders</th>
                <th>Admin</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center">No users found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo $user['order_count']; ?></td>
                        <td>
                            <span class="badge <?php echo $user['is_admin'] ? 'badge-success' : 'badge-secondary'; ?>">
                                <?php echo $user['is_admin'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td class="actions">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_admin" value="<?php echo $user['is_admin']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                    </button>
                                </form>
                                
                                <?php if (!$user['is_admin']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger delete-btn">Delete</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" 
               class="btn btn-secondary">Previous</a>
        <?php endif; ?>
        
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" 
               class="btn btn-secondary">Next</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
