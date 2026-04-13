<?php
function envValue($key) {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?? null;
}

function connection() {
    $host = envValue('MYSQLHOST');
    $user = envValue('MYSQLUSER');
    $pass = envValue('MYSQLPASSWORD');
    $dbname = envValue('MYSQLDATABASE');
    $port = envValue('MYSQLPORT');

    if (!$host || !$user || !$dbname || !$port) {
        die(
            "Missing DB env vars.<br>" .
            "MYSQLHOST: " . var_export($host, true) . "<br>" .
            "MYSQLUSER: " . var_export($user, true) . "<br>" .
            "MYSQLDATABASE: " . var_export($dbname, true) . "<br>" .
            "MYSQLPORT: " . var_export($port, true)
        );
    }

    $conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>