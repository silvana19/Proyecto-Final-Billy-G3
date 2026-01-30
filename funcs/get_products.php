<?php
require_once "../config/db.php";

$limit = 3; // Cantidad de productos por carga
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Preparar consulta
$sql = "SELECT * FROM productos";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " WHERE nombre LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $imagen = !empty($row['imagen']) ? $row['imagen'] : 'assets/img/default.jpg';
        ?>
        <div class="card">
            <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($row['nombre']); ?>" />
            <div class="infoc">
                <h3>
                    <?php echo htmlspecialchars($row['nombre']); ?>
                </h3>
                <p>
                    <?php echo htmlspecialchars($row['descripcion']); ?>
                </p>
                <p class="text-primary" style="font-weight:bold;">$
                    <?php echo $row['precio']; ?>
                </p>
                <p class="text-muted">Stock:
                    <?php echo $row['stock']; ?>
                </p>

                <?php if ($row['stock'] > 0): ?>
                    <a href="funcs/comprar.php?id=<?php echo $row['id']; ?>" class="buy"
                        onclick="return confirm('¿Comprar <?php echo htmlspecialchars($row['nombre']); ?>?')">Comprar Ahora</a>
                <?php else: ?>
                    <button class="buy" style="background-color: #ccc; cursor: not-allowed;" disabled>Agotado</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} else {
    // Si es la primera carga (offset 0) y no hay resultados, mostramos mensaje
    if ($offset == 0) {
        echo "<p style='color:white; width:100%; text-align:center;'>No se encontraron productos.</p>";
    }
}
?>