<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['update_address'])) {
    $address_id   = intval($_POST['address_id']);
    $address_name = trim($_POST['address_name']);
    $house_street = trim($_POST['house_street']);
    $barangay     = trim($_POST['barangay']);
    $city         = trim($_POST['city']);
    $province     = trim($_POST['province']);
    $region       = trim($_POST['region']);
    $postal_code  = trim($_POST['postal_code']);
    $is_default   = isset($_POST['is_default']) ? 1 : 0;

    // ✅ Country always Philippines
    $country = "Philippines";

    // If user checks "Set as Default", unset old defaults
    if ($is_default) {
        $conn->query("UPDATE address SET is_default = 0 WHERE user_id = $user_id");
    }

    $update_sql = "UPDATE address 
        SET address_name=?, house_street=?, barangay=?, city=?, province=?, region=?, postal_code=?, country=?, is_default=?
        WHERE address_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param(
        "ssssssssiii",
        $address_name,
        $house_street,  
        $barangay,
        $city,
        $province,
        $region,
        $postal_code,
        $country,
        $is_default,
        $address_id,
        $user_id
    );


    if ($stmt->execute()) {
        $_SESSION['message'] = "Address updated successfully!";
        $_SESSION['message_class'] = "alert-success";
    } else {
        $_SESSION['message'] = "Error updating address. Please try again.";
        $_SESSION['message_class'] = "alert-danger";
    }

    header("Location: ../my_account.php");
    exit;
} else {
    header("Location: ../my_account.php");
    exit;
}