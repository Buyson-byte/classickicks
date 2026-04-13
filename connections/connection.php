<?php
    function connection(){
        
    $host = getenv("MYSQLHOST");
    $user = getenv("MYSQLUSER");
    $pass = getenv("MYSQLPASSWORD");
    $dbname = getenv("MYSQLDATABASE");
    $port = getenv("MYSQLPORT");


    $conn = new mysqli($host, $user, $pass, $dbname, $port); 


    if ($conn->connect_error) {
        echo $conn->connect_error;
    }else{
        return $conn; 
    }
}