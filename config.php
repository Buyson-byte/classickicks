<?php
$host = getenv("MYSQLHOST") ?: getenv("MYSQL_HOST") ?: ($_SERVER['MYSQLHOST'] ?? $_SERVER['MYSQL_HOST'] ?? $_ENV['MYSQLHOST'] ?? $_ENV['MYSQL_HOST'] ?? null);
$user = getenv("MYSQLUSER") ?: getenv("MYSQL_USER") ?: ($_SERVER['MYSQLUSER'] ?? $_SERVER['MYSQL_USER'] ?? $_ENV['MYSQLUSER'] ?? $_ENV['MYSQL_USER'] ?? null);
$pass = getenv("MYSQLPASSWORD") ?: getenv("MYSQL_PASSWORD") ?: ($_SERVER['MYSQLPASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? $_ENV['MYSQLPASSWORD'] ?? $_ENV['MYSQL_PASSWORD'] ?? null);
$dbname = getenv("MYSQLDATABASE") ?: getenv("MYSQL_DATABASE") ?: ($_SERVER['MYSQLDATABASE'] ?? $_SERVER['MYSQL_DATABASE'] ?? $_ENV['MYSQLDATABASE'] ?? $_ENV['MYSQL_DATABASE'] ?? null);
$port = getenv("MYSQLPORT") ?: getenv("MYSQL_PORT") ?: ($_SERVER['MYSQLPORT'] ?? $_SERVER['MYSQL_PORT'] ?? $_ENV['MYSQLPORT'] ?? $_ENV['MYSQL_PORT'] ?? 3306);
$port = (int)$port;
?>