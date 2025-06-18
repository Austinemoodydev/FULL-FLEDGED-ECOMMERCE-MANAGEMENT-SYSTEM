<?php
session_start();
require_once '../../config/db.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: shop.php');
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get product details
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.id = :id AND p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Get related products (same category, excluding current product)
$related_query = "SELECT p.*, c.name as category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = :category_id AND p.id != :product_id 
                  AND p.status = 'active' 
                  ORDER BY RAND() LIMIT 4";
$related_stmt = $db->prepare($related_query);
$related_stmt->bindParam(':category_id', $product['category_id']);
$related_stmt->bindParam(':product_id', $product_id);
$related_stmt->execute();
$related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title><?php echo htmlspecialchars($product['name']); ?> - ShopHub</title>
    <link rel="stylesheet" href="../../assets/css/product-detail.css">
    </link>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    </style>
</head>

<body>
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
                    <div class="product-image bg-light d-flex align-items-center justify-content-center w-100" style="height: 500px; border-radius: 15px;">
                        <i class="fas fa-image fa-5x text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <?php if ($product['featured']): ?>
                        <span class="featured-badge me-2">Featured</span>
                    <?php endif; ?>
                    <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($product['category_name']); ?></span>
                </div>

                <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="price-display mb-3">$<?php echo number_format($product['price'], 2); ?></div>

                <!-- Stock Information -->
                <?php
                $stock_class = '';
                $stock_message = '';
                $stock_icon = '';

                if ($product['stock_quantity'] <= 0) {
                    $stock_class = 'out-of-stock';
                    $stock_message = 'Out of Stock';
                    $stock_icon = 'fas fa-times-circle';
                } elseif ($product['stock_quantity'] <= 5) {
                    $stock_class = 'low-stock';
                    $stock_message = 'Only ' . $product['stock_quantity'] . ' left in stock!';
                    $stock_icon = 'fas fa-exclamation-triangle';
                } else {
                    $stock_class = 'in-stock';
                    $stock_message = $product['stock_quantity'] . ' items available';
                    $stock_icon = 'fas fa-check-circle';
                }
                ?>

                <div class="stock-info <?php echo $stock_class; ?>">
                    <i class="<?php echo $stock_icon; ?>"></i> <?php echo $stock_message; ?>
                </div>

                <div class="mb-4">
                    <h5>Description</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <?php if ($product['stock_quantity'] > 0): ?>
                    <form id="addToCartForm" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control quantity-selector" id="quantity"
                                    value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-danger btn-lg">
                                    <i class="fas fa-heart"></i> Wishlist
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-4">
                        <button class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-times"></i> Out of Stock
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Product Details Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Product Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>SKU:</strong></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Stock:</strong></td>
                                <td><?php echo $product['stock_quantity']; ?> units</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-success">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="mb-5">
                <h3 class="mb-4">Related Products</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <?php if ($related['image']): ?>
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($related['image']); ?>"
                                        class="card-img-top" style="height: 200px; object-fit: cover;"
                                        alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h6>
                                    <p class="card-text flex-grow-1 small text-muted">
                                        <?php echo htmlspecialchars(substr($related['description'], 0, 80)) . '...'; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-success fw-bold">$<?php echo number_format($related['price'], 2); ?></span>
                                        <div>
                                            <a href="product-detail.php?id=<?php echo $related['id']; ?>"
                                                class="btn btn-outline-primary btn-sm">View</a>
                                            <?php if ($related['stock_quantity'] > 0): ?>
                                                <button class="btn btn-primary btn-sm add-to-cart"
                                                    data-product-id="<?php echo $related['id']; ?>">Add</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container text-center">
                <p>&copy; 2024 ShopHub. All rights reserved.</p>
                <div>
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </footer>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <script>
            // Add to cart form handler
            document.getElementById('addToCartForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const quantity = document.getElementById('quantity').value;
                const productId = <?php echo $product_id; ?>;
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                submitBtn.disabled = true;

                fetch('add-to-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_id=' + productId + '&quantity=' + quantity
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart badge
                            const cartBadge = document.querySelector('.cart-badge');
                            if (cartBadge) {
                                cartBadge.textContent = data.cart_count;
                            } else {
                                const cartLink = document.querySelector('a[href="cart.php"]');
                                cartLink.innerHTML += '<span class="cart-badge">' + data.cart_count + '</span>';
                            }

                            // Show success state
                            submitBtn.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
                            submitBtn.classList.remove('btn-primary');
                            submitBtn.classList.add('btn-success');

                            setTimeout(() => {
                                submitBtn.innerHTML = originalText;
                                submitBtn.classList.remove('btn-success');
                                submitBtn.classList.add('btn-primary');
                                submitBtn.disabled = false;
                            }, 3000);
                        } else {
                            alert('Error: ' + data.message);
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error adding product to cart');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });

            // Related products add to cart
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');

                    fetch('add-to-cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'product_id=' + productId + '&quantity=1'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update cart badge
                                const cartBadge = document.querySelector('.cart-badge');
                                if (cartBadge) {
                                    cartBadge.textContent = data.cart_count;
                                } else {
                                    const cartLink = document.querySelector('a[href="cart.php"]');
                                    cartLink.innerHTML += '<span class="cart-badge">' + data.cart_count + '</span>';
                                }

                                // Show success message
                                this.innerHTML = '<i class="fas fa-check"></i>';
                                this.classList.remove('btn-primary');
                                this.classList.add('btn-success');

                                setTimeout(() => {
                                    this.innerHTML = 'Add';
                                    this.classList.remove('btn-success');
                                    this.classList.add('btn-primary');
                                }, 2000);
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error adding product to cart');
                        });
                });
            });
        </script>
</body>

</html>
<a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
<a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Register</a>

</div>
</div>
</nav>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="shop.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <!-- Product Details -->
    <!-- Product Details -->
    <div class="row mb-5">
        <div class="col-md-6">
            <?php if ($product['image']): ?>
                <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                    class="img-fluid product-image w-100" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php else: ?>
                <img src="../../assets/images/no-image.png"
                    class="img-fluid product-image w-100" alt="No image available">
            <?php endif; ?>
        </div>
    </div>