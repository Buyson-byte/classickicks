<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_POST['order_id']) && isset($_SESSION['user_id'])) {
    $order_id = intval($_POST['order_id']);
    $user_id  = $_SESSION['user_id'];

    // Check if the order belongs to the user and can still be canceled
    $sql = "SELECT order_date, status FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $order_time = strtotime($order['order_date']);
        $time_diff = time() - $order_time;

        if ($time_diff <= 3600 && strtolower($order['status']) == 'pending') {
            // Start a database transaction
            $conn->begin_transaction();

            try {
                // 1️⃣ Fetch all ordered items (to restore their stock)
                $item_sql = "SELECT product_id, quantity, size FROM order_items WHERE order_id = ?";
                $item_stmt = $conn->prepare($item_sql);
                $item_stmt->bind_param("i", $order_id);
                $item_stmt->execute();
                $items = $item_stmt->get_result();

                while ($row = $items->fetch_assoc()) {
                    $product_id = $row['product_id'];
                    $qty        = (int)$row['quantity'];
                    $size       = $row['size'];

                    // 2️⃣ Restore the stock for the specific size
                    $restore_stock = $conn->prepare("
                        UPDATE product_sizes 
                        SET stock = stock + ? 
                        WHERE product_id = ? AND size = ?
                    ");
                    $restore_stock->bind_param("iis", $qty, $product_id, $size);
                    $restore_stock->execute();
                    $restore_stock->close();

                    // 3️⃣ Recalculate total stock in main products table
                    $recalc = $conn->prepare("
                        UPDATE products
                        SET quantity = (SELECT COALESCE(SUM(stock), 0) FROM product_sizes WHERE product_id = ?)
                        WHERE id = ?
                    ");
                    $recalc->bind_param("ii", $product_id, $product_id);
                    $recalc->execute();
                    $recalc->close();
                }

                // 4️⃣ Mark the order as canceled
                $update = $conn->prepare("UPDATE orders SET status = 'Canceled' WHERE id = ?");
                $update->bind_param("i", $order_id);
                $update->execute();
                $update->close();

                // 5️⃣ Log into order_timeline
                $message = "Your order has been canceled and stock has been restored.";
                $timeline = $conn->prepare("
                    INSERT INTO order_timeline (order_id, status, message)
                    VALUES (?, 'Canceled', ?)
                ");
                $timeline->bind_param("is", $order_id, $message);
                $timeline->execute();
                $timeline->close();

                // Commit the transaction
                $conn->commit();

                $_SESSION['message'] = "Order canceled successfully. Stock restored.";
                $_SESSION['message_class'] = "alert-success";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = "Error restoring stock: " . $e->getMessage();
                $_SESSION['message_class'] = "alert-danger";
            }
        } else {
            $_SESSION['message'] = "Order can no longer be canceled.";
            $_SESSION['message_class'] = "alert-warning";
        }
    } else {
        $_SESSION['message'] = "Order not found.";
        $_SESSION['message_class'] = "alert-danger";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_class'] = "alert-danger";
}

header("Location: ../my_account.php");
exit;
?>
