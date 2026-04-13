<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_product'])) {
    include_once("../connections/connection.php");
    $conn = connection();

    $name = trim($_POST['name']);
    $brand_id = (int)$_POST['brand_id'];
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $quantity = 0; // total will be auto-calculated
    $image = "";
    $stocks = $_POST['stock'] ?? [];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../images/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . time() . "_" . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $image = substr($targetFilePath, 3); // save path relative to root
        } else {
            $_SESSION['message'] = "Failed to upload image.";
            header("Location: ../softeng/admin.php");
            exit;
        }
    }

    // Compute total quantity = sum of all size stocks
    foreach ($stocks as $s => $count) {
        $quantity += (int)$count;
    }

    // Collect available sizes
    $sizes = implode(',', array_keys(array_filter($stocks, fn($v) => $v > 0)));

    // Insert product
    $sql = "INSERT INTO products (name, brand_id, description, price, quantity, image, sizes)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisdsss", $name, $brand_id, $description, $price, $quantity, $image, $sizes);
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;

        // Insert each size stock
        $sizeStmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)");
        foreach ($stocks as $size => $count) {
            $sizeStmt->bind_param("isi", $product_id, $size, $count);
            $sizeStmt->execute();
        }
        $sizeStmt->close();

        $_SESSION['message'] = "✅ Product created with size-based inventory!";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }

    header("Location: ../softeng/admin.php");
    exit;
}
?>
