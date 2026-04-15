<?php
$host = 'localhost';
$user = 'root';
$password = '1243';
$database = 'farmacia_db';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>