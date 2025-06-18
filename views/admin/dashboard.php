<?php
session_start();

// Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: ../auth/login.php');
//     exit();
// }

// Initialize variables with default values
$total_products = 0;
$total_orders = 0;
$pending_orders = 0;
$total_revenue = 0;
$recent_orders = [];
$error_message = '';

// Include database connection
try {
    require_once '../../config/db.php';

    // Check if $pdo exists
    if (!isset($pdo)) {
        throw new Exception("Database connection not found");
    }

    // Get dashboard statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = $result ? $result['total_products'] : 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_orders = $result ? $result['total_orders'] : 0;

    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'processing'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_orders = $result ? $result['pending_orders'] : 0;

    $stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status NOT IN ('cancelled', 'refunded')");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_revenue = $result && $result['total_revenue'] ? $result['total_revenue'] : 0;

    $stmt = $pdo->query("SELECT id, order_number, customer_email, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$recent_orders) {
        $recent_orders = [];
    }
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - eCommerce System</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .stat-card .icon {
            margin-bottom: 10px;
            display: block;
        }

        .action-btn i {
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>eCommerce Admin</h2>
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage products.php">Manage Products</a></li>
                <li><a href="manage-orders.php">Manage Orders</a></li>
                <li><a href="customers.php">Customers</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>!</span>
                    <a href="../auth/logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card products">
                    <i class="fas fa-box fa-2x icon" style="color: #2980b9;"></i>
                    <h3>Total Products</h3>
                    <div class="number"><?php echo number_format($total_products); ?></div>
                    <p>Active products in inventory</p>
                </div>

                <div class="stat-card orders">
                    <i class="fas fa-shopping-cart fa-2x icon" style="color: #27ae60;"></i>
                    <h3>Total Orders</h3>
                    <div class="number"><?php echo number_format($total_orders); ?></div>
                    <p>All time orders</p>
                </div>

                <div class="stat-card pending">
                    <i class="fas fa-clock fa-2x icon" style="color: #f39c12;"></i>
                    <h3>Pending Orders</h3>
                    <div class="number"><?php echo number_format($pending_orders); ?></div>
                    <p>Orders awaiting processing</p>
                </div>

                <div class="stat-card revenue">
                    <i class="fas fa-money-bill-wave fa-2x icon" style="color: #c0392b;"></i>
                    <h3>Total Revenue</h3>
                    <div class="number">KSh <?php echo number_format($total_revenue, 2); ?></div>
                    <p>Total sales revenue</p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <?php if (!empty($recent_orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer Email</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($order['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #7f8c8d;">
                        <p>No orders found. Orders will appear here once customers start purchasing.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="manage-products.php" class="action-btn products">
                    <i class="fas fa-plus-circle" style="color: #2980b9;"></i> Add New Product
                </a>
                <a href="manage-orders.php" class="action-btn orders">
                    <i class="fas fa-clipboard-list" style="color: #27ae60;"></i> View All Orders
                </a>
                <a href="customers.php" class="action-btn">
                    <i class="fas fa-users" style="color: #8e44ad;"></i> Manage Customers
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-chart-line" style="color: #e67e22;"></i> View Reports
                </a>
            </div>
        </main>
    </div>

    <script src="../../assets/js/dashboard.js"></script>

</body>

</html>