<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo "<script>alert('Acceso denegado.'); window.location='login.php';</script>";
    exit;
}

require_once "config/db.php";

// ── Acciones POST (cambiar estado, eliminar) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cambiar_estado') {
        $id     = intval($_POST['pedido_id']);
        $estado = $conn->real_escape_string($_POST['estado']);
        $conn->query("UPDATE pedidos SET estado = '$estado' WHERE id = $id");
    }

    if ($action === 'eliminar_pedido') {
        $id = intval($_POST['pedido_id']);
        $conn->query("DELETE FROM pedido_items WHERE pedido_id = $id");
        $conn->query("DELETE FROM pedidos WHERE id = $id");
    }

    header('Location: admin_dashboard.php');
    exit;
}

// ── Estadísticas ──────────────────────────────
$total_ventas   = $conn->query("SELECT COUNT(*) as c FROM ventas")->fetch_assoc()['c'] ?? 0;
$total_pedidos  = $conn->query("SELECT COUNT(*) as c FROM pedidos")->fetch_assoc()['c'] ?? 0;
$total_usuarios = $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'] ?? 0;
$total_productos = $conn->query("SELECT COUNT(*) as c FROM productos")->fetch_assoc()['c'] ?? 0;

$ingresos_ventas  = $conn->query("SELECT SUM(total) as s FROM ventas")->fetch_assoc()['s'] ?? 0;
$ingresos_pedidos = $conn->query("SELECT SUM(total) as s FROM pedidos WHERE estado != 'cancelado'")->fetch_assoc()['s'] ?? 0;
$ingresos_total   = $ingresos_ventas + $ingresos_pedidos;

$pedidos_pendientes   = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='pendiente'")->fetch_assoc()['c'] ?? 0;
$pedidos_confirmados  = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='confirmado'")->fetch_assoc()['c'] ?? 0;
$pedidos_entregados   = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='entregado'")->fetch_assoc()['c'] ?? 0;
$pedidos_cancelados   = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='cancelado'")->fetch_assoc()['c'] ?? 0;

// ── Últimas ventas ────────────────────────────
$ventas = $conn->query(
    "SELECT v.id, u.nombre as usuario, v.fecha, v.total
     FROM ventas v
     JOIN usuarios u ON v.usuario_id = u.id
     ORDER BY v.fecha DESC LIMIT 10"
);

// ── Pedidos ───────────────────────────────────
$filtro_estado = $_GET['estado'] ?? 'todos';
$where = $filtro_estado !== 'todos' ? "WHERE p.estado = '" . $conn->real_escape_string($filtro_estado) . "'" : '';

$pedidos = $conn->query(
    "SELECT p.*, u.nombre as usuario_nombre
     FROM pedidos p
     LEFT JOIN usuarios u ON p.usuario_id = u.id
     $where
     ORDER BY p.fecha_pedido DESC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración — Farmacia del Amor</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d2919;
            --secondary: #1e3932;
            --accent: #00a86b;
            --accent-hover: #00754a;
            --light: #f4f6f9;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light);
            margin: 0;
            padding: 0;
        }

        /* ── Navbar ── */
        .navbar-admin {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: .75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-brand-admin {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .navbar-right { display: flex; align-items: center; gap: 1rem; }
        .navbar-user  { color: rgba(255,255,255,.85); font-size: .9rem; }
        .btn-logout-nav {
            background: #e53e3e; color: #fff;
            border: none; padding: .4rem 1rem;
            border-radius: 20px; font-size: .85rem;
            font-weight: 600; cursor: pointer;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-logout-nav:hover { background: #c53030; color: #fff; }
        .btn-site {
            background: rgba(255,255,255,.15); color: #fff;
            border: 1px solid rgba(255,255,255,.2);
            padding: .4rem 1rem; border-radius: 20px;
            font-size: .85rem; font-weight: 500;
            text-decoration: none; transition: background .2s;
        }
        .btn-site:hover { background: var(--accent); color: #fff; }

        /* ── Layout ── */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.8rem;
        }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            display: flex;
            align-items: center;
            gap: 1.1rem;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .stat-icon.green  { background: #e8f5ee; }
        .stat-icon.blue   { background: #e8f0fe; }
        .stat-icon.orange { background: #fff3e0; }
        .stat-icon.purple { background: #f3e8ff; }
        .stat-icon.red    { background: #fff0f0; }
        .stat-info { min-width: 0; }
        .stat-value { font-size: 1.6rem; font-weight: 700; color: var(--primary); line-height: 1.2; }
        .stat-label { font-size: .78rem; color: #888; font-weight: 500; }

        /* ── Tabs ── */
        .tabs {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: .55rem 1.3rem;
            border-radius: 30px;
            border: 2px solid #e0e0e0;
            background: #fff;
            color: #666;
            font-size: .88rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
        }
        .tab-btn:hover, .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        /* ── Section card ── */
        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
        }
        .section-head h2 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        /* ── Table ── */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .87rem;
        }
        .admin-table th {
            background: #f8f9fa;
            padding: .8rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #f0f0f0;
            white-space: nowrap;
        }
        .admin-table td {
            padding: .75rem 1rem;
            border-bottom: 1px solid #f5f5f5;
            vertical-align: middle;
        }
        .admin-table tr:hover td { background: #fafffe; }
        .admin-table tr:last-child td { border: none; }

        /* ── Badges de estado ── */
        .badge-estado {
            padding: .28rem .75rem;
            border-radius: 20px;
            font-size: .76rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-pendiente   { background: #fff3cd; color: #856404; }
        .badge-confirmado  { background: #d1ecf1; color: #0c5460; }
        .badge-en_camino   { background: #d4edda; color: #155724; }
        .badge-entregado   { background: #e8f5ee; color: #00754a; }
        .badge-cancelado   { background: #f8d7da; color: #721c24; }

        /* ── Botones de acción ── */
        .btn-action {
            padding: .3rem .75rem;
            border-radius: 8px;
            font-size: .78rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .2s;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }
        .btn-view   { background: #e8f5ee; color: var(--accent-hover); }
        .btn-view:hover   { background: var(--accent); color: #fff; }
        .btn-delete { background: #fff0f0; color: #e53e3e; }
        .btn-delete:hover { background: #e53e3e; color: #fff; }

        /* ── Select estado ── */
        .select-estado {
            padding: .28rem .6rem;
            border-radius: 8px;
            border: 1.5px solid #e0e0e0;
            font-size: .8rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            background: #fff;
            transition: border-color .2s;
        }
        .select-estado:focus { outline: none; border-color: var(--accent); }

        /* ── Modal detalles ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 18px;
            width: min(560px, 95vw);
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0,0,0,.2);
            animation: popIn .3s cubic-bezier(.175,.885,.32,1.275);
        }
        @keyframes popIn {
            from { transform: scale(.95); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }
        .modal-box::-webkit-scrollbar { width: 4px; }
        .modal-box::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
        .modal-head {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 1.3rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 18px 18px 0 0;
        }
        .modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 600; }
        .modal-close {
            background: rgba(255,255,255,.15); border: none;
            color: #fff; width: 30px; height: 30px;
            border-radius: 50%; font-size: 1.2rem;
            cursor: pointer; display: flex;
            align-items: center; justify-content: center;
            transition: background .2s;
        }
        .modal-close:hover { background: rgba(255,255,255,.3); }
        .modal-body { padding: 1.5rem; }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: .5rem 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: .88rem;
        }
        .detail-row:last-child { border: none; }
        .detail-label { color: #888; font-weight: 500; }
        .detail-value { color: #333; font-weight: 600; text-align: right; max-width: 60%; }

        .product-item-row {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: .7rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .product-item-row:last-child { border: none; }
        .product-item-row img {
            width: 48px; height: 48px;
            object-fit: cover; border-radius: 8px;
            background: #eee; flex-shrink: 0;
        }
        .product-item-row .pi-name { font-size: .88rem; font-weight: 600; color: #333; }
        .product-item-row .pi-qty  { font-size: .8rem; color: #888; }
        .product-item-row .pi-price { font-size: .9rem; font-weight: 700; color: var(--accent-hover); margin-left: auto; white-space: nowrap; }

        .section-sub {
            font-size: .82rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 1rem 0 .6rem;
        }

        .total-final {
            display: flex;
            justify-content: space-between;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: .8rem;
            padding-top: .8rem;
            border-top: 2px solid #f0f0f0;
        }

        /* ── Filtros de pedidos ── */
        .filter-bar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .filter-badge {
            padding: .3rem .9rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
            border: 1.5px solid transparent;
        }
        .filter-badge.all      { background: #f0f0f0; color: #555; }
        .filter-badge.pending  { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .filter-badge.confirmed{ background: #d1ecf1; color: #0c5460; border-color: #17a2b8; }
        .filter-badge.delivered{ background: #e8f5ee; color: #00754a; border-color: #00a86b; }
        .filter-badge.cancelled{ background: #f8d7da; color: #721c24; border-color: #dc3545; }
        .filter-badge.active, .filter-badge:hover { opacity: .8; transform: translateY(-1px); }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #aaa;
        }
        .empty-state p { font-size: 1rem; margin-top: .5rem; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .admin-container { padding: 1rem; }
            .admin-table { font-size: .78rem; }
            .admin-table th, .admin-table td { padding: .5rem .6rem; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-admin">
    <a href="index.php" class="navbar-brand-admin">
        🏥 Farmacia Admin
    </a>
    <div class="navbar-right">
        <span class="navbar-user">👤 <?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <a href="index.php" class="btn-site">← Ver sitio</a>
        <a href="funcs/logout.php" class="btn-logout-nav">Cerrar Sesión</a>
    </div>
</nav>

<div class="admin-container">
    <h1 class="page-title">Panel de Control</h1>

    <!-- ═══ ESTADÍSTICAS ═══ -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">💊</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_productos ?></div>
                <div class="stat-label">Productos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_usuarios ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">🛒</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_ventas ?></div>
                <div class="stat-label">Ventas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">📦</div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_pedidos ?></div>
                <div class="stat-label">Pedidos</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <div class="stat-value">RD$<?= number_format($ingresos_total, 0) ?></div>
                <div class="stat-label">Ingresos totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">⏳</div>
            <div class="stat-info">
                <div class="stat-value"><?= $pedidos_pendientes ?></div>
                <div class="stat-label">Pedidos pendientes</div>
            </div>
        </div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('tab-pedidos', this)">📦 Pedidos</button>
        <button class="tab-btn" onclick="showTab('tab-ventas', this)">🛒 Ventas</button>
        <button class="tab-btn" onclick="showTab('tab-acciones', this)">⚙️ Acciones</button>
    </div>

    <!-- ═══ TAB: PEDIDOS ═══ -->
    <div id="tab-pedidos" class="tab-content">

        <!-- Filtros de estado -->
        <div class="filter-bar">
            <a href="?estado=todos"      class="filter-badge all       <?= $filtro_estado === 'todos'      ? 'active' : '' ?>">Todos (<?= $total_pedidos ?>)</a>
            <a href="?estado=pendiente"  class="filter-badge pending   <?= $filtro_estado === 'pendiente'  ? 'active' : '' ?>">⏳ Pendiente (<?= $pedidos_pendientes ?>)</a>
            <a href="?estado=confirmado" class="filter-badge confirmed <?= $filtro_estado === 'confirmado' ? 'active' : '' ?>">✅ Confirmado (<?= $pedidos_confirmados ?>)</a>
            <a href="?estado=entregado"  class="filter-badge delivered <?= $filtro_estado === 'entregado'  ? 'active' : '' ?>">📬 Entregado (<?= $pedidos_entregados ?>)</a>
            <a href="?estado=cancelado"  class="filter-badge cancelled <?= $filtro_estado === 'cancelado'  ? 'active' : '' ?>">❌ Cancelado (<?= $pedidos_cancelados ?>)</a>
        </div>

        <div class="section-card">
            <div class="section-head">
                <h2>📦 Gestión de Pedidos</h2>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Teléfono</th>
                            <th>Método envío</th>
                            <th>Método pago</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($pedidos && $pedidos->num_rows > 0):
                        while ($p = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;color:#333;"><?= htmlspecialchars($p['nombre'] ?? 'N/A') ?></div>
                                <div style="font-size:.75rem;color:#888;"><?= htmlspecialchars($p['email'] ?? '') ?></div>
                            </td>
                            <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['metodo_envio'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['metodo_pago'] ?? '-') ?></td>
                            <td><strong style="color:var(--accent-hover);">RD$<?= number_format($p['total'], 2) ?></strong></td>
                            <td style="white-space:nowrap;font-size:.82rem;"><?= $p['fecha_pedido'] ?></td>
                            <td>
                                <span class="badge-estado badge-<?= $p['estado'] ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center;">
                                    <!-- Ver detalles -->
                                    <button class="btn-action btn-view"
                                        onclick="verDetalles(<?= $p['id'] ?>, '<?= addslashes($p['nombre'] ?? '') ?>', '<?= addslashes($p['email'] ?? '') ?>', '<?= addslashes($p['telefono'] ?? '') ?>', '<?= addslashes($p['direccion'] ?? '') ?>', '<?= addslashes($p['ciudad'] ?? '') ?>', '<?= addslashes($p['metodo_envio'] ?? '') ?>', '<?= addslashes($p['metodo_pago'] ?? '') ?>', <?= $p['total'] ?>, '<?= $p['estado'] ?>', '<?= $p['fecha_pedido'] ?>')">
                                        👁 Ver
                                    </button>

                                    <!-- Cambiar estado -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="cambiar_estado">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <select name="estado" class="select-estado" onchange="this.form.submit()">
                                            <option value="pendiente"   <?= $p['estado']==='pendiente'   ? 'selected' : '' ?>>⏳ Pendiente</option>
                                            <option value="confirmado"  <?= $p['estado']==='confirmado'  ? 'selected' : '' ?>>✅ Confirmado</option>
                                            <option value="en_camino"   <?= $p['estado']==='en_camino'   ? 'selected' : '' ?>>🚚 En camino</option>
                                            <option value="entregado"   <?= $p['estado']==='entregado'   ? 'selected' : '' ?>>📬 Entregado</option>
                                            <option value="cancelado"   <?= $p['estado']==='cancelado'   ? 'selected' : '' ?>>❌ Cancelado</option>
                                        </select>
                                    </form>

                                    <!-- Eliminar -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar pedido #<?= $p['id'] ?>?')">
                                        <input type="hidden" name="action" value="eliminar_pedido">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div style="font-size:2.5rem;">📭</div>
                                    <p>No hay pedidos<?= $filtro_estado !== 'todos' ? " con estado \"$filtro_estado\"" : '' ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: VENTAS ═══ -->
    <div id="tab-ventas" class="tab-content" style="display:none;">
        <div class="section-card">
            <div class="section-head">
                <h2>🛒 Últimas Ventas</h2>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $ventas->data_seek(0);
                    if ($ventas && $ventas->num_rows > 0):
                        while ($v = $ventas->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $v['id'] ?></strong></td>
                            <td><?= htmlspecialchars($v['usuario']) ?></td>
                            <td style="font-size:.82rem;"><?= $v['fecha'] ?></td>
                            <td><strong style="color:var(--accent-hover);">RD$<?= number_format($v['total'], 2) ?></strong></td>
                            <td>
                                <button class="btn-action btn-view" onclick="verProductosVenta(<?= $v['id'] ?>)">
                                    👁 Ver productos
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5"><div class="empty-state"><div style="font-size:2.5rem;">🛒</div><p>No hay ventas registradas</p></div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: ACCIONES ═══ -->
    <div id="tab-acciones" class="tab-content" style="display:none;">
        <div class="stats-grid">
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.5rem;">
                <div style="font-size:1.1rem;font-weight:700;color:var(--primary);">⚙️ Acciones rápidas</div>
                <a href="admin_products.php" class="btn-action btn-view" style="padding:.6rem 1.2rem;font-size:.9rem;">
                    💊 Gestionar Productos
                </a>
            </div>
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.5rem;">
                <div style="font-size:.9rem;font-weight:600;color:#555;">📊 Resumen de pedidos</div>
                <div style="font-size:.85rem;color:#666;line-height:1.8;">
                    ⏳ Pendientes: <strong><?= $pedidos_pendientes ?></strong><br>
                    ✅ Confirmados: <strong><?= $pedidos_confirmados ?></strong><br>
                    📬 Entregados: <strong><?= $pedidos_entregados ?></strong><br>
                    ❌ Cancelados: <strong><?= $pedidos_cancelados ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL: DETALLES DEL PEDIDO ═══ -->
<div id="modal-pedido" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modal-title">Detalles del Pedido</h3>
            <button class="modal-close" onclick="cerrarModal()">×</button>
        </div>
        <div class="modal-body" id="modal-body-pedido">
            Cargando...
        </div>
    </div>
</div>

<!-- ═══ MODAL: PRODUCTOS DE VENTA ═══ -->
<div id="modal-venta" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modal-venta-title">Productos de la Venta</h3>
            <button class="modal-close" onclick="cerrarModalVenta()">×</button>
        </div>
        <div class="modal-body" id="modal-body-venta">
            Cargando...
        </div>
    </div>
</div>

<script>
// ── Tabs ──────────────────────────────────────
function showTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    btn.classList.add('active');
}

// ── Modal pedido ──────────────────────────────
function verDetalles(id, nombre, email, telefono, direccion, ciudad, metodoEnvio, metodoPago, total, estado, fecha) {
    document.getElementById('modal-title').textContent = `Pedido #${id}`;

    const badgeColors = {
        pendiente:  '#fff3cd',  confirmado: '#d1ecf1',
        en_camino:  '#d4edda',  entregado:  '#e8f5ee',  cancelado: '#f8d7da'
    };
    const textColors = {
        pendiente:  '#856404',  confirmado: '#0c5460',
        en_camino:  '#155724',  entregado:  '#00754a',  cancelado: '#721c24'
    };

    const bg   = badgeColors[estado] || '#f0f0f0';
    const tc   = textColors[estado]  || '#333';

    document.getElementById('modal-body-pedido').innerHTML = `
        <p class="section-sub">Información del cliente</p>
        <div class="detail-row"><span class="detail-label">Nombre</span><span class="detail-value">${nombre || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${email || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Teléfono</span><span class="detail-value">${telefono || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Dirección</span><span class="detail-value">${direccion || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Ciudad</span><span class="detail-value">${ciudad || '-'}</span></div>

        <p class="section-sub" style="margin-top:1.2rem;">Información del pedido</p>
        <div class="detail-row"><span class="detail-label">Método de envío</span><span class="detail-value">${metodoEnvio || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Método de pago</span><span class="detail-value">${metodoPago || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Fecha</span><span class="detail-value">${fecha}</span></div>
        <div class="detail-row">
            <span class="detail-label">Estado</span>
            <span class="detail-value">
                <span style="background:${bg};color:${tc};padding:.2rem .7rem;border-radius:20px;font-size:.8rem;font-weight:600;">
                    ${estado.charAt(0).toUpperCase() + estado.slice(1)}
                </span>
            </span>
        </div>

        <p class="section-sub" style="margin-top:1.2rem;">Productos</p>
        <div id="modal-items-container">Cargando productos...</div>

        <div class="total-final">
            <span>Total pagado</span>
            <span style="color:var(--accent-hover);">RD$${parseFloat(total).toFixed(2)}</span>
        </div>
    `;

    // Cargar items del pedido via AJAX
    fetch(`funcs/get_order_items.php?pedido_id=${id}`)
        .then(r => r.json())
        .then(items => {
            const container = document.getElementById('modal-items-container');
            if (!items.length) {
                container.innerHTML = '<p style="color:#aaa;font-size:.85rem;">Sin productos registrados</p>';
                return;
            }
            container.innerHTML = items.map(item => `
                <div class="product-item-row">
                    <img src="${item.imagen || 'assets/img/default.jpg'}" onerror="this.src='assets/img/default.jpg'" alt="${item.nombre}">
                    <div>
                        <div class="pi-name">${item.nombre}</div>
                        <div class="pi-qty">×${item.cantidad} · RD$${parseFloat(item.precio).toFixed(2)} c/u</div>
                    </div>
                    <div class="pi-price">RD$${(parseFloat(item.precio) * parseInt(item.cantidad)).toFixed(2)}</div>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('modal-items-container').innerHTML =
                '<p style="color:#aaa;font-size:.85rem;">No se pudieron cargar los productos</p>';
        });

    document.getElementById('modal-pedido').classList.add('show');
}

function cerrarModal() {
    document.getElementById('modal-pedido').classList.remove('show');
}

// ── Modal venta ───────────────────────────────
function verProductosVenta(ventaId) {
    document.getElementById('modal-venta-title').textContent = `Venta #${ventaId}`;
    document.getElementById('modal-body-venta').innerHTML = 'Cargando...';
    document.getElementById('modal-venta').classList.add('show');

    fetch(`funcs/get_sale_items.php?venta_id=${ventaId}`)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                document.getElementById('modal-body-venta').innerHTML =
                    '<p style="color:#aaa;text-align:center;">Sin productos registrados</p>';
                return;
            }
            let total = 0;
            const html = items.map(item => {
                const sub = parseFloat(item.precio) * parseInt(item.cantidad);
                total += sub;
                return `
                <div class="product-item-row">
                    <img src="${item.imagen || 'assets/img/default.jpg'}" onerror="this.src='assets/img/default.jpg'" alt="${item.nombre}">
                    <div>
                        <div class="pi-name">${item.nombre}</div>
                        <div class="pi-qty">×${item.cantidad} · RD$${parseFloat(item.precio).toFixed(2)} c/u</div>
                    </div>
                    <div class="pi-price">RD$${sub.toFixed(2)}</div>
                </div>`;
            }).join('');

            document.getElementById('modal-body-venta').innerHTML = html +
                `<div class="total-final"><span>Total</span><span style="color:var(--accent-hover);">RD$${total.toFixed(2)}</span></div>`;
        })
        .catch(() => {
            document.getElementById('modal-body-venta').innerHTML =
                '<p style="color:#aaa;text-align:center;">Error cargando productos</p>';
        });
}

function cerrarModalVenta() {
    document.getElementById('modal-venta').classList.remove('show');
}

// Cerrar modales al hacer clic fuera
document.getElementById('modal-pedido').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
document.getElementById('modal-venta').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalVenta();
});
</script>
</body>
</html>