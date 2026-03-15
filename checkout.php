<?php
session_start();
require_once "config/db.php";

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener el carrito de la sesión
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Si el carrito está vacío, redirigir
if (empty($cart)) {
    header("Location: index.php#S3");
    exit();
}

// Calcular total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Procesar el pedido si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_pedido'])) {
    $errores = [];
    
    // Verificar stock disponible para cada producto
    foreach ($cart as $item) {
        $sql = "SELECT stock FROM productos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if ($producto['stock'] < $item['quantity']) {
            $errores[] = "El producto '{$item['name']}' solo tiene {$producto['stock']} unidades disponibles";
        }
    }
    
    // Si no hay errores, procesar el pedido
    if (empty($errores)) {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar el pedido
            $sql = "INSERT INTO pedidos (usuario_id, total, fecha, estado) VALUES (?, ?, NOW(), 'pendiente')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("id", $_SESSION['user_id'], $total);
            $stmt->execute();
            $pedido_id = $conn->insert_id;
            
            // Insertar detalles del pedido y actualizar stock
            foreach ($cart as $item) {
                // Insertar detalle
                $sql = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiid", $pedido_id, $item['id'], $item['quantity'], $item['price']);
                $stmt->execute();
                
                // Actualizar stock
                $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $item['quantity'], $item['id']);
                $stmt->execute();
            }
            
            // Confirmar transacción
            $conn->commit();
            
            // Limpiar carrito
            unset($_SESSION['cart']);
            
            // Redirigir a página de éxito
            header("Location: pedido_exitoso.php?id=" . $pedido_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errores[] = "Error al procesar el pedido: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Farmacia del Amor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-elevation-medium);
        }
        .checkout-title {
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 30px;
        }
        .checkout-items {
            margin-bottom: 30px;
        }
        .checkout-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .checkout-item-info {
            flex: 2;
        }
        .checkout-item-name {
            font-weight: 600;
            color: var(--color-dark);
        }
        .checkout-item-price {
            color: var(--color-accent);
            font-weight: 500;
        }
        .checkout-item-quantity {
            background: #f5f5f5;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .checkout-item-subtotal {
            font-weight: 700;
            color: var(--color-primary);
            min-width: 100px;
            text-align: right;
        }
        .checkout-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            font-size: 1.3rem;
            font-weight: 700;
            border-top: 2px solid #333;
            margin-top: 20px;
        }
        .checkout-total-amount {
            color: var(--color-accent);
            font-size: 1.8rem;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--color-accent);
        }
        .btn-confirm {
            width: 100%;
            padding: 15px;
            background: var(--color-accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-confirm:hover {
            background: var(--color-accent-hover);
            transform: translateY(-2px);
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: var(--color-gray);
            text-decoration: none;
        }
        .stock-warning {
            color: #f57c00;
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <h1 class="checkout-title">Finalizar Compra</h1>
        
        <?php if (!empty($errores)): ?>
            <div class="error-message">
                <?php foreach ($errores as $error): ?>
                    <p>⚠️ <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-items">
            <?php foreach ($cart as $item): ?>
                <?php
                // Verificar stock actual
                $sql = "SELECT stock FROM productos WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $producto = $result->fetch_assoc();
                $stock_actual = $producto['stock'];
                $stock_suficiente = $stock_actual >= $item['quantity'];
                ?>
                <div class="checkout-item">
                    <div class="checkout-item-info">
                        <div class="checkout-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="checkout-item-price">$<?php echo number_format($item['price'], 2); ?> c/u</div>
                        <?php if (!$stock_suficiente): ?>
                            <div class="stock-warning">⚠️ Solo hay <?php echo $stock_actual; ?> disponibles</div>
                        <?php endif; ?>
                    </div>
                    <div class="checkout-item-quantity">x<?php echo $item['quantity']; ?></div>
                    <div class="checkout-item-subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>

            <div class="checkout-total">
                <span>TOTAL</span>
                <span class="checkout-total-amount">$<?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="direccion">Dirección de entrega</label>
                <input type="text" id="direccion" name="direccion" required placeholder="Calle, número, colonia...">
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono de contacto</label>
                <input type="tel" id="telefono" name="telefono" required placeholder="809-555-5555">
            </div>
            <div class="form-group">
                <label for="notas">Notas adicionales (opcional)</label>
                <textarea id="notas" name="notas" rows="3" placeholder="Indicaciones para la entrega..."></textarea>
            </div>

            <button type="submit" name="confirmar_pedido" class="btn-confirm">Confirmar Pedido</button>
        </form>

        <a href="index.php#S3" class="btn-back">← Seguir comprando</a>
    </div>
</body>
</html>