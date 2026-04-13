<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please log in to add items to your cart.";
    $_SESSION['product_name'] = "Login Required";
    header("Location: ../shop.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity   = max(1, (int)$_POST['quantity']);
    $size       = isset($_POST['sizes']) ? trim($_POST['sizes']) : '';

    if (empty($size)) {
        $_SESSION['message'] = "Please select a size.";
        $_SESSION['product_name'] = "Error";
        header("Location: ../product.php?id=$product_id");
        exit;
    }

    // 🔍 Get product name and specific size stock
    $stmt = $conn->prepare("
        SELECT p.name, ps.stock 
        FROM products p
        JOIN product_sizes ps ON p.id = ps.product_id
        WHERE p.id = ? AND ps.size = ?
    ");
    $stmt->bind_param("is", $product_id, $size);
    $stmt->execute();
    $stmt->bind_result($product_name, $size_stock);
    $stmt->fetch();
    $stmt->close();

    if (!$product_name) {
        $_SESSION['message'] = "Product not found.";
        $_SESSION['product_name'] = "Error";
        header("Location: ../product.php?id=$product_id");
        exit;
    }

    if ($size_stock <= 0) {
        $_SESSION['message'] = "Sorry, $product_name (Size: $size) is out of stock.";
        $_SESSION['product_name'] = $product_name;
        header("Location: ../product.php?id=$product_id");
        exit;
    }

    // 🧺 Ensure user has a cart
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($cart_id);
    $stmt->fetch();
    $stmt->close();

    if (!$cart_id) {
        $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $stmt->insert_id;
        $stmt->close();
    }

    // 🧮 Check if same product + size already exists in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND sizes = ?");
    $stmt->bind_param("iis", $cart_id, $product_id, $size);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($item = $result->fetch_assoc()) {
        $new_qty = $item['quantity'] + $quantity;

        // 🚨 Prevent exceeding available size stock
        if ($new_qty > $size_stock) {
            $new_qty = $size_stock;
            $_SESSION['message'] = "You can only add up to $size_stock of $product_name (Size: $size).";
        } else {
            $_SESSION['message'] = "Updated $product_name (Size: $size) quantity in cart.";
        }

        $_SESSION['product_name'] = $product_name;

        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $item['id']);
        $stmt->execute();
        $stmt->close();

    } else {
        // 🚨 Prevent exceeding stock on first add
        if ($quantity > $size_stock) {
            $quantity = $size_stock;
            $_SESSION['message'] = "You can only add up to $size_stock of $product_name (Size: $size).";
        } else {
            $_SESSION['message'] = "$quantity × $product_name (Size: $size) added to cart.";
        }

        $_SESSION['product_name'] = $product_name;

        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, sizes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $cart_id, $product_id, $quantity, $size);
        $stmt->execute();
        $stmt->close();
    }
}

// ✅ Always redirect to cart
header("Location: ../cart.php");
exit;
?>
