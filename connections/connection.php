<?php
function connection() {
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $dbname = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT');

    $conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>