<?php
require "conexion.php";

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombre, correo, password) VALUES (?, ?, ?)";
$query = $mysqli->prepare($sql);
$query->bind_param("sss", $nombre, $correo, $password);

if ($query->execute()) {
    header("Location: ../login.php?msg=Cuenta creada");
} else {
    echo "Error: " . $mysqli->error;
}
?>
