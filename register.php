<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">

<?php if (isset($_GET["error"]) && $_GET["error"] === "correo_duplicado") : ?>
    <div class="alert alert-danger text-center">
        El correo ingresado ya está registrado. Intenta con otro.
    </div>
<?php endif; ?>

<?php if (isset($_GET["success"]) && $_GET["success"] === "registrado") : ?>
    <div class="alert alert-success text-center">
        Cuenta creada correctamente. ¡Ahora puedes iniciar sesión!
    </div>
<?php endif; ?>


<div class="card p-4 shadow-lg" style="width: 380px;">
    <h3 class="text-center mb-3">Crear Cuenta</h3>

    <?php if(isset($_GET["error"])): ?>
    <div class="alert alert-danger">Este correo ya está registrado.</div>
    <?php endif; ?>

    <form action="funcs/register_user.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="correo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <button class="btn btn-success w-100">Registrarse</button>

        <p class="mt-3 text-center">
            ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
        </p>
    </form>
</div>

</body>


</html>
