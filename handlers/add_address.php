<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['add_address'])) {
    $address_name = trim($_POST['address_name']);
    $house_street = trim($_POST['house_street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $region = trim($_POST['region']);
    $postal_code = trim($_POST['postal_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // If set as default, unset previous defaults
    if ($is_default) {
        $conn->query("UPDATE address SET is_default = 0 WHERE user_id = $user_id");
    }

    $sql = "INSERT INTO address 
            (user_id, address_name, house_street, barangay, city, province, region, postal_code, country, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Philippines', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssi", 
        $user_id, $address_name, $house_street, $barangay, $city, 
        $province, $region, $postal_code, $is_default
    );

    if ($stmt->execute()) {
        $_SESSION['message'] = "Address added successfully!";
        $_SESSION['message_class'] = "alert-success";
    } else {
        $_SESSION['message'] = "Failed to add address. Please try again.";
        $_SESSION['message_class'] = "alert-danger";
    }

    // Always redirect back to my_account.php
    header("Location: ../my_account.php");
    exit;
} else {
    // If accessed directly without form submission
    header("Location: ../my_account.php");
    exit;
}