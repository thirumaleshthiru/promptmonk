<?php
 
$host = 'localhost';   
$userr = 'root';
$password = '';
$database = 'promptmonk';   
$port = 3307;
 $conn = new mysqli($host, $userr, $password, $database,$port);

 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

 $conn->set_charset("utf8mb4");

 ?>
