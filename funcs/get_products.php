<?php
require_once "../config/db.php";

$limit = 3;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para contar total
$countSql = "SELECT COUNT(*) as total FROM productos";
$countParams = [];
$countTypes = "";

if (!empty($search)) {
    $countSql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
    $searchTerm = "%$search%";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes .= "ss";
}

$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRow = $countResult->fetch_assoc();
$totalProducts = $totalRow['total'];

// Consulta para obtener productos
$sql = "SELECT * FROM productos";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " WHERE nombre LIKE ? OR descripcion LIKE ?";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $imagen = !empty($row['imagen']) ? $row['imagen'] : 'assets/img/placeholder.jpg';
        ?>
        <div class="card" data-id="<?php echo $row['id']; ?>">
            <img src="<?php echo $imagen; ?>" 
                 alt="<?php echo htmlspecialchars($row['nombre']); ?>" 
                 onerror="this.src='assets/img/placeholder.jpg'; this.onerror=null;">
            <div class="infoc">
                <h3><?php echo htmlspecialchars($row['nombre']); ?></h3>
                <p><?php echo htmlspecialchars($row['descripcion']); ?></p>
                
                <!-- Selector de cantidad simple -->
                <div class="cantidad-selector">
                    <label>Cantidad:</label>
                    <div class="cantidad-control">
                        <button type="button" onclick="cambiarCantidad(<?php echo $row['id']; ?>, -1)" class="cantidad-btn">-</button>
                        <input type="number" id="cantidad_<?php echo $row['id']; ?>" 
                               class="cantidad-input" value="1" min="1" 
                               max="<?php echo $row['stock']; ?>" readonly>
                        <button type="button" onclick="cambiarCantidad(<?php echo $row['id']; ?>, 1)" class="cantidad-btn">+</button>
                    </div>
                </div>

                <!-- Precio -->
                <p class="text-primary">$<?php echo number_format($row['precio'], 2); ?> c/u</p>
                <p class="text-muted">Stock: <?php echo $row['stock']; ?> unidades</p>

                <?php if ($row['stock'] > 0): ?>
                    <button class="buy add-to-cart-btn" 
                            onclick="agregarAlCarrito(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nombre']); ?>', <?php echo $row['precio']; ?>, '<?php echo $row['imagen']; ?>')">
                        <i class="fas fa-cart-plus"></i> Agregar al Carrito
                    </button>
                <?php else: ?>
                    <button class="buy" style="background-color: #ccc; cursor: not-allowed;" disabled>Agotado</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    if ($offset + $limit < $totalProducts) {
        echo '<!-- HAS_MORE -->';
    }
} else {
    if ($offset == 0) {
        echo "<p class='no-results'>No se encontraron productos.</p>";
    }
}

$stmt->close();
$countStmt->close();
$conn->close();
?>