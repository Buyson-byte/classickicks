<?php
include_once("connections/connection.php");
$conn = connection();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Step 1: Delete related cart_items first
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    // Step 2: Delete product itself
    $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
    $delete->bind_param("i", $delete_id);
    if ($delete->execute()) {
        $_SESSION['message'] = "Product deleted successfully.";
    } else {
        $_SESSION['message'] = "Failed to delete product.";
    }
    $delete->close();

    header("Location: admin.php#list");
    exit;
}