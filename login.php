<?php session_start(); ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-dark text-white d-flex justify-content-center align-items-center vh-100">

<div class="card p-4 shadow-lg" style="width: 350px;">
    <h3 class="text-center mb-3">Iniciar Sesión</h3>

    <form action="funcs/login_user.php" method="POST">

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="correo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-success w-100" type="submit">Entrar</button>

        <p class="mt-3 text-center">
            ¿No tienes cuenta? <a href="register.php">Regístrate</a>
        </p>

    </form>
</div>

</body>
</html>
