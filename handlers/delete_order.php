<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_class'] = "alert-danger";
    header("Location: ../my_account.php");
    exit;
}

$order_id = intval($_POST['order_id']);

// Verify the order belongs to the logged-in user
$verify_sql = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $order_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['message'] = "You cannot delete this order.";
    $_SESSION['message_class'] = "alert-danger";
    header("Location: ../my_account.php");
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete order items first
    $delete_items_sql = "DELETE FROM order_items WHERE order_id = ?";
    $delete_items_stmt = $conn->prepare($delete_items_sql);
    $delete_items_stmt->bind_param("i", $order_id);
    $delete_items_stmt->execute();

    // Delete the order itself
    $delete_order_sql = "DELETE FROM orders WHERE id = ?";
    $delete_order_stmt = $conn->prepare($delete_order_sql);
    $delete_order_stmt->bind_param("i", $order_id);
    $delete_order_stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['message'] = "Order deleted successfully.";
    $_SESSION['message_class'] = "alert-success";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['message'] = "Failed to delete order: " . $e->getMessage();
    $_SESSION['message_class'] = "alert-danger";
}

header("Location: ../my_account.php");
exit;