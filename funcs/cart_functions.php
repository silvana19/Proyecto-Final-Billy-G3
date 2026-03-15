<?php
/**
 * cart_functions.php
 * Maneja el carrito de compras en sesión PHP (server-side).
 * Reemplaza el uso de localStorage del cliente.
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// Inicializar carrito en sesión
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Agregar producto ──────────────────────
    case 'add':
        require_once "../config/db.php";

        $id    = intval($_POST['id']);
        $name  = htmlspecialchars(strip_tags($_POST['name'] ?? ''));
        $price = floatval($_POST['price'] ?? 0);
        $image = htmlspecialchars(strip_tags($_POST['image'] ?? ''));

        // Verificar stock real en BD
        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();

        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }

        $stock = intval($producto['stock']);
        $currentQty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['quantity'] : 0;
        $newQty     = $currentQty + 1;

        if ($newQty > $stock) {
            echo json_encode([
                'success' => false,
                'message' => "Stock insuficiente. Solo hay $stock unidad(es) disponible(s)."
            ]);
            exit;
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] = $newQty;
        } else {
            $_SESSION['cart'][$id] = [
                'id'       => $id,
                'name'     => $name,
                'price'    => $price,
                'image'    => $image,
                'quantity' => 1,
                'stock'    => $stock,
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Producto añadido al carrito',
            'cart'    => array_values($_SESSION['cart']),
            'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    // ── Actualizar cantidad ───────────────────
    case 'update':
        require_once "../config/db.php";

        $id  = intval($_POST['id']);
        $qty = intval($_POST['quantity']);

        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
            echo json_encode([
                'success' => true,
                'cart'    => array_values($_SESSION['cart']),
                'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
            ]);
            exit;
        }

        // Verificar stock real
        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result  = $stmt->get_result();
        $producto = $result->fetch_assoc();

        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit;
        }

        $stock = intval($producto['stock']);

        if ($qty > $stock) {
            echo json_encode([
                'success' => false,
                'message' => "Solo hay $stock unidad(es) disponible(s)."
            ]);
            exit;
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] = $qty;
            $_SESSION['cart'][$id]['stock']    = $stock;
        }

        echo json_encode([
            'success' => true,
            'cart'    => array_values($_SESSION['cart']),
            'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    // ── Eliminar producto ─────────────────────
    case 'remove':
        $id = intval($_POST['id']);
        unset($_SESSION['cart'][$id]);

        echo json_encode([
            'success' => true,
            'cart'    => array_values($_SESSION['cart']),
            'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    // ── Vaciar carrito ────────────────────────
    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'cart' => [], 'count' => 0]);
        break;

    // ── Obtener carrito ───────────────────────
    case 'get':
        echo json_encode([
            'success' => true,
            'cart'    => array_values($_SESSION['cart']),
            'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>