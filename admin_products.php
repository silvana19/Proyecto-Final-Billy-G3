<?php
require_once "config/db.php";
session_start();

// Validar Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 1. Agregar Producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];

    // Manejo de Imagen
    $imagen = 'assets/img/default.jpg'; // Default

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_destino = "assets/img/" . $nombre_archivo;

        // Mover el archivo
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            $imagen = $ruta_destino;
        }
    }

    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, imagen) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $nombre, $descripcion, $precio, $stock, $imagen);

    if ($stmt->execute()) {
        $msg = "Producto agregado correctamente.";
    } else {
        $error = "Error al agregar: " . $stmt->error;
    }
}

// 2. Actualizar Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = $_POST['id'];
    $nuevo_stock = $_POST['stock'];

    $stmt = $conn->prepare("UPDATE productos SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $nuevo_stock, $id);
    if ($stmt->execute()) {
        $msg = "Stock actualizado.";
    } else {
        $error = "Error al actualizar.";
    }
}

// 3. Eliminar Producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $msg = "Producto eliminado correctamente.";
    } else {
        $error = "Error al eliminar.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin_style.css"> <!-- Nuevo estilo -->
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-custom mb-4">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Farmacia Admin</a>
            <div class="d-flex">
                <a href="admin_dashboard.php" class="btn btn-outline-light me-2">Volver</a>
                <a href="funcs/logout.php" class="btn btn-danger-custom">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container">

        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="section-title text-center mb-4" style="color: var(--primary-green);">Gestionar Inventario
                </h2>

                <?php if (isset($msg))
                    echo "<div class='alert alert-success'>$msg</div>"; ?>
                <?php if (isset($error))
                    echo "<div class='alert alert-danger'>$error</div>"; ?>

                <!-- Formulario para Agregar Producto -->
                <div class="card card-custom mb-4">
                    <div class="card-header card-header-custom">Agregar Nuevo Producto</div>
                    <div class="card-body">
                        <form method="POST" class="row g-3" enctype="multipart/form-data">
                            <div class="col-md-3">
                                <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="descripcion" class="form-control" placeholder="Descripción"
                                    required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" name="precio" class="form-control"
                                    placeholder="Precio ($)" required>
                            </div>
                            <div class="col-md-1">
                                <input type="number" name="stock" class="form-control" placeholder="Stock" required>
                            </div>
                            <div class="col-md-2">
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" name="add_product" class="btn btn-primary-custom w-100">+</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="card card-custom">
                    <div class="card-body p-0">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM productos ORDER BY id DESC");
                                while ($row = $res->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <strong><?php echo $row['nombre']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $row['descripcion']; ?></small>
                                        </td>
                                        <td>$<?php echo $row['precio']; ?></td>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="number" name="stock" value="<?php echo $row['stock']; ?>"
                                                    class="form-control form-control-sm me-2" style="width: 70px;">
                                                <button type="submit" name="update_stock"
                                                    class="btn btn-sm btn-outline-success">↻</button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="admin_edit_product.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-primary-custom btn-sm"
                                                    style="padding: 5px 15px; text-decoration: none;">Editar</a>
                                                <form method="POST"
                                                    onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_product"
                                                        class="btn btn-danger-custom btn-sm">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>