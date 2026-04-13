<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['from']) && $_GET['from'] === 'shop') {
        // Shop page should use SweetAlert
        $_SESSION['shop_wishlist_login'] = true;
    } else {
        // Product page shows normal session alert
        $_SESSION['message'] = "Please login to wishlist this item.";
    }

    // Redirect back to previous page
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header("Location: " . $redirect_url);
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];

    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 1) {
        $_SESSION['message'] = "Invalid product.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Check if already in wishlist
    $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
    $check_stmt->bind_param("ii", $user_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Already exists
        if (isset($_GET['from']) && $_GET['from'] === 'shop') {
            $_SESSION['shop_wishlist_exists'] = true;
        } else {
            $_SESSION['message'] = "Product already added to wishlist!";
        }
    } else {
        // Add to wishlist
        $insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $user_id, $product_id);
        $insert_stmt->execute();

        if (isset($_GET['from']) && $_GET['from'] === 'shop') {
            $_SESSION['shop_wishlist_success'] = true;
        } else {
            $_SESSION['message'] = "Product added to wishlist.";
        }
    }
}

// Redirect back to the page where the user came from
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect_url);
exit;
?>