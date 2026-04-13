<?php
include_once("connections/connection.php");

if (!isset($_SESSION['user_id'])) {
    // Not logged in
    $_SESSION['message'] = "Please login to continue.";
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    // Logged in but not admin
    $_SESSION['message'] = "You are not authorized to access this page!";
    header("Location: index.php");
    exit;
}