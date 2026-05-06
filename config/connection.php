<?php
    $server = "127.0.0.1";
    $user = "root";
    $password = "";
    $database = "attendance";
    
    $conn = new mysqli($server, $user, $password, $database);
    if(!$conn) die ("Connection error: " . $conn->error);
?>