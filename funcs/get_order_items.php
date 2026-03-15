<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../config/db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$pedido_id = intval($_GET['pedido_id'] ?? 0);
if (!$pedido_id) { echo json_encode([]); exit; }

// Intentar obtener items con imagen del producto
$result = $conn->query(
    "SELECT pi.nombre, pi.precio, pi.cantidad, p.imagen
     FROM pedido_items pi
     LEFT JOIN productos p ON pi.producto_id = p.id
     WHERE pi.pedido_id = $pedido_id"
);

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode($items);
?>