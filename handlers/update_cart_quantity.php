<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $cart_item_id = (int)$_POST['cart_item_id'];
    $action = $_POST['action'];

    // 🟢 Get current cart quantity, product_id, and size
    $stmt = $conn->prepare("
        SELECT ci.quantity AS cart_quantity, ci.product_id, ci.sizes, p.name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.id = ?
    ");
    $stmt->bind_param("i", $cart_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        header("Location: ../cart.php");
        exit;
    }

    $current_quantity = (int)$item['cart_quantity'];
    $product_id = (int)$item['product_id'];
    $selected_size = $item['sizes'];
    $product_name = $item['name'];

    // 🟢 Get stock based on size from product_sizes
    $stock_stmt = $conn->prepare("
        SELECT stock 
        FROM product_sizes 
        WHERE product_id = ? AND size = ?
    ");
    $stock_stmt->bind_param("is", $product_id, $selected_size);
    $stock_stmt->execute();
    $stock_stmt->bind_result($stock_quantity);
    $stock_stmt->fetch();
    $stock_stmt->close();

    $stock_quantity = $stock_quantity ?? 0;

    if ($action === 'increase') {
        // ✅ Only increase if below stock of that specific size
        if ($current_quantity < $stock_quantity) {
            $new_quantity = $current_quantity + 1;
        } else {
            $new_quantity = $current_quantity;
            $_SESSION['message'] = "⚠️ Only $stock_quantity stocks available for size $selected_size of $product_name.";
            $_SESSION['product_name'] = $product_name;
            header("Location: ../cart.php");
            exit;
        }
    } elseif ($action === 'decrease') {
        $new_quantity = $current_quantity - 1;

        if ($new_quantity <= 0) {
            // Remove item if quantity goes to 0 or less
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
            $stmt->bind_param("i", $cart_item_id);
            $stmt->execute();
            $stmt->close();
            header("Location: ../cart.php");
            exit;
        }
    } else {
        $new_quantity = $current_quantity; // No change if action invalid
    }

    // 🟢 Update quantity if changed
    if ($new_quantity != $current_quantity) {
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ../cart.php");
    exit;
}
?>
