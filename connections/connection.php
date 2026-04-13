<?php
    function connection(){
        
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "kicks_n_style"; 


    $conn = new mysqli($servername, $username, $password, $database); 


    if ($conn->connect_error) {
        echo $conn->connect_error;
    }else{
        return $conn; 
    }
}