<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_brand'])) {
    $brand_id = intval($_POST['id']);
    $brand_name = trim($_POST['name']);

    if (empty($brand_name)) {
        $_SESSION['message'] = "Brand name cannot be empty.";
        header("Location: admin.php#brand");
        exit;
    }

    // Prevent duplicates
    $stmt = $conn->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
    $stmt->bind_param("si", $brand_name, $brand_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['message'] = "Brand name already exists.";
        $stmt->close();
        header("Location: admin.php#brand");
        exit;
    }
    $stmt->close();

    // Update brand
    $stmt = $conn->prepare("UPDATE brands SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $brand_name, $brand_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Brand updated successfully!";
    } else {
        $_SESSION['message'] = "Failed to update brand.";
    }

    $stmt->close();
    header("Location: admin.php#brand");
    exit;
}
?>
