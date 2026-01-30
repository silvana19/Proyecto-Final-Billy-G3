<?php
require_once "config/db.php";
session_start();

// Validar Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_products.php");
    exit;
}

$id = $_GET['id'];
$msg = "";
$error = "";

// Obtener datos actuales
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

if (!$producto) {
    echo "Producto no encontrado.";
    exit;
}

// Procesar Formulario de Edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];

    // Mantener imagen actual por defecto
    $imagen = $producto['imagen'];

    // Si se sube una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_destino = "assets/img/" . $nombre_archivo;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            $imagen = $ruta_destino;
        }
    }

    $update = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, imagen = ? WHERE id = ?");
    $update->bind_param("ssdisi", $nombre, $descripcion, $precio, $stock, $imagen, $id);

    if ($update->execute()) {
        $msg = "Producto actualizado correctamente.";
        // Actualizar datos en memoria para mostrar lo nuevo
        $producto['nombre'] = $nombre;
        $producto['descripcion'] = $descripcion;
        $producto['precio'] = $precio;
        $producto['stock'] = $stock;
        $producto['imagen'] = $imagen;
    } else {
        $error = "Error al actualizar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin_style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Farmacia Admin</a>
            <div class="d-flex">
                <a href="admin_products.php" class="btn btn-outline-light me-2">Volver</a>
                <a href="funcs/logout.php" class="btn btn-danger-custom">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom">
                    <div class="card-header card-header-custom">Editar Producto</div>
                    <div class="card-body">

                        <?php if ($msg)
                            echo "<div class='alert alert-success'>$msg</div>"; ?>
                        <?php if ($error)
                            echo "<div class='alert alert-danger'>$error</div>"; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control"
                                    value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control"
                                    required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Precio ($)</label>
                                    <input type="number" step="0.01" name="precio" class="form-control"
                                        value="<?php echo $producto['precio']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stock</label>
                                    <input type="number" name="stock" class="form-control"
                                        value="<?php echo $producto['stock']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Imagen actual</label><br>
                                <img src="<?php echo $producto['imagen']; ?>" alt="Imagen actual"
                                    style="width: 150px; height: 100px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                                <div class="form-text">Sube una nueva imagen solo si deseas cambiar la actual.</div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100">Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>