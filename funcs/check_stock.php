<?php
require_once "../config/db.php";

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Producto no válido']);
    exit;
}

// Obtener stock del producto
$sql = "SELECT nombre, stock FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $producto = $result->fetch_assoc();
    
    if ($producto['stock'] >= $quantity) {
        echo json_encode([
            'success' => true, 
            'message' => 'Stock disponible',
            'stock' => $producto['stock']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "Solo hay {$producto['stock']} unidades de '{$producto['nombre']}' disponibles"
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
}
?>