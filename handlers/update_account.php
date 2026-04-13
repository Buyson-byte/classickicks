<?php
session_start();
include_once("../connections/connection.php");
$conn = connection();

// 🔒 Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['update_account'])) {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    if (!empty($first_name) && !empty($last_name) && !empty($email)) {
        // Check if customer already has a record
        $check_sql = "SELECT id FROM customer_details WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // ✅ Update existing record
            $update_sql = "UPDATE customer_details 
                           SET first_name = ?, last_name = ?, email = ?, phone = ?
                           WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
            $update_stmt->execute();

            $_SESSION['message'] = "Personal information updated successfully!";
            $_SESSION['message_class'] = "alert-success";
        } else {
            // ✅ Insert new record
            $insert_sql = "INSERT INTO customer_details (user_id, first_name, last_name, email, phone)
                           VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("issss", $user_id, $first_name, $last_name, $email, $phone);
            $insert_stmt->execute();

            $_SESSION['message'] = "Personal information added successfully!";
            $_SESSION['message_class'] = "alert-success";
        }
    } else {
        $_SESSION['message'] = "Please fill in all required fields.";
        $_SESSION['message_class'] = "alert-danger";
    }
}

header("Location: ../my_account.php");
exit;