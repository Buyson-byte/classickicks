<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = intval($_POST['order_id']);
    $status   = strtolower($_POST['status']);

    // ✅ Allowed statuses for admin updates
    $valid_status = ['pending', 'packed', 'shipped', 'completed', 'canceled'];
    if (!in_array($status, $valid_status)) {
        header("Location: ../admin.php#orders");
        exit;
    }

    // ✅ Safety check: Prevent updates to already canceled orders
    $check = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $check->bind_param("i", $order_id);
    $check->execute();
    $check->bind_result($current_status);
    $check->fetch();
    $check->close();

    if (strtolower($current_status) === 'canceled') {
        $_SESSION['message'] = "⚠️ This order was already canceled and cannot be modified.";
        header("Location: ../admin.php#orders");
        exit;
    }

    // 🧮 Restore stock only when admin cancels manually (user can’t cancel anymore)
    if ($status === 'canceled') {
        $sql = "SELECT product_id, quantity, size FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items = $stmt->get_result();
        $stmt->close();

        // Restore per-size stock first
        $restoreSize = $conn->prepare("
            UPDATE product_sizes SET stock = stock + ? 
            WHERE product_id = ? AND size = ?
        ");

        // Then recalc total quantity in products table
        $recalc = $conn->prepare("
            UPDATE products 
            SET quantity = (SELECT COALESCE(SUM(stock),0) FROM product_sizes WHERE product_id = ?)
            WHERE id = ?
        ");

        while ($row = $items->fetch_assoc()) {
            $qty  = (int)$row['quantity'];
            $pid  = (int)$row['product_id'];
            $size = $row['size'];

            $restoreSize->bind_param("iis", $qty, $pid, $size);
            $restoreSize->execute();

            // Recalculate main product quantity
            $recalc->bind_param("ii", $pid, $pid);
            $recalc->execute();
        }

        $restoreSize->close();
        $recalc->close();
    }

    // 📝 Update order and order_items status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();

    // 🕓 Add to order_timeline (avoid duplicates)
    $timelineSteps = [
        'pending'   => ['Order Placed', 'Your order has been received and is being processed.'],
        'packed'    => ['Packed', 'Your order has been packed and is ready for shipping.'],
        'shipped'   => ['Shipped', 'Your order is on the way.'],
        'completed' => ['Delivered', 'Your order has been delivered successfully.'],
        'canceled'  => ['Canceled', 'Your order has been canceled.'],
    ];

    [$timeline_status, $message] = $timelineSteps[$status];

    $check = $conn->prepare("SELECT COUNT(*) FROM order_timeline WHERE order_id = ? AND status = ?");
    $check->bind_param("is", $order_id, $timeline_status);
    $check->execute();
    $check->bind_result($exists);
    $check->fetch();
    $check->close();

    if ($exists == 0) {
        $stmt = $conn->prepare("INSERT INTO order_timeline (order_id, status, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $order_id, $timeline_status, $message);
        $stmt->execute();
        $stmt->close();
    }

    // 🪄 Auto-fill all previous steps when marking as Completed
    if ($status === 'completed') {
        $previousSteps = [
            ['Order Placed', 'Your order has been received and is being processed.'],
            ['Packed', 'Your order has been packed and is ready for shipping.'],
            ['Shipped', 'Your order is on the way.'],
            ['Delivered', 'Your order has been delivered successfully.'],
        ];

        foreach ($previousSteps as [$step, $msg]) {
            $check = $conn->prepare("SELECT COUNT(*) FROM order_timeline WHERE order_id = ? AND status = ?");
            $check->bind_param("is", $order_id, $step);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count == 0) {
                $stmt = $conn->prepare("INSERT INTO order_timeline (order_id, status, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $order_id, $step, $msg);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

$_SESSION['message'] = "✅ Order status updated successfully!";
header("Location: ../admin.php#orders");
exit;
?>
