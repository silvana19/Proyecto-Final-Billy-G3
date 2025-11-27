<?php
require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // ⛔ Si $conn no existe, viene el error que tienes ahora
    global $conn;

    // Verificar si el correo ya existe
    $check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $check->bind_param("s", $correo);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Ese correo ya está registrado'); window.location='../register.php';</script>";
        exit;
    }

    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $correo, $pass);

    if ($stmt->execute()) {
        echo "<script>alert('Cuenta creada correctamente'); window.location='../login.php';</script>";
    } else {
        echo "<script>alert('Error al registrar: " . $stmt->error . "'); window.location='../register.php';</script>";
    }
}
?>
