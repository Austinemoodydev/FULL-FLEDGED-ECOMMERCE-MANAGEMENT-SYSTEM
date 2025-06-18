<?php
session_start();
require_once '../../config/db.php';

// Initialize database connection
$database = Database::getInstance();
$pdo = $database->getConnection();
// Now you can use $pdo for queries
$stmt = $pdo->prepare("SELECT * FROM products");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build the query with filters
$query = "SELECT p.*, c.name as category_name FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.status = 'active' AND p.stock_quantity > 0";

$params = [];

if (!empty($category_filter)) {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category_filter;
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($min_price > 0) {
    $query .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price < 999999) {
    $query .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

// Add sorting
switch ($sort) {
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.name ASC";
}


// Prepare and execute
$stmt = $pdo->prepare($query);
$stmt->execute($params);

// Fetch all results
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$cat_query = "SELECT * FROM categories ORDER BY name";

// Get the PDO instance from your Database singleton
$pdo = $db->getConnection(); // ✅ This returns a PDO object

// Use the PDO object to prepare and execute the query
$cat_stmt = $pdo->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);


// Get featured products for homepage section
// Get PDO connection from Database instance
$pdo = $db->getConnection();

// Get featured products for homepage section
$featured_query = "SELECT p.*, c.name as category_name FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active' AND p.featured = 1
    ORDER BY p.created_at DESC LIMIT 6";

$featured_stmt = $pdo->prepare($featured_query);
$featured_stmt->execute();
$featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Online Shop - Home</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/shop.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-store"></i> ShopHub</a>

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

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Welcome to ShopHub</h1>
            <p class="lead mb-4">Discover amazing products at unbeatable prices</p>
            <a href="#products" class="btn btn-light btn-lg">Shop Now</a>
        </div>
    </section>

    <div class="container">
        <!-- Featured Products Section -->
        <?php if (!empty($featured_products)): ?>
            <section class="mb-5">
                <h2 class="text-center mb-4">Featured Products</h2>
                <div class="row">
                    <?php foreach ($featured_products as $product): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card product-card h-100">
                                <?php if ($product['image']): ?>
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                                        class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="featured-badge">Featured</span>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-tag">$<?php echo number_format($product['price'], 2); ?></span>
                                        <div>
                                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-cart-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Filter Section -->
        <section id="products">
            <div class="filter-section">
                <h4 class="mb-3"><i class="fas fa-filter"></i> Filter Products</h4>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Min Price</label>
                        <input type="number" class="form-control" name="min_price"
                            value="<?php echo $min_price > 0 ? $min_price : ''; ?>" placeholder="$0" step="0.01">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Max Price</label>
                        <input type="number" class="form-control" name="max_price"
                            value="<?php echo $max_price < 999999 ? $max_price : ''; ?>" placeholder="$999" step="0.01">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" name="sort">
                            <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name Z-A</option>
                            <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Price Low-High</option>
                            <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Price High-Low</option>
                            <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Products Grid -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Products (<?php echo count($products); ?> found)</h3>
                <a href="?" class="btn btn-outline-secondary">Clear Filters</a>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h4>No products found</h4>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card product-card h-100">
                                <?php if ($product['image']): ?>
                                    <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                                        class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <?php if ($product['featured']): ?>
                                            <span class="featured-badge">Featured</span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                        <span class="badge bg-info"><?php echo $product['stock_quantity']; ?> in stock</span>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-tag">$<?php echo number_format($product['price'], 2); ?></span>
                                        <div>
                                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button class="btn btn-primary btn-sm add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                                <i class="fas fa-cart-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 ShopHub. All rights reserved.</p>
            <div>
                <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/shop.js> </script>
</body>
</html>