<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $wishlist_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Ensure user can only delete their own wishlist item
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
}

header("Location: ../wishlist.php");
exit;
?>