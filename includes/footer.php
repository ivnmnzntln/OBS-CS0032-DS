    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            <p>Built with MLP (Minimum Lovable Product) approach</p>
            <p>
                <a href="<?php echo SITE_URL; ?>/index.php">Home</a> | 
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/">Admin Panel</a> |
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/orders.php">Orders</a> | 
                <a href="<?php echo SITE_URL; ?>/cart.php">Cart</a>
            </p>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const navMenu = document.getElementById('navMenu');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
            });
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-container')) {
                navMenu?.classList.remove('active');
            }
        });
    </script>
</body>
</html>
