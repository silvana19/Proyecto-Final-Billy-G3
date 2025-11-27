<?php
$conn = new mysqli('localhost', 'root', '', 'farmacia_db');

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
