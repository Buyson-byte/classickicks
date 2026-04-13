<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate address
$address_id = $_POST['address_id'] ?? null;
if (!$address_id || $address_id === "create") {
    $_SESSION['checkout_errors'][] = "Please select a valid delivery address.";
    header("Location: ../checkout.php");
    exit;
}
$address_id = intval($address_id);

// ✅ Capture shipping & payment method
$delivery_data = explode('|', $_POST['shipping']); // e.g. "150|₱150|Wed, Oct 30 - Sat, Nov 2"
$shipping_fee = floatval($delivery_data[0]);
$shipping_type = ($shipping_fee > 0) ? 'Express' : 'Free';
$payment_method = $_POST['payment_method'] ?? 'GCASH'; // cod or gcash

// 1️⃣ Get user's cart ID
$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();

if (!$cart_id) {
    $_SESSION['checkout_errors'][] = "Your cart is empty.";
    header("Location: ../checkout.php");
    exit;
}

// 2️⃣ Fetch cart items
$sql = "SELECT ci.product_id, ci.quantity, ci.sizes, p.price, p.name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total_price += $row['price'] * $row['quantity'];
}
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['checkout_errors'][] = "Your cart is empty.";
    header("Location: ../checkout.php");
    exit;
}

// ✅ Step 2.5: Validate current stock before creating the order
foreach ($cart_items as $item) {
    $product_id = $item['product_id'];
    $size = $item['sizes'];
    $qty = (int)$item['quantity'];

    $check_stock = $conn->prepare("SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?");
    $check_stock->bind_param("is", $product_id, $size);
    $check_stock->execute();
    $check_stock->bind_result($current_stock);
    $check_stock->fetch();
    $check_stock->close();

    if ($current_stock === null || $current_stock < $qty) {
        $_SESSION['checkout_errors'][] = "Sorry, the product '{$item['name']}' (Size: {$size}) is no longer available.";
        header("Location: ../checkout.php");
        exit;
    }
}

// ✅ Include shipping in total
$total_with_shipping = $total_price + $shipping_fee;

// 3️⃣ Insert or update customer_details
$email      = $_POST['email'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name  = $_POST['last_name'] ?? '';
$phone      = $_POST['phone'] ?? '';

$stmt = $conn->prepare("
    INSERT INTO customer_details (user_id, email, first_name, last_name, phone)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        phone = VALUES(phone)
");
$stmt->bind_param("issss", $user_id, $email, $first_name, $last_name, $phone);
$stmt->execute();
$stmt->close();

// 4️⃣ Create new order (include shipping and payment)
$stmt = $conn->prepare("
    INSERT INTO orders (user_id, order_date, total, shipping_type, shipping_fee, payment_method, status, address_id)
    VALUES (?, NOW(), ?, ?, ?, ?, 'Pending', ?)
");
$stmt->bind_param("idsssi", $user_id, $total_with_shipping, $shipping_type, $shipping_fee, $payment_method, $address_id);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

if ($order_id == 0) {
    die("Order insert failed — no order_id generated.");
}

// 🚨 Start transaction
$conn->begin_transaction();

try {
    // 5️⃣ Insert order_items & deduct stock
    $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size)
                                  VALUES (?, ?, ?, ?, ?)");

    foreach ($cart_items as $item) {
        $product_id = $item['product_id'];
        $size = $item['sizes'];
        $qty = (int)$item['quantity'];
        $price = $item['price'];

        // Lock stock
        $check_stock = $conn->prepare("SELECT stock FROM product_sizes WHERE product_id = ? AND size = ? FOR UPDATE");
        $check_stock->bind_param("is", $product_id, $size);
        $check_stock->execute();
        $check_stock->bind_result($current_stock);
        $check_stock->fetch();
        $check_stock->close();

        if ($current_stock === null) {
            throw new Exception("Size $size for product ID $product_id not found.");
        }

        if ($qty > $current_stock) {
            throw new Exception("Not enough stock for size $size of product ID $product_id.");
        }

        // Deduct stock
        $update_stock = $conn->prepare("UPDATE product_sizes SET stock = stock - ? WHERE product_id = ? AND size = ?");
        $update_stock->bind_param("iis", $qty, $product_id, $size);
        $update_stock->execute();
        $update_stock->close();

        // Recalculate total stock
        $recalc = $conn->prepare("UPDATE products 
                                  SET quantity = (SELECT COALESCE(SUM(stock),0) FROM product_sizes WHERE product_id = ?)
                                  WHERE id = ?");
        $recalc->bind_param("ii", $product_id, $product_id);
        $recalc->execute();
        $recalc->close();

        // Insert item
        $stmt_items->bind_param("iiids", $order_id, $product_id, $qty, $price, $size);
        $stmt_items->execute();
    }
    $stmt_items->close();

    // 6️⃣ Clear the user's own cart
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $stmt->close();

    // 7️⃣ Insert timeline
    $timeline = $conn->prepare("
        INSERT INTO order_timeline (order_id, status, message)
        VALUES (?, 'Order Placed', 'Your order has been received and is being processed.')
    ");
    $timeline->bind_param("i", $order_id);
    $timeline->execute();
    $timeline->close();

    // ✅ 8️⃣ Clean up out-of-stock products from other users’ carts
    $cleanup = $conn->prepare("
        DELETE ci FROM cart_items ci
        JOIN product_sizes ps ON ci.product_id = ps.product_id AND ci.sizes = ps.size
        WHERE ps.stock = 0
    ");
    $cleanup->execute();
    $cleanup->close();

    $conn->commit();

    $_SESSION['message'] = "✅ Your order has been placed successfully!";
    header("Location: ../thanks.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['checkout_errors'][] = "Order failed: " . $e->getMessage();
    header("Location: ../checkout.php");
    exit;
}
?>
