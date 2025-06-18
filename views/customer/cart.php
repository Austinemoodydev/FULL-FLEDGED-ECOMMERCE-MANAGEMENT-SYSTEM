<?php
session_start();
require_once '../../config/db.php';

// Initialize database connection
$db = getDB()->getConnection();

$cart_items = [];
$total_amount = 0;

// Get cart items from session
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

    $query = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;

        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];

        $total_amount += $subtotal;
    }
}

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);

                if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
                    // Check stock availability
                    $stock_query = "SELECT stock_quantity FROM products WHERE id = ?";
                    $stock_stmt = $db->prepare($stock_query);
                    $stock_stmt->execute([$product_id]);
                    $stock = $stock_stmt->fetchColumn();

                    if ($quantity <= $stock) {
                        $_SESSION['cart'][$product_id] = $quantity;
                        $success_message = "Cart updated successfully!";
                    } else {
                        $error_message = "Only $stock items available in stock.";
                    }
                } elseif ($quantity <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                    $success_message = "Item removed from cart.";
                }
                break;

            case 'remove':
                $product_id = intval($_POST['product_id']);
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    $success_message = "Item removed from cart.";
                }
                break;

            case 'clear':
                $_SESSION['cart'] = [];
                $success_message = "Cart cleared successfully!";
                break;
        }

        // Redirect to prevent form resubmission
        header('Location: cart.php' . (isset($success_message) ? '?success=1' : (isset($error_message) ? '?error=1' : '')));
        exit;
    }
}

// Get cart item count for header
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ShopHub</title>
    <link rel="stylesheet" href="../../assets/css/cart.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

</head>

<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="shop.php"><i class="fas fa-store"></i> ShopHub</a>

            <div class="navbar-nav ms-auto">
                <a class="nav-link position-relative" href="cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="orders.php"><i class="fas fa-list"></i> Orders</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="shop.php">Home</a></li>
                <li class="breadcrumb-item active">Shopping Cart</li>
            </ol>
        </nav>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Cart updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> Error updating cart. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
                    <?php if (!empty($cart_items)): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to clear your cart?')">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart fa-5x mb-3"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add some products to your cart to get started.</p>
                        <a href="shop.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-store"></i> Continue Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php if ($item['product']['image']): ?>
                                        <img src="../../uploads/products/<?php echo htmlspecialchars($item['product']['image']); ?>"
                                            class="cart-item-image" alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                    <?php else: ?>
                                        <div class="cart-item-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4">
                                    <h5><?php echo htmlspecialchars($item['product']['name']); ?></h5>
                                    <p class="text-muted mb-1">SKU: <?php echo htmlspecialchars($item['product']['sku']); ?></p>
                                    <p class="text-success mb-0">$<?php echo number_format($item['product']['price'], 2); ?> each</p>
                                </div>

                                <div class="col-md-3">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">

                                        <label class="form-label me-2 mb-0">Qty:</label>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                            min="0" max="<?php echo $item['product']['stock_quantity']; ?>"
                                            class="form-control quantity-input me-2">
                                        <button type="submit" class="btn btn-update btn-sm">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                    <small class="text-muted">Max: <?php echo $item['product']['stock_quantity']; ?></small>
                                </div>

                                <div class="col-md-2">
                                    <h5 class="text-success">$<?php echo number_format($item['subtotal'], 2); ?></h5>
                                </div>

                                <div class="col-md-1">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Remove this item from cart?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-3"><i class="fas fa-receipt"></i> Order Summary</h4>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?php echo array_sum(array_column($cart_items, 'quantity')); ?>):</span>
                            <span>$<?php echo number_format($total_amount, 2); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span class="text-success">Free</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($total_amount * 0.08, 2); ?></span>
                        </div>

                        <hr>

                        <div class="total-row d-flex justify-content-between text-dark">
                            <span>Total:</span>
                            <span>$<?php echo number_format($total_amount + ($total_amount * 0.08), 2); ?></span>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <a href="checkout.php" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Proceed to Checkout
                            </a>
                            <a href="shop.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>

                        <!-- Promo Code Section -->
                        <div class="mt-4">
                            <h6>Have a promo code?</h6>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Enter promo code">
                                <button class="btn btn-outline-secondary" type="button">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2024 ShopHub. All rights reserved.</p>
        </div>
    </footer>
    <script src="../../assets/js/cart.js"></script>

</body>

</html>