<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Validar datos requeridos
$required = ['nombre', 'email', 'telefono', 'direccion', 'ciudad', 'codigo_postal', 'envio', 'pago'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => 'Campo ' . $field . ' es requerido']);
        exit;
    }
}

// Verificar que hay items en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

// Iniciar transacción
$conn->begin_transaction();

try {
    $usuario_id = $_SESSION['user_id'];
    $nombre = $conn->real_escape_string($data['nombre']);
    $email = $conn->real_escape_string($data['email']);
    $telefono = $conn->real_escape_string($data['telefono']);
    $direccion = $conn->real_escape_string($data['direccion']);
    $ciudad = $conn->real_escape_string($data['ciudad']);
    $codigo_postal = $conn->real_escape_string($data['codigo_postal']);
    $metodo_envio = $conn->real_escape_string($data['envio']);
    $metodo_pago = $conn->real_escape_string($data['pago']);
    
    // Calcular total de productos
    $subtotal = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
    }
    
    // Calcular costo de envío
    $costo_envio = 0;
    if ($metodo_envio == 'estandar') {
        $costo_envio = 150;
    } elseif ($metodo_envio == 'express') {
        $costo_envio = 250;
    }
    
    $total = $subtotal + $costo_envio;
    
    // Insertar pedido
    $stmt = $conn->prepare("
        INSERT INTO pedidos (
            usuario_id, nombre, email, telefono, direccion, ciudad, 
            codigo_postal, metodo_envio, metodo_pago, total, estado, fecha_pedido
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    
    $stmt->bind_param(
        "issssssssd",
        $usuario_id, $nombre, $email, $telefono, $direccion, $ciudad,
        $codigo_postal, $metodo_envio, $metodo_pago, $total
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear el pedido");
    }
    
    $pedido_id = $conn->insert_id;
    
    // Insertar detalles del pedido y actualizar stock
    foreach ($_SESSION['carrito'] as $item) {
        // Verificar stock actual
        $stmt_stock = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt_stock->bind_param("i", $item['id']);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        $producto = $result_stock->fetch_assoc();
        
        if ($producto['stock'] < $item['cantidad']) {
            throw new Exception("Stock insuficiente para " . $item['nombre']);
        }
        
        // Insertar detalle
        $stmt_detalle = $conn->prepare("
            INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?)
        ");
        $stmt_detalle->bind_param("iiid", $pedido_id, $item['id'], $item['cantidad'], $item['precio']);
        
        if (!$stmt_detalle->execute()) {
            throw new Exception("Error al guardar detalles del pedido");
        }
        
        // Actualizar stock
        $stmt_update = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmt_update->bind_param("ii", $item['cantidad'], $item['id']);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar stock");
        }
    }
    
    // Commit de la transacción
    $conn->commit();
    
    // Limpiar carrito
    unset($_SESSION['carrito']);
    
    echo json_encode([
        'success' => true,
        'message' => '¡Pedido realizado con éxito!',
        'pedido_id' => $pedido_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>