<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Farmacia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Iniciar Sesión</h2>

<form action="funcs/login_user.php" method="POST">
    <input type="email" name="correo" placeholder="Correo" required><br>
    <input type="password" name="password" placeholder="Contraseña" required><br>
    <button type="submit">Entrar</button>
</form>

<a href="register.php">Crear cuenta</a>

</body>
</html>
