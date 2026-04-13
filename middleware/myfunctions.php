<?php
include_once("connections/connection.php");

function redirect($url, $message)
{
    $_SESSION['message'] = $message;
    header("Location: " . $url);
    exit();
}

?>