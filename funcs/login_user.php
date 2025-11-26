<?php
session_start();
require "conexion.php";

$correo = $_POST['correo'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios WHERE correo = ?";
$query = $mysqli->prepare($sql);
$query->bind_param("s", $correo);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['usuario'] = $user['nombre'];
        header("Location: ../index.html");
    } else {
        echo "Contraseña incorrecta.";
    }
} else {
    echo "Correo no encontrado.";
}
?>
