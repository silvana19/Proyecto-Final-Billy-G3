<?php
$mysqli = new mysqli('localhost', 'root', '', 'farmacia_db');

if ($mysqli->connect_errno) {
    die("Error al conectar: " . $mysqli->connect_error);
}
?>
