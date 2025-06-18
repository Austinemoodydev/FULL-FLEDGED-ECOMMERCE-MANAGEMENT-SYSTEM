<?php
// Check admin authentication and permissions here
require_once '../../config/db.php';
//require_once '../../includes/auth.php';
//get pdo instance
$db = Database::getInstance();
$pdo = $db->getConnection();

$categories = [];
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categories[$row['id']] = $row['name'];
}

// Get product attributes
$pdo->beginTransaction();
$attributes = [];
$stmt = $pdo->query("SELECT id, name, type FROM product_attributes ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $attributes[$row['id']] = $row;
}

// Check if editing existing product
$product = null;
$productImages = [];
$productAttributes = [];

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Get product images
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
        $stmt->execute([$productId]);
        $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get product attributes
        $stmt = $pdo->prepare("SELECT * FROM product_attribute_values WHERE product_id = ?");
        $stmt->execute([$productId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productAttributes[$row['attribute_id']] = $row['value'];
        }
    }
}

$pageTitle = isset($product) ? "Edit Product" : "Add New Product";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/manage-products.css">
    <link rel="stylesheet" href="/ecommerce_system/assets/css/manage_products.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-<?= isset($product) ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
                        <?= htmlspecialchars($pageTitle) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="products-list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Products
                        </a>
                    </div>
                </div>

                <form id="productForm" action="../../controllers/productController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= isset($product) ? 'update' : 'create' ?>">
                    <?php if (isset($product)): ?>
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-info-circle me-2"></i>Basic Information
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            <i class="bi bi-tag me-1"></i>Product Name *
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="<?= isset($product) ? htmlspecialchars($product['name']) : '' ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">
                                            <i class="bi bi-text-paragraph me-1"></i>Description
                                        </label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= isset($product) ? htmlspecialchars($product['description']) : '' ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="short_description" class="form-label">
                                            <i class="bi bi-text-left me-1"></i>Short Description
                                        </label>
                                        <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= isset($product) ? htmlspecialchars($product['short_description']) : '' ?></textarea>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Brief summary for product listings (max 500 characters)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing & Inventory Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-currency-dollar me-2"></i>Pricing & Inventory
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">
                                                <i class="bi bi-tag-fill me-1"></i>Price *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-currency-dollar"></i> KES
                                                </span>
                                                <input type="number" class="form-control" id="price" name="price"
                                                    step="0.01" min="0"
                                                    value="<?= isset($product) ? $product['price'] : '0' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="compare_price" class="form-label">
                                                <i class="bi bi-percent me-1"></i>Compare at Price
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-currency-dollar"></i> KES
                                                </span>
                                                <input type="number" class="form-control" id="compare_price" name="compare_price"
                                                    step="0.01" min="0"
                                                    value="<?= isset($product) ? $product['compare_price'] : '' ?>">
                                            </div>
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>Show as original price when discounted
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cost_price" class="form-label">
                                                <i class="bi bi-calculator me-1"></i>Cost Price
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-currency-dollar"></i> KES
                                                </span>
                                                <input type="number" class="form-control" id="cost_price" name="cost_price"
                                                    step="0.01" min="0"
                                                    value="<?= isset($product) ? $product['cost_price'] : '' ?>">
                                            </div>
                                            <div class="form-text">
                                                <i class="bi bi-graph-up me-1"></i>For profit calculation
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sku" class="form-label">
                                                <i class="bi bi-upc-scan me-1"></i>SKU *
                                            </label>
                                            <input type="text" class="form-control" id="sku" name="sku"
                                                value="<?= isset($product) ? htmlspecialchars($product['sku']) : '' ?>" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="stock_quantity" class="form-label">
                                                <i class="bi bi-boxes me-1"></i>Stock Quantity
                                            </label>
                                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                                min="0" value="<?= isset($product) ? $product['stock_quantity'] : '0' ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="min_stock_level" class="form-label">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Low Stock Threshold
                                            </label>
                                            <input type="number" class="form-control" id="min_stock_level" name="min_stock_level"
                                                min="0" value="<?= isset($product) ? $product['min_stock_level'] : '5' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Attributes Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-sliders me-2"></i>Product Attributes
                                </div>
                                <div class="card-body">
                                    <?php foreach ($attributes as $attrId => $attr): ?>
                                        <div class="mb-3">
                                            <label for="attr_<?= $attrId ?>" class="form-label">
                                                <i class="bi bi-<?= $attr['type'] === 'color' ? 'palette' : ($attr['type'] === 'select' ? 'list' : 'input-cursor-text') ?> me-1"></i>
                                                <?= htmlspecialchars($attr['name']) ?>
                                            </label>
                                            <?php if ($attr['type'] === 'select'): ?>
                                                <select class="form-select" id="attr_<?= $attrId ?>" name="attributes[<?= $attrId ?>]">
                                                    <option value="">-- Select <?= htmlspecialchars($attr['name']) ?> --</option>
                                                    <?php
                                                    $options = ['Small', 'Medium', 'Large', 'XL'];
                                                    foreach ($options as $option): ?>
                                                        <option value="<?= htmlspecialchars($option) ?>"
                                                            <?= isset($productAttributes[$attrId]) && $productAttributes[$attrId] === $option ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($option) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($attr['type'] === 'color'): ?>
                                                <input type="color" class="form-control form-control-color" id="attr_<?= $attrId ?>"
                                                    name="attributes[<?= $attrId ?>]"
                                                    value="<?= isset($productAttributes[$attrId]) ? htmlspecialchars($productAttributes[$attrId]) : '#000000' ?>">
                                            <?php else: ?>
                                                <input type="text" class="form-control" id="attr_<?= $attrId ?>"
                                                    name="attributes[<?= $attrId ?>]"
                                                    value="<?= isset($productAttributes[$attrId]) ? htmlspecialchars($productAttributes[$attrId]) : '' ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Product Status Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-toggle-on me-2"></i>Product Status
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            <i class="bi bi-circle-fill me-1"></i>Status *
                                        </label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?= isset($product) && $product['status'] === 'active' ? 'selected' : '' ?>>
                                                <i class="bi bi-check-circle"></i> Active
                                            </option>
                                            <option value="inactive" <?= isset($product) && $product['status'] === 'inactive' ? 'selected' : '' ?>>
                                                <i class="bi bi-x-circle"></i> Inactive
                                            </option>
                                            <option value="draft" <?= isset($product) && $product['status'] === 'draft' ? 'selected' : '' ?>>
                                                <i class="bi bi-file-text"></i> Draft
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="featured" name="featured"
                                            value="1" <?= isset($product) && $product['featured'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="featured">
                                            <i class="bi bi-star-fill me-1"></i>Featured Product
                                        </label>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">
                                            <i class="bi bi-folder me-1"></i>Category *
                                        </label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">-- Select Category --</option>
                                            <?php foreach ($categories as $id => $name): ?>
                                                <option value="<?= $id ?>" <?= isset($product) && $product['category_id'] == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Images Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-images me-2"></i>Product Images
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="product_images" class="form-label">
                                            <i class="bi bi-cloud-upload me-1"></i>Upload Images
                                        </label>
                                        <input class="form-control" type="file" id="product_images" name="product_images[]" multiple accept="image/*">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Upload product images (max 5MB each)
                                        </div>
                                    </div>

                                    <div id="imagePreviewContainer">
                                        <?php if (!empty($productImages)): ?>
                                            <?php foreach ($productImages as $image): ?>
                                                <div class="image-preview-item mb-3" data-image-id="<?= $image['id'] ?>">
                                                    <img src="<?= htmlspecialchars($image['image_path']) ?>" class="img-thumbnail" style="max-height: 100px;">
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input primary-image" type="radio" name="primary_image"
                                                                value="<?= $image['id'] ?>" <?= $image['is_primary'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label">
                                                                <i class="bi bi-star me-1"></i>Primary
                                                            </label>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-danger delete-image" data-image-id="<?= $image['id'] ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                    <input type="hidden" name="existing_images[]" value="<?= $image['id'] ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-truck me-2"></i>Shipping
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">
                                            <i class="bi bi-speedometer me-1"></i>Weight (kg)
                                        </label>
                                        <input type="number" class="form-control" id="weight" name="weight"
                                            step="0.01" min="0"
                                            value="<?= isset($product) ? $product['weight'] : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="dimensions" class="form-label">
                                            <i class="bi bi-rulers me-1"></i>Dimensions (L×W×H)
                                        </label>
                                        <input type="text" class="form-control" id="dimensions" name="dimensions"
                                            placeholder="e.g., 10×5×2"
                                            value="<?= isset($product) ? htmlspecialchars($product['dimensions']) : '' ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-search me-2"></i>SEO Settings
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">
                                            <i class="bi bi-card-heading me-1"></i>Meta Title
                                        </label>
                                        <input type="text" class="form-control" id="meta_title" name="meta_title"
                                            value="<?= isset($product) ? htmlspecialchars($product['meta_title']) : '' ?>">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Max 60 characters
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">
                                            <i class="bi bi-card-text me-1"></i>Meta Description
                                        </label>
                                        <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?= isset($product) ? htmlspecialchars($product['meta_description']) : '' ?></textarea>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Max 160 characters
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mb-5">
                        <button type="button" onclick="saveDraft()" class="btn btn-secondary">
                            <i class="bi bi-file-text me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="save" class="btn btn-primary">
                            <i class="bi bi-<?= isset($product) ? 'arrow-clockwise' : 'plus-circle' ?> me-1"></i>
                            <?= isset($product) ? 'Update Product' : 'Create Product' ?>
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../../assets/js/manage-products.js"></script>

</body>

</html>