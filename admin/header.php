<?php
/**
 * Admin Header with Navigation
 * NFR-06: Accessibility compliance
 * NFR-10, NFR-11: Responsive design
 */
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Panel - Online Bookstore Management">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <nav class="sidebar-nav" role="navigation">
                <ul>
                    <li>
                        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <span class="icon">📊</span>
                            <span class="text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="books.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>">
                            <span class="icon">📚</span>
                            <span class="text">Manage Books</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                            <span class="icon">📦</span>
                            <span class="text">Manage Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <span class="icon">👥</span>
                            <span class="text">Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                            <span class="icon">🏷️</span>
                            <span class="text">Categories</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <span class="icon">📈</span>
                            <span class="text">Reports</span>
                        </a>
                    </li>
                    <li class="separator"></li>
                    <li>
                        <a href="../index.php">
                            <span class="icon">🏠</span>
                            <span class="text">View Store</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <span class="icon">🚪</span>
                            <span class="text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <h1 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                <div class="topbar-right">
                    <span class="admin-user">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                    </span>
                </div>
            </header>

            <!-- Content -->
            <div class="admin-content">
