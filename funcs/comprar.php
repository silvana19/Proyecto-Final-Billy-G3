<?php
require_once "../config/db.php";
session_start();

// 1. Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Debes iniciar sesión para comprar.'); window.location='../login.php';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$producto_id = $_GET['id'];
$usuario_id = $_SESSION['user_id'];
$cantidad = 1; // Por ahora compras de 1 en 1

// 2. Obtener datos del producto y verificar STOCK
$stmt = $conn->prepare("SELECT precio, stock, nombre FROM productos WHERE id = ?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$stmt->bind_result($precio, $stock, $nombre_producto);
$stmt->fetch();
$stmt->close();

if ($stock < $cantidad) {
    echo "<script>alert('Lo sentimos, no hay stock suficiente de $nombre_producto.'); window.location='../index.php';</script>";
    exit;
}

// 3. Iniciar Transacción
$conn->begin_transaction();

try {
    // A. Insertar Venta
    $total = $precio * $cantidad;
    $fecha = date("Y-m-d H:i:s");

    $venta_stmt = $conn->prepare("INSERT INTO ventas (usuario_id, fecha, total) VALUES (?, ?, ?)");
    $venta_stmt->bind_param("isd", $usuario_id, $fecha, $total);
    $venta_stmt->execute();
    $venta_id = $conn->insert_id;
    $venta_stmt->close();

    // B. Insertar Detalle
    $detalle_stmt = $conn->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $detalle_stmt->bind_param("iiid", $venta_id, $producto_id, $cantidad, $precio);
    $detalle_stmt->execute();
    $detalle_stmt->close();

    // C. Restar Stock
    $update_stmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
    $update_stmt->bind_param("ii", $cantidad, $producto_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Confirmar todo
    $conn->commit();

    echo "<script>alert('¡Compra realizada con éxito! Has comprado $nombre_producto.'); window.location='../index.php';</script>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Error al procesar la compra. Inténtalo de nuevo.'); window.location='../index.php';</script>";
}
?>