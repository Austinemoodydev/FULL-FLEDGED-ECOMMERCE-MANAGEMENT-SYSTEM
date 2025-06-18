

 <?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    // Redirect to login with redirect parameter
    header("Location: ../../../auth/customers login.php?redirect=../customer/orders.php");
    exit();
}


$customer_id = $_SESSION['customer_id'];
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                updateCartItem();
                break;
            case 'remove_item':
                removeCartItem();
                break;
            case 'add_to_wishlist':
                addToWishlist();
                break;
            case 'proceed_to_checkout':
                proceedToCheckout();
                break;
        }
    }
}

// Function to update cart item quantity
function updateCartItem() {
    global $db, $customer_id;
    
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        $_SESSION['error'] = "Quantity must be at least 1";
        return;
    }
    
    // Check product stock
    $product = $db->fetchOne("
        SELECT p.id, p.stock_quantity, sc.quantity as cart_quantity 
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.id
        WHERE sc.id = ? AND sc.customer_id = ?
    ", [$cart_id, $customer_id]);
    
    if (!$product) {
        $_SESSION['error'] = "Item not found in your cart";
        return;
    }
    
    $new_quantity = $quantity;
    
    // Check if we have enough stock
    if ($new_quantity > $product['stock_quantity']) {
        $_SESSION['error'] = "Only {$product['stock_quantity']} items available in stock";
        $new_quantity = $product['stock_quantity'];
    }
    
    // Update cart
    $db->update('shopping_cart', 
        ['quantity' => $new_quantity, 'updated_at' => date('Y-m-d H:i:s')],
        'id = ? AND customer_id = ?', 
        [$cart_id, $customer_id]
    );
    
    $_SESSION['success'] = "Cart updated successfully";
}

// Function to remove cart item
function removeCartItem() {
    global $db, $customer_id;
    
    $cart_id = $_POST['cart_id'];
    
    $deleted = $db->delete('shopping_cart', 
        'id = ? AND customer_id = ?', 
        [$cart_id, $customer_id]
    );
    
    if ($deleted) {
        $_SESSION['success'] = "Item removed from cart";
    } else {
        $_SESSION['error'] = "Failed to remove item";
    }
}

// Function to add item to wishlist
function addToWishlist() {
    global $db, $customer_id;
    
    $product_id = $_POST['product_id'];
    
    // Check if product exists
    $product = $db->fetchOne("SELECT id FROM products WHERE id = ? AND status = 'active'", [$product_id]);
    if (!$product) {
        $_SESSION['error'] = "Product not found";
        return;
    }
    
    // Check if already in wishlist
    $exists = $db->exists('wishlist', 'customer_id = ? AND product_id = ?', [$customer_id, $product_id]);
    if ($exists) {
        $_SESSION['error'] = "Product already in your wishlist";
        return;
    }
    
    // Add to wishlist
    $db->insert('wishlist', [
        'customer_id' => $customer_id,
        'product_id' => $product_id,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $_SESSION['success'] = "Product added to wishlist";
}

// Function to proceed to checkout
function proceedToCheckout() {
    // Verify cart has items
    global $db, $customer_id;
    
    $cart_count = $db->count('shopping_cart', 'customer_id = ?', [$customer_id]);
    if ($cart_count < 1) {
        $_SESSION['error'] = "Your cart is empty";
        return;
    }
    
    // Check stock availability for all items
    $cart_items = $db->fetchAll("
        SELECT sc.id, sc.product_id, sc.quantity, p.name, p.stock_quantity
        FROM shopping_cart sc
        JOIN products p ON sc.product_id = p.id
        WHERE sc.customer_id = ?
    ", [$customer_id]);
    
    $out_of_stock = [];
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $out_of_stock[] = $item['name'];
        }
    }
    
    if (!empty($out_of_stock)) {
        $_SESSION['error'] = "Some items are out of stock: " . implode(', ', $out_of_stock);
        return;
    }
    
    // Redirect to checkout
    header("Location: checkout.php");
    exit();
}

// Get customer's cart items
$cart_items = $db->fetchAll("
    SELECT 
        sc.id as cart_id, 
        sc.quantity, 
        p.id as product_id, 
        p.name, 
        p.price, 
        p.compare_price, 
        p.stock_quantity,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
    FROM shopping_cart sc
    JOIN products p ON sc.product_id = p.id
    WHERE sc.customer_id = ?
    ORDER BY sc.created_at DESC
", [$customer_id]);

// Calculate cart totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

// Get customer's default address for shipping estimate
$default_address = $db->fetchOne("
    SELECT * FROM customer_addresses 
    WHERE customer_id = ? AND is_default = 1
    LIMIT 1
", [$customer_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - eCommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/oders.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <h2 class="mb-4">Your Shopping Cart</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-info">
                        Your cart is empty. <a href="products.php" class="alert-link">Continue shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): 
    $item_total = $item['price'] * $item['quantity'];
    
    if ($item['stock_quantity'] <= 0) {
        $stock_status = 'out-of-stock';
        $stock_text = 'Out of stock';
    } elseif ($item['stock_quantity'] < $item['quantity']) {
        $stock_status = 'low-stock';
        $stock_text = 'Low stock';
    } else {
        $stock_status = 'in-stock';
        $stock_text = 'In stock';
    }
?>
    <!-- Your HTML or other PHP output for each item goes here -->
    <div class="cart-item <?= $stock_status ?>">
        <p><?= htmlspecialchars($item['name']) ?></p>
        <p>Price: <?= number_format($item['price'], 2) ?></p>
        <p>Quantity: <?= $item['quantity'] ?></p>
        <p>Status: <?= $stock_text ?></p>
        <p>Total: <?= number_format($item_total, 2) ?></p>
    </div>
<?php endforeach; ?>

                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars($item['image'] ?? 'images/placeholder-product.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="product-img me-3">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                                <span class="stock-status <?= $stock_status ?>">
                                                    <i class="fas fa-circle"></i> <?= $stock_text ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold">KSh <?= number_format($item['price'], 2) ?></span>
                                            <?php if ($item['compare_price'] > $item['price']): ?>
                                                <span class="discount-price">KSh <?= number_format($item['compare_price'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                                   min="1" max="<?= $item['stock_quantity'] ?>" 
                                                   class="form-control quantity-input me-2">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="fw-bold">KSh <?= number_format($item_total, 2) ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline ms-1">
                                            <input type="hidden" name="action" value="add_to_wishlist">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        <form method="post">
                            <input type="hidden" name="action" value="proceed_to_checkout">
                            <button type="submit" class="btn btn-primary">
                                Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card summary-card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?= $total_items ?> items)</span>
                            <span>KSh <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>
                                <?php if ($subtotal > 5000): ?>
                                    FREE
                                <?php else: ?>
                                    KSh 300.00
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax</span>
                            <span>KSh <?= number_format($subtotal * 0.16, 2) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span>KSh <?= number_format($subtotal * 1.16 + ($subtotal > 5000 ? 0 : 300), 2) ?></span>
                        </div>
                        
                        <?php if (!empty($default_address)): ?>
                            <div class="mt-4">
                                <h6 class="mb-2">Shipping to:</h6>
                                <address class="small">
                                    <?= htmlspecialchars($default_address['first_name'] . ' ' . $default_address['last_name']) ?><br>
                                    <?= htmlspecialchars($default_address['address_line_1']) ?><br>
                                    <?= !empty($default_address['address_line_2']) ? htmlspecialchars($default_address['address_line_2']) . '<br>' : '' ?>
                                    <?= htmlspecialchars($default_address['city']) ?>, <?= htmlspecialchars($default_address['state']) ?><br>
                                    <?= htmlspecialchars($default_address['country']) ?>, <?= htmlspecialchars($default_address['postal_code']) ?><br>
                                    Phone: <?= htmlspecialchars($default_address['phone']) ?>
                                </address>
                                <a href="account.php?tab=addresses" class="small">Change address</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-4">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                You haven't set a default shipping address. 
                                <a href="account.php?tab=addresses" class="alert-link">Add one now</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($cart_items)): ?>
                            <form method="post" class="mt-3">
                                <input type="hidden" name="action" value="proceed_to_checkout">
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4 shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-3">Secure Payment</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <img src="images/visa.png" alt="Visa" style="height: 24px;">
                            <img src="images/mastercard.png" alt="Mastercard" style="height: 24px;">
                            <img src="images/mpesa.png" alt="M-Pesa" style="height: 24px;">
                            <img src="images/paypal.png" alt="PayPal" style="height: 24px;">
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            <i class="fas fa-lock me-1"></i> Your payment information is processed securely.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quantity input validation
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
                if (this.value > parseInt(this.max)) {
                    this.value = this.max;
                }
            });
        });
    </script>
</body>
</html>