<?php
session_start();
require_once '../../config/db.php'; // DB connection
require_once '../api/daraja/stk_push.php'; // M-Pesa STK Push logic

// Mock customer data (replace with session or login system)
$customer_id = $_SESSION['customer_id'] ?? null;
$session_id = session_id();

// Validate required inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get billing/shipping details from POST
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$address_1 = $_POST['address_line_1'];
$city = $_POST['city'];
$state = $_POST['state'];
$postal_code = $_POST['postal_code'];
$country = $_POST['country'];
$notes = $_POST['notes'] ?? null;

// Fetch cart items
$stmt = $conn->prepare("SELECT sc.*, p.name, p.sku, p.price FROM shopping_cart sc
JOIN products p ON sc.product_id = p.id
WHERE sc.session_id = ?");
$stmt->execute([$session_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.16;
$shipping = ($subtotal >= 5000) ? 0 : 300;
$total = $subtotal + $tax + $shipping;

// Generate order number
$stmt = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM orders");
$next_id = $stmt->fetch()['next_id'];
$order_number = 'ORD' . date('Y') . str_pad($next_id, 6, '0', STR_PAD_LEFT);

// Insert order
$order_sql = "INSERT INTO orders (
    order_number, customer_id, customer_email, customer_phone, status, payment_status,
    payment_method, subtotal, tax_amount, shipping_amount, total_amount, currency, notes, created_at
) VALUES (?, ?, ?, ?, 'pending', 'pending', 'mpesa', ?, ?, ?, ?, 'KES', ?, NOW())";

$stmt = $conn->prepare($order_sql);
$stmt->execute([
    $order_number,
    $customer_id,
    $email,
    $phone,
    $subtotal,
    $tax,
    $shipping,
    $total,
    $notes
]);
$order_id = $conn->lastInsertId();

// Insert order address
$addr_sql = "INSERT INTO order_addresses (
    order_id, type, first_name, last_name, address_line_1, city, state, postal_code, country, phone
) VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?, ?)";

$conn->prepare($addr_sql)->execute([
    $order_id, $first_name, $last_name, $address_1, $city, $state, $postal_code, $country, $phone
]);

// Insert order items
$item_sql = "INSERT INTO order_items (
    order_id, product_id, product_name, product_sku, quantity, unit_price, total_price, product_options
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($item_sql);
foreach ($cart_items as $item) {
    $stmt->execute([
        $order_id,
        $item['product_id'],
        $item['name'],
        $item['sku'],
        $item['quantity'],
        $item['price'],
        $item['price'] * $item['quantity'],
        $item['product_options'] ?? '{}'
    ]);
}

// Clear cart
$conn->prepare("DELETE FROM shopping_cart WHERE session_id = ?")->execute([$session_id]);

// Initiate M-Pesa STK Push
$callback_url = 'http://localhost/ecommerce%20system/api/daraja/callback_url.php';
$response = lipaNaMpesa($phone, $total, $order_number, 'Order Payment');

if (isset($response['error'])) {
    echo json_encode(['error' => 'M-Pesa STK Push failed', 'details' => $response]);
    exit;
}

echo json_encode([
    'message' => 'Order placed successfully. Awaiting payment confirmation.',
    'order_number' => $order_number,
    'mpesa_response' => $response
]);

?>
