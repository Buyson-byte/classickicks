<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['delete_address'])) {
    $address_id = intval($_POST['address_id']);
    $user_id    = $_SESSION['user_id'];

    // Check if address belongs to user
    $check = $conn->prepare("SELECT * FROM address WHERE address_id = ? AND user_id = ?");
    $check->bind_param("ii", $address_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM address WHERE address_id = ? AND user_id = ?");
        $delete->bind_param("ii", $address_id, $user_id);

        if ($delete->execute()) {
            $_SESSION['message'] = "Address deleted successfully!";
            $_SESSION['message_class'] = "alert-success";
        } else {
            $_SESSION['message'] = "Error deleting address. Please try again.";
            $_SESSION['message_class'] = "alert-danger";
        }
    } else {
        $_SESSION['message'] = "Invalid address or unauthorized action.";
        $_SESSION['message_class'] = "alert-danger";
    }

    header("Location: ../my_account.php");
    exit;
}
?>