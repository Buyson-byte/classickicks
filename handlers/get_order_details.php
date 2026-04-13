<?php
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_GET['order_id'])) {
    echo "Invalid order ID.";
    exit;
}

$order_id = intval($_GET['order_id']);

$sql = "SELECT p.name, p.image, oi.quantity, oi.price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No items found in this order.</p>";
    exit;
}

echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Product</th><th>Image</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>";

$total = 0;
while ($row = $result->fetch_assoc()) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    echo "<tr>
            <td>{$row['name']}</td>
            <td><img src='../uploads/{$row['image']}' width='50'></td>
            <td>{$row['quantity']}</td>
            <td>₱" . number_format($row['price'], 2) . "</td>
            <td>₱" . number_format($subtotal, 2) . "</td>
          </tr>";
}
echo "<tr><td colspan='4' class='text-end'><strong>Total</strong></td>
      <td><strong>₱" . number_format($total, 2) . "</strong></td></tr>";
echo "</tbody></table>";