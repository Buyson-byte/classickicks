<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_brand'])) {
    $brand_name = trim($_POST['name']);

    // Validate brand name
    if (empty($brand_name)) {
        $_SESSION['message'] = "Brand name cannot be empty.";
        header("Location: admin.php#brand");
        exit;
    }

    // Check if brand already exists
    $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ?");
    $stmt->bind_param("s", $brand_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['message'] = "Brand already exists.";
        $stmt->close();
        header("Location: admin.php#brand");
        exit;
    }
    $stmt->close();

    // Insert new brand
    $stmt = $conn->prepare("INSERT INTO brands (name) VALUES (?)");
    $stmt->bind_param("s", $brand_name);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Brand added successfully!";
    } else {
        $_SESSION['message'] = "Failed to add brand: " . $stmt->error;
    }

    $stmt->close();
    header("Location: admin.php#brand");
    exit;
}
?>
