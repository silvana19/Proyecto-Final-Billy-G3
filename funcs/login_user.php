<?php
require_once "../config/db.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre, $hash, $rol);

    if ($stmt->num_rows == 1) {
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['rol'] = $rol;

            $redirect_url = ($rol === 'admin') ? '../admin_dashboard.php' : '../index.php';

            echo "<script>
                    alert('Bienvenido $nombre');
                    window.location='$redirect_url';
                  </script>";
        } else {
            echo "<script>
                    alert('Contraseña incorrecta');
                    window.location='../login.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('Ese correo no existe');
                window.location='../login.php';
              </script>";
    }
}
?>