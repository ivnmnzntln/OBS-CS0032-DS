<?php
/**
 * Admin - Reports & Analytics
 * FR-12: Admin order viewing
 * NFR-02: Performance monitoring
 */
require_once '../config.php';
require_once '../database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance()->getConnection();
$pageTitle = 'Reports & Analytics';

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Sales statistics
$salesStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        SUM(tax_amount) as total_tax
    FROM orders 
    WHERE order_date BETWEEN ? AND ?
");
$salesStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$salesStats = $salesStmt->fetch();

// Orders by status
$statusStmt = $db->prepare("
    SELECT order_status, COUNT(*) as count 
    FROM orders 
    WHERE order_date BETWEEN ? AND ?
    GROUP BY order_status
");
$statusStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$statusData = $statusStmt->fetchAll();

// Top selling books
$topBooksStmt = $db->prepare("
    SELECT b.title, b.author, b.category, 
           SUM(oi.quantity) as units_sold,
           SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY oi.book_id
    ORDER BY units_sold DESC
    LIMIT 10
");
$topBooksStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$topBooks = $topBooksStmt->fetchAll();

// Category performance
$categoryStmt = $db->prepare("
    SELECT b.category, 
           COUNT(DISTINCT oi.order_id) as orders,
           SUM(oi.quantity) as units_sold,
           SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date BETWEEN ? AND ?
    GROUP BY b.category
    ORDER BY revenue DESC
");
$categoryStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$categoryPerformance = $categoryStmt->fetchAll();

// Daily sales trend
$trendStmt = $db->prepare("
    SELECT DATE(order_date) as order_day,
           COUNT(*) as orders,
           SUM(total_amount) as revenue
    FROM orders
    WHERE order_date BETWEEN ? AND ?
    GROUP BY DATE(order_date)
    ORDER BY order_day ASC
");
$trendStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$salesTrend = $trendStmt->fetchAll();

include 'header.php';
?>

<div class="admin-page-header">
    <h2>Reports & Analytics</h2>
</div>

<!-- Date Range Filter -->
<div class="card mb-3">
    <form method="GET" class="date-filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Sales Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <p class="stat-value">$<?php echo number_format($salesStats['total_revenue'] ?? 0, 2); ?></p>
        <p class="stat-label">Total Sales</p>
    </div>
    <div class="stat-card">
        <h3>Total Orders</h3>
        <p class="stat-value"><?php echo number_format($salesStats['total_orders'] ?? 0); ?></p>
        <p class="stat-label">Orders Placed</p>
    </div>
    <div class="stat-card">
        <h3>Average Order</h3>
        <p class="stat-value">$<?php echo number_format($salesStats['avg_order_value'] ?? 0, 2); ?></p>
        <p class="stat-label">Per Order</p>
    </div>
    <div class="stat-card">
        <h3>Tax Collected</h3>
        <p class="stat-value">$<?php echo number_format($salesStats['total_tax'] ?? 0, 2); ?></p>
        <p class="stat-label">Total Tax</p>
    </div>
</div>

<!-- Orders by Status -->
<div class="card mt-3">
    <h3>Orders by Status</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statusData as $status): ?>
                    <tr>
                        <td><span class="badge badge-<?php echo $status['order_status']; ?>"><?php echo ucfirst($status['order_status']); ?></span></td>
                        <td><?php echo $status['count']; ?></td>
                        <td><?php echo number_format(($status['count'] / ($salesStats['total_orders'] ?: 1)) * 100, 1); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Selling Books -->
<div class="card mt-3">
    <h3>Top 10 Selling Books</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topBooks)): ?>
                    <tr><td colspan="5" class="text-center">No sales data</td></tr>
                <?php else: ?>
                    <?php foreach ($topBooks as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                            <td><?php echo $book['units_sold']; ?></td>
                            <td>$<?php echo number_format($book['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Performance -->
<div class="card mt-3">
    <h3>Category Performance</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Orders</th>
                    <th>Units Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categoryPerformance)): ?>
                    <tr><td colspan="4" class="text-center">No sales data</td></tr>
                <?php else: ?>
                    <?php foreach ($categoryPerformance as $cat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['category']); ?></strong></td>
                            <td><?php echo $cat['orders']; ?></td>
                            <td><?php echo $cat['units_sold']; ?></td>
                            <td>$<?php echo number_format($cat['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sales Trend Chart -->
<?php if (!empty($salesTrend)): ?>
<div class="card mt-3">
    <h3>Daily Sales Trend</h3>
    <div class="chart-container">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($salesTrend, 'order_day')); ?>,
        datasets: [{
            label: 'Daily Revenue',
            data: <?php echo json_encode(array_column($salesTrend, 'revenue')); ?>,
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33, 150, 243, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(2);
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<style>
.date-filter-form .form-row {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.date-filter-form .form-group {
    flex: 1;
    min-width: 200px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-card:nth-child(2) {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card:nth-child(3) {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card:nth-child(4) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 1rem;
    opacity: 0.9;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 10px 0;
}

.stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.chart-container {
    position: relative;
    height: 400px;
    padding: 20px;
}

.mb-3 {
    margin-bottom: 20px;
}

.mt-3 {
    margin-top: 20px;
}
</style>

<?php include 'footer.php'; ?>
