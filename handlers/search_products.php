<?php
include_once("../connections/connection.php"); // adjust path if needed
$conn = connection();

$q = $_GET['q'] ?? '';
$q = $conn->real_escape_string($q);

$sql = "SELECT id, name, price, image 
        FROM products 
        WHERE name LIKE '%$q%' 
        LIMIT 10";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Send proper JSON header
header('Content-Type: application/json; charset=utf-8');
echo json_encode($products);
exit;