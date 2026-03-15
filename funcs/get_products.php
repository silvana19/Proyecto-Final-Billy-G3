<?php
/**
 * get_products.php
 * Devuelve tarjetas de productos en HTML con botón para agregar al carrito
 * usando cart_functions.php (server-side, no localStorage).
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../config/db.php";

$limit  = 6;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql    = "SELECT * FROM productos";
$params = [];
$types  = "";

if (!empty($search)) {
    $sql     .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= "ss";
}

$sql     .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if ($offset === 0) {
        echo "<p class='no-results'>No se encontraron productos.</p>";
    }
    exit;
}

while ($row = $result->fetch_assoc()) {
    $id      = intval($row['id']);
    $nombre  = htmlspecialchars($row['nombre']);
    $desc    = htmlspecialchars($row['descripcion']);
    $precio  = number_format(floatval($row['precio']), 2);
    $stock   = intval($row['stock']);
    $imagen  = !empty($row['imagen']) ? htmlspecialchars($row['imagen']) : 'assets/img/default.jpg';

    // Para pasar datos al JS de forma segura
    $nombre_js = addslashes($row['nombre']);
    $imagen_js = addslashes($imagen);
    $precio_raw = floatval($row['precio']);
    ?>
    <div class="card" data-product-id="<?= $id ?>">
        <img src="<?= $imagen ?>" alt="<?= $nombre ?>" onerror="this.src='assets/img/default.jpg'" />
        <div class="infoc">
            <h3><?= $nombre ?></h3>
            <p><?= $desc ?></p>
            <p class="text-primary">RD$<?= $precio ?></p>
            <p class="text-muted">Stock: <?= $stock ?></p>

            <?php if ($stock > 0): ?>
                <button
                    class="buy"
                    onclick="addToCartItem({
                        id:    <?= $id ?>,
                        name:  '<?= $nombre_js ?>',
                        price: <?= $precio_raw ?>,
                        image: '<?= $imagen_js ?>',
                        stock: <?= $stock ?>
                    })"
                >
                    🛒 Agregar al carrito
                </button>
            <?php else: ?>
                <button class="buy" disabled style="background:#555;cursor:not-allowed;">
                    Agotado
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>