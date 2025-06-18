<?php
include_once '../config/db.php';

// Handle form submission

// require_once '../includes/auth.php';
// checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['product_id'] ?? 0;

    try {
        $pdo = Database::getInstance()->getConnection(); // ✅ Initialize first
        $pdo->beginTransaction(); // ✅ Now you can start the transaction

        // ... continue with your logic



        if ($action === 'create' || $action === 'update') {
            // Basic product data
            $productData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description']),
                'short_description' => trim($_POST['short_description']),
                'sku' => trim($_POST['sku']),
                'category_id' => intval($_POST['category_id']),
                'price' => floatval($_POST['price']),
                'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
                'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                'stock_quantity' => intval($_POST['stock_quantity']),
                'min_stock_level' => intval($_POST['min_stock_level']),
                'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                'dimensions' => !empty($_POST['dimensions']) ? trim($_POST['dimensions']) : null,
                'status' => isset($_POST['save_as_draft']) ? 'draft' : trim($_POST['status']),
                'featured' => isset($_POST['featured']) ? 1 : 0,
                'meta_title' => !empty($_POST['meta_title']) ? trim($_POST['meta_title']) : null,
                'meta_description' => !empty($_POST['meta_description']) ? trim($_POST['meta_description']) : null,
                'created_by' => $_SESSION['admin_user_id']
            ];

            // Generate slug from name
            $slug = generateSlug($productData['name']);

            // Check if slug already exists
            $slugCheckQuery = "SELECT id FROM products WHERE slug = ?";
            $slugCheckParams = [$slug];

            if ($action === 'update') {
                $slugCheckQuery .= " AND id != ?";
                $slugCheckParams[] = $productId;
            }

            $stmt = $pdo->prepare($slugCheckQuery);
            $stmt->execute($slugCheckParams);

            if ($stmt->rowCount() > 0) {
                $slug .= '-' . uniqid();
            }

            $productData['slug'] = $slug;

            if ($action === 'create') {
                // Insert new product
                $columns = implode(', ', array_keys($productData));
                $placeholders = implode(', ', array_fill(0, count($productData), '?'));

                $query = "INSERT INTO products ($columns) VALUES ($placeholders)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array_values($productData));
                $productId = $pdo->lastInsertId();

                // Log activity
                logActivity("Created product: " . $productData['name']);
            } else {
                // Update existing product
                $setParts = [];
                $values = [];

                foreach ($productData as $column => $value) {
                    $setParts[] = "$column = ?";
                    $values[] = $value;
                }

                $values[] = $productId;

                $query = "UPDATE products SET " . implode(', ', $setParts) . " WHERE id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute($values);

                // Log activity
                logActivity("Updated product: " . $productData['name']);
            }

            // Handle product attributes
            if (!empty($_POST['attributes'])) {
                // First, delete existing attributes for this product
                $stmt = $pdo->prepare("DELETE FROM product_attribute_values WHERE product_id = ?");
                $stmt->execute([$productId]);

                // Insert new attributes
                foreach ($_POST['attributes'] as $attrId => $value) {
                    if (!empty($value)) {
                        $stmt = $pdo->prepare("INSERT INTO product_attribute_values (product_id, attribute_id, value) VALUES (?, ?, ?)");
                        $stmt->execute([$productId, $attrId, trim($value)]);
                    }
                }
            }

            // Handle product images
            $uploadDir = '../../uploads/product-images/';

            // Create upload directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Process existing images (mark for deletion or update primary status)
            $existingImages = $_POST['existing_images'] ?? [];
            $deletedImages = $_POST['deleted_images'] ?? [];

            foreach ($existingImages as $imageId) {
                if (in_array($imageId, $deletedImages)) {
                    // Delete image from database and filesystem
                    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
                    $stmt->execute([$imageId]);
                    $image = $stmt->fetch();

                    if ($image) {
                        $filePath = '../../' . $image['image_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                    $stmt->execute([$imageId]);
                } else {
                    // Update primary status
                    $isPrimary = ($_POST['primary_image'] ?? '') === $imageId ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE product_images SET is_primary = ? WHERE id = ?");
                    $stmt->execute([$isPrimary, $imageId]);
                }
            }

            // Process newly uploaded images
            if (!empty($_FILES['product_images']['name'][0])) {
                $primaryImageSet = isset($_POST['primary_image']);

                foreach ($_FILES['product_images']['tmp_name'] as $index => $tmpName) {
                    if ($_FILES['product_images']['error'][$index] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    // Validate file type and size
                    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($fileInfo, $tmpName);
                    finfo_close($fileInfo);

                    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                        continue;
                    }

                    if ($_FILES['product_images']['size'][$index] > 5 * 1024 * 1024) { // 5MB limit
                        continue;
                    }

                    // Generate unique filename
                    $extension = pathinfo($_FILES['product_images']['name'][$index], PATHINFO_EXTENSION);
                    $filename = 'product-' . $productId . '-' . uniqid() . '.' . $extension;
                    $destination = $uploadDir . $filename;

                    if (move_uploaded_file($tmpName, $destination)) {
                        $isPrimary = (!$primaryImageSet && $index === 0) ||
                            ($_POST['primary_image'] ?? '') === "new_$index";

                        $relativePath = 'uploads/product-images/' . $filename;

                        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $stmt->execute([$productId, $relativePath, $isPrimary ? 1 : 0]);

                        if ($isPrimary) {
                            $primaryImageSet = true;
                        }
                    }
                }
            }

            $pdo->commit();

            // Redirect to product list with success message
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Product ' . ($action === 'create' ? 'created' : 'updated') . ' successfully!'];
            header("Location: ../views/admin/products-list.php?id=$productId");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();

        // Log error
        error_log("Product save error: " . $e->getMessage());

        // Redirect back with error message
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Error saving product: ' . $e->getMessage()];
        header("Location: " . ($action === 'create' ? 'manage-products.php' : "manage-products.php?id=$productId"));
        exit();
    }
}

// Helper function to generate slug from product name
function generateSlug($string)
{
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

// Helper function to log activity
function logActivity($action)
{
    global $pdo;

    $stmt = $pdo->prepare("INSERT INTO system_logs (user_type, user_id, action, ip_address, user_agent) 
                          VALUES ('admin', ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['admin_user_id'],
        $action,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);


    // Handle delete action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $productId = intval($_POST['product_id']);

        try {
            $pdo->beginTransaction();

            // Get product info for logging
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Product not found");
            }

            // Get images to delete from filesystem
            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Delete product and related records (cascade should handle most)
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);

            // Delete image files
            foreach ($images as $image) {
                $filePath = '../../' . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $pdo->commit();

            // Log activity
            logActivity("Deleted product: " . $product['name']);

            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();

            // Return JSON error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }
}
