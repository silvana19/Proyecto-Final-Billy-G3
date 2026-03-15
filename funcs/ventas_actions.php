<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../config/db.php";
header('Content-Type: application/json');

// Solo admins
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$action = $input['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Cerrar venta (pendiente → cerrado) ────
    case 'cerrar_venta':
        $id = intval($input['venta_id']);
        $stmt = $conn->prepare("UPDATE ventas SET estado = 'cerrado' WHERE id = ? AND estado = 'pendiente'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => "Venta #$id cerrada correctamente"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo cerrar la venta (ya está cerrada o no existe)']);
        }
        break;

    // ── Eliminar venta + restaurar stock ─────
    case 'eliminar_venta':
        $id = intval($input['venta_id']);

        // Obtener items de la venta para restaurar stock
        $items = $conn->query(
            "SELECT producto_id, cantidad FROM detalle_ventas WHERE venta_id = $id"
        );

        if (!$items) {
            echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
            exit;
        }

        // Restaurar stock de cada producto
        while ($item = $items->fetch_assoc()) {
            $pid = intval($item['producto_id']);
            $qty = intval($item['cantidad']);
            $conn->query("UPDATE productos SET stock = stock + $qty WHERE id = $pid");
        }

        // Eliminar detalle y venta
        $conn->query("DELETE FROM detalle_ventas WHERE venta_id = $id");
        $stmt = $conn->prepare("DELETE FROM ventas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => "Venta #$id eliminada y stock restaurado"
        ]);
        break;

    // ── Resetear tabla ventas ─────────────────
    case 'resetear_ventas':
        // Restaurar todo el stock antes de borrar
        $items = $conn->query(
            "SELECT producto_id, SUM(cantidad) as total_qty 
             FROM detalle_ventas 
             GROUP BY producto_id"
        );
        if ($items) {
            while ($item = $items->fetch_assoc()) {
                $pid = intval($item['producto_id']);
                $qty = intval($item['total_qty']);
                $conn->query("UPDATE productos SET stock = stock + $qty WHERE id = $pid");
            }
        }

        $conn->query("DELETE FROM detalle_ventas");
        $conn->query("DELETE FROM ventas");
        $conn->query("ALTER TABLE ventas AUTO_INCREMENT = 1");
        $conn->query("ALTER TABLE detalle_ventas AUTO_INCREMENT = 1");

        echo json_encode([
            'success' => true,
            'message' => 'Tabla ventas reseteada y stock restaurado'
        ]);
        break;

    // ── Verificar PIN de admin ────────────────
    case 'verificar_pin':
        $pin = $input['pin'] ?? '';
        // PIN guardado en sesión o constante — puedes cambiarlo
        $PIN_ADMIN = '1234'; // ← Cambia este PIN
        if ($pin === $PIN_ADMIN) {
            $_SESSION['pin_verified'] = true;
            $_SESSION['pin_time']     = time();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'PIN incorrecto']);
        }
        break;

    // ── Modificar venta (requiere PIN) ────────
    case 'modificar_venta':
        // Verificar que el PIN fue validado hace menos de 5 minutos
        if (!isset($_SESSION['pin_verified']) || !$_SESSION['pin_verified'] ||
            (time() - ($_SESSION['pin_time'] ?? 0)) > 300) {
            echo json_encode(['success' => false, 'error' => 'PIN requerido', 'need_pin' => true]);
            exit;
        }

        $id     = intval($input['venta_id']);
        $estado = $conn->real_escape_string($input['estado'] ?? 'pendiente');

        $stmt = $conn->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $id);

        if ($stmt->execute()) {
            $_SESSION['pin_verified'] = false; // PIN de un solo uso
            echo json_encode(['success' => true, 'message' => "Venta #$id actualizada a '$estado'"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar: ' . $stmt->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>