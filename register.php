<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrarse - Farmacia</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>Crear Cuenta</h2>

<form action="funcs/register_user.php" method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required><br>
    <input type="email" name="correo" placeholder="Correo" required><br>
    <input type="password" name="password" placeholder="Contraseña" required><br>
    <button type="submit">Registrar</button>
</form>

<a href="login.php">Ya tengo cuenta</a>

</body>
</html>
