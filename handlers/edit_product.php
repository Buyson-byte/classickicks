<?php
if (isset($_POST['update_product'])) {
    session_start();
    include_once("connections/connection.php"); // removed "../"
    $conn = connection();

    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $brand_id = (int)$_POST['brand_id'];
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $image = trim($_POST['image']);
    $stocks = $_POST['stock'] ?? [];

    if (empty($name) || empty($brand_id) || empty($price) || empty($image)) {
        $_SESSION['message'] = "All fields are required to update.";
        header("Location: admin.php#list"); // removed "../"
        exit;
    }

    // Compute total quantity and sizes
    $quantity = 0;
    foreach ($stocks as $size => $count) {
        $quantity += (int)$count;
    }
    $sizes = implode(',', array_keys(array_filter($stocks, fn($v) => $v > 0)));

    // Update product
    $stmt = $conn->prepare("UPDATE products 
        SET name = ?, brand_id = ?, description = ?, price = ?, quantity = ?, image = ?, sizes = ?
        WHERE id = ?");
    $stmt->bind_param("sisdsssi", $name, $brand_id, $description, $price, $quantity, $image, $sizes, $id);

    if ($stmt->execute()) {
        // Update or insert per-size stock
        foreach ($stocks as $size => $count) {
            $check = $conn->prepare("SELECT id FROM product_sizes WHERE product_id = ? AND size = ?");
            $check->bind_param("is", $id, $size);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $update = $conn->prepare("UPDATE product_sizes SET stock = ? WHERE product_id = ? AND size = ?");
                $update->bind_param("iis", $count, $id, $size);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)");
                $insert->bind_param("isi", $id, $size, $count);
                $insert->execute();
                $insert->close();
            }
            $check->close();
        }

        $_SESSION['message'] = "✅ Product and size stocks updated successfully!";
    } else {
        $_SESSION['message'] = "❌ Failed to update product.";
    }

    $stmt->close();
    header("Location: admin.php#list"); // removed "../"
    exit;
}
?>
