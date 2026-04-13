<?php
session_start();
include_once("connections/connection.php");
$conn = connection();

if (!isset($_SESSION['pending_order_id'])) {
    header("Location: shop.php");
    exit;
}

$order_id = $_SESSION['pending_order_id'];
$user_id = $_SESSION['user_id'];

// Get order items
$sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Deduct stock
foreach ($items as $item) {
    $stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
    $stock_stmt->execute();
    $stock_stmt->close();
}

// Mark order as Completed
$status_stmt = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
$status_stmt->bind_param("i", $order_id);
$status_stmt->execute();
$status_stmt->close();

// Clear cart
$conn->query("DELETE FROM cart_items WHERE cart_id = (SELECT id FROM carts WHERE user_id = $user_id)");
$conn->query("DELETE FROM carts WHERE user_id = $user_id");

// Remove session
unset($_SESSION['pending_order_id']);

$_SESSION['message'] = "Payment successful! Your order has been completed.";
header("Location: shop.php");
exit;
?>