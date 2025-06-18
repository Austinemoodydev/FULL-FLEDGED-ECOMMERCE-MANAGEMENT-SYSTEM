<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$db = getDB();
if (!$db->isConnected()) {
    die("Database connection failed.");
}

// Fetch categories
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="../../assets/css/manage products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="header">
                <h1>Add New Product</h1>
                <a href="manage products.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>

            <form action="../../controllers/productController.php" method="POST" enctype="multipart/form-data" class="product-form">
                <div class="form-group">
                    <label>Product Name:</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>SKU:</label>
                    <input type="text" name="sku" required>
                </div>

                <div class="form-group">
                    <label>Category:</label>
                    <select name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Price (KES):</label>
                    <input type="number" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Stock Quantity:</label>
                    <input type="number" name="stock_quantity" min="0" required>
                </div>

                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Image:</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>

                <button type="submit" name="add_product" class="btn submit-btn">Add Product</button>
            </form>
        </main>
    </div>
</body>

</html>