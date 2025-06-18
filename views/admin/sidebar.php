<aside class="sidebar">
    <h2><i class="fas fa-store" style="color: #4CAF50;"></i> eCommerce Admin</h2>
    <ul>
        <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt" style="color: #2196F3;"></i> Dashboard</a></li>

        <li><a href="manage products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage products.php' ? 'active' : '' ?>">
                <i class="fas fa-box-open" style="color: #FF9800;"></i> Manage Products</a></li>

        <li><a href="manage-orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage-orders.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart" style="color: #9C27B0;"></i> Manage Orders</a></li>

        <li><a href="customers.php" class="<?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>">
                <i class="fas fa-users" style="color: #E91E63;"></i> Customers</a></li>

        <li><a href="categories.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-tags" style="color: #795548;"></i> Categories</a></li>

        <li><a href="reports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line" style="color: #00BCD4;"></i> Reports</a></li>

        <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog" style="color: #607D8B;"></i> Settings</a></li>
    </ul>
</aside>