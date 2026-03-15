<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../config/db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$venta_id = intval($_GET['venta_id'] ?? 0);
if (!$venta_id) { echo json_encode([]); exit; }

// Buscar en detalle_ventas o detalle_pedido según lo que exista
$tables = [];
$r = $conn->query("SHOW TABLES");
while ($row = $r->fetch_array()) $tables[] = $row[0];

$items = [];

if (in_array('detalle_ventas', $tables)) {
    $result = $conn->query(
        "SELECT dv.cantidad, dv.precio_unitario as precio, p.nombre, p.imagen
         FROM detalle_ventas dv
         JOIN productos p ON dv.producto_id = p.id
         WHERE dv.venta_id = $venta_id"
    );
    if ($result) {
        while ($row = $result->fetch_assoc()) $items[] = $row;
    }
}

// Si no encontró nada, intentar con detalle_pedido
if (empty($items) && in_array('detalle_pedido', $tables)) {
    $result = $conn->query(
        "SELECT dp.cantidad, dp.precio_unitario as precio, p.nombre, p.imagen
         FROM detalle_pedido dp
         JOIN productos p ON dp.producto_id = p.id
         WHERE dp.venta_id = $venta_id"
    );
    if ($result) {
        while ($row = $result->fetch_assoc()) $items[] = $row;
    }
}

echo json_encode($items);
?>