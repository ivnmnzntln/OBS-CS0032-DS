<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Skip to main content for accessibility (NFR-06) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation menu">
                ☰
            </button>
            
            <nav>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo SITE_URL; ?>/cart.php">Cart</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a></li>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li><a href="<?php echo SITE_URL; ?>/admin/">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main id="main-content">
