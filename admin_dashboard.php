<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo "<script>
            alert('Acceso denegado. Debes ser administrador.');
            window.location='login.php';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin_style.css"> <!-- New Style -->
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Farmacia Admin</a>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    Hola, <?php echo $_SESSION['nombre']; ?>
                </span>
                <a href="funcs/logout.php" class="btn btn-danger-custom btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1>Bienvenido al Panel de Control</h1>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card card-custom mb-3">
                            <div class="card-header card-header-custom">Productos</div>
                            <div class="card-body">
                                <h5 class="card-title">Gestionar Inventario</h5>
                                <a href="admin_products.php" class="btn btn-primary-custom">Ir a Productos</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <div class="card card-custom">
                            <div class="card-header card-header-custom">Últimas Ventas</div>
                            <div class="card-body">
                                <table class="table table-custom table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID Venta</th>
                                            <th>Usuario</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Detalles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        require_once "config/db.php";
                                        $sql = "SELECT v.id, u.nombre as usuario, v.fecha, v.total 
                                            FROM ventas v 
                                            JOIN usuarios u ON v.usuario_id = u.id 
                                            ORDER BY v.fecha DESC LIMIT 10";
                                        $res = $conn->query($sql);

                                        if ($res->num_rows > 0):
                                            while ($row = $res->fetch_assoc()):
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo $row['usuario']; ?></td>
                                                    <td><?php echo $row['fecha']; ?></td>
                                                    <td>$<?php echo $row['total']; ?></td>
                                                    <td>
                                                        <!-- Podríamos agregar un modal aquí para ver los productos -->
                                                        <span class="badge bg-secondary">Ver productos</span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No hay ventas registradas</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>