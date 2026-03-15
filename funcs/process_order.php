<?php
// Leer php://input ANTES de session_start()
$raw_input = file_get_contents('php://input');

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../config/db.php";

header('Content-Type: application/json');

// ── 1. Método correcto ────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// ── 2. Usuario logueado ───────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para realizar un pedido']);
    exit;
}

// ── 3. Carrito no vacío ───────────────────────
if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Tu carrito está vacío']);
    exit;
}

// ── 4. Parsear input ──────────────────────────
$input    = json_decode($raw_input, true) ?? [];
$cart     = $_SESSION['cart'];
$delivery = $input['delivery'] ?? 'home';
$payment  = $input['payment']  ?? 'cash';
$shipping = $input['shipping'] ?? [];
$user_id  = intval($_SESSION['user_id']);

// ── 5. Mapear campos del formulario a columnas de TU tabla ──
// Tu tabla pedidos tiene: usuario_id, nombre, email, telefono,
// direccion, ciudad, codigo_postal, metodo_envio, metodo_pago, total, estado, fecha_pedido

$nombre        = htmlspecialchars($shipping['name']     ?? ($_SESSION['nombre'] ?? ''));
$email         = htmlspecialchars($shipping['email']    ?? ($_SESSION['email']  ?? ''));
$telefono      = htmlspecialchars($shipping['phone']    ?? '');
$direccion     = htmlspecialchars($shipping['address']  ?? '');
$ciudad        = htmlspecialchars($shipping['city']     ?? '');
$codigo_postal = htmlspecialchars($shipping['province'] ?? '');

// Si es pickup, usar datos de recogida
if ($delivery === 'pickup') {
    $nombre   = htmlspecialchars($shipping['name']  ?? ($_SESSION['nombre'] ?? ''));
    $telefono = htmlspecialchars($shipping['phone'] ?? '');
    $direccion  = 'Retiro en tienda - Av. Principal #123, Santiago';
    $ciudad     = 'Santiago';
    $codigo_postal = 'RD';
}

// Mapear método de envío y pago a tus valores
$metodo_envio = ($delivery === 'home') ? 'Envío a domicilio' : 'Retiro en tienda';
$metodo_pago  = match($payment) {
    'card'     => 'Tarjeta de crédito/débito',
    'transfer' => 'Transferencia bancaria',
    'cash'     => 'Efectivo',
    default    => 'Efectivo'
};

// ── 6. Calcular total ─────────────────────────
$subtotal      = 0;
foreach ($cart as $item) {
    $subtotal += floatval($item['price']) * intval($item['quantity']);
}
$shipping_cost = ($delivery === 'home') ? 150.00 : 0.00;
$total         = $subtotal + $shipping_cost;

// ── 7. Verificar stock ────────────────────────
foreach ($cart as $item) {
    $pid  = intval($item['id']);
    $qty  = intval($item['quantity']);

    $stmt = $conn->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => "Producto ID $pid no encontrado"]);
        exit;
    }
    if (intval($row['stock']) < $qty) {
        echo json_encode(['success' => false,
            'error' => "Stock insuficiente para \"{$row['nombre']}\". Disponible: {$row['stock']}"
        ]);
        exit;
    }
}

// ── 8. Insertar en tu tabla pedidos ──────────
// Columnas exactas: usuario_id, nombre, email, telefono, direccion,
//                   ciudad, codigo_postal, metodo_envio, metodo_pago, total, estado, fecha_pedido
$stmt = $conn->prepare(
    "INSERT INTO pedidos 
     (usuario_id, nombre, email, telefono, direccion, ciudad,
      codigo_postal, metodo_envio, metodo_pago, total, estado, fecha_pedido)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error prepare pedidos: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    "issssssssd",
    $user_id, $nombre, $email, $telefono,
    $direccion, $ciudad, $codigo_postal,
    $metodo_envio, $metodo_pago, $total
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Error execute pedidos: ' . $stmt->error]);
    exit;
}

$order_id = $conn->insert_id;

// ── 9. Insertar en pedido_items ───────────────
// Primero ver qué columnas tiene pedido_items
$check = $conn->query("DESCRIBE pedido_items");
$cols  = [];
while ($row = $check->fetch_assoc()) $cols[] = $row['Field'];

// Intentar insertar con las columnas más comunes
// (ajusta según tu estructura real)
if (in_array('pedido_id', $cols) && in_array('producto_id', $cols)) {
    $item_stmt = $conn->prepare(
        "INSERT INTO pedido_items (pedido_id, producto_id, nombre, precio, cantidad)
         VALUES (?, ?, ?, ?, ?)"
    );
} else if (in_array('venta_id', $cols)) {
    // Por si usa otra nomenclatura
    $item_stmt = $conn->prepare(
        "INSERT INTO pedido_items (venta_id, producto_id, nombre, precio, cantidad)
         VALUES (?, ?, ?, ?, ?)"
    );
} else {
    // Fallback: solo registrar el pedido sin items detallados
    $item_stmt = null;
}

foreach ($cart as $item) {
    $pid    = intval($item['id']);
    $qty    = intval($item['quantity']);
    $pname  = $item['name'];
    $pprice = floatval($item['price']);

    if ($item_stmt) {
        $item_stmt->bind_param("iisdi", $order_id, $pid, $pname, $pprice, $qty);
        $item_stmt->execute();
    }

    // Descontar stock
    $conn->query(
        "UPDATE productos SET stock = stock - $qty WHERE id = $pid AND stock >= $qty"
    );
}

// ── 10. Limpiar carrito ───────────────────────
$_SESSION['cart']          = [];
$_SESSION['last_order_id'] = $order_id;

echo json_encode([
    'success'  => true,
    'order_id' => $order_id,
    'total'    => $total
]);
?>