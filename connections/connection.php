<?php
include_once(__DIR__ . "/../config.php");

function connection(){
    global $host, $user, $pass, $dbname, $port;

    if (!$host || !$user || !$dbname) {
        die("Database configuration missing. Please set MYSQLHOST, MYSQLUSER, and MYSQLDATABASE environment variables.");
    }

    $conn = new mysqli($host, $user, $pass, $dbname, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}