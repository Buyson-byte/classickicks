<?php
if (isset($_GET['delete_brand']) && is_numeric($_GET['delete_brand'])) {
    $brand_id = intval($_GET['delete_brand']);

    // Check if this brand has products linked to it
    $check = $conn->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
    $check->bind_param("i", $brand_id);
    $check->execute();
    $check->bind_result($product_count);
    $check->fetch();
    $check->close();

    if ($product_count > 0) {
        $_SESSION['message'] = "Cannot delete brand — it’s linked to $product_count product(s).";
        header("Location: admin.php#brand");
        exit;
    }

    // Proceed with deletion if no linked products
    $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->bind_param("i", $brand_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Brand deleted successfully.";
    } else {
        $_SESSION['message'] = "Failed to delete brand.";
    }

    $stmt->close();
    header("Location: admin.php#brand");
    exit;
}
?>
