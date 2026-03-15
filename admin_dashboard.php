<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    echo "<script>alert('Acceso denegado.'); window.location='login.php';</script>";
    exit;
}

require_once "config/db.php";

// ── Acciones de pedidos (POST form) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'cambiar_estado_pedido') {
        $id     = intval($_POST['pedido_id']);
        $estado = $conn->real_escape_string($_POST['estado']);
        $conn->query("UPDATE pedidos SET estado = '$estado' WHERE id = $id");
    }
    if ($action === 'eliminar_pedido') {
        $id = intval($_POST['pedido_id']);
        $conn->query("DELETE FROM pedido_items WHERE pedido_id = $id");
        $conn->query("DELETE FROM pedidos WHERE id = $id");
    }
    header('Location: admin_dashboard.php' . (isset($_GET['tab']) ? '?tab='.$_GET['tab'] : ''));
    exit;
}

// ── Estadísticas ──────────────────────────────
$total_productos  = $conn->query("SELECT COUNT(*) as c FROM productos")->fetch_assoc()['c'] ?? 0;
$total_usuarios   = $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'] ?? 0;
$total_ventas     = $conn->query("SELECT COUNT(*) as c FROM ventas")->fetch_assoc()['c'] ?? 0;
$total_pedidos    = $conn->query("SELECT COUNT(*) as c FROM pedidos")->fetch_assoc()['c'] ?? 0;
$ingresos_ventas  = $conn->query("SELECT SUM(total) as s FROM ventas")->fetch_assoc()['s'] ?? 0;
$ingresos_pedidos = $conn->query("SELECT SUM(total) as s FROM pedidos WHERE estado != 'cancelado'")->fetch_assoc()['s'] ?? 0;
$ingresos_total   = $ingresos_ventas + $ingresos_pedidos;

// Stock bajo (menos de 5 unidades)
$stock_bajo = $conn->query(
    "SELECT id, nombre, stock, imagen FROM productos WHERE stock <= 5 ORDER BY stock ASC"
);
$stock_bajo_count = $stock_bajo ? $stock_bajo->num_rows : 0;

$pedidos_pendientes  = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='pendiente'")->fetch_assoc()['c'] ?? 0;
$pedidos_confirmados = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='confirmado'")->fetch_assoc()['c'] ?? 0;
$pedidos_entregados  = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='entregado'")->fetch_assoc()['c'] ?? 0;
$pedidos_cancelados  = $conn->query("SELECT COUNT(*) as c FROM pedidos WHERE estado='cancelado'")->fetch_assoc()['c'] ?? 0;

$ventas_pendientes = $conn->query("SELECT COUNT(*) as c FROM ventas WHERE estado='pendiente'")->fetch_assoc()['c'] ?? 0;
$ventas_cerradas   = $conn->query("SELECT COUNT(*) as c FROM ventas WHERE estado='cerrado'")->fetch_assoc()['c'] ?? 0;

// ── Ventas ────────────────────────────────────
$filtro_venta = $_GET['venta_estado'] ?? 'todos';
$where_v = $filtro_venta !== 'todos' ? "WHERE v.estado = '" . $conn->real_escape_string($filtro_venta) . "'" : '';
$ventas = $conn->query(
    "SELECT v.*, u.nombre as usuario_nombre
     FROM ventas v
     JOIN usuarios u ON v.usuario_id = u.id
     $where_v
     ORDER BY v.fecha DESC"
);

// ── Pedidos ───────────────────────────────────
$filtro_pedido = $_GET['pedido_estado'] ?? 'todos';
$where_p = $filtro_pedido !== 'todos' ? "WHERE p.estado = '" . $conn->real_escape_string($filtro_pedido) . "'" : '';
$pedidos = $conn->query(
    "SELECT p.*, u.nombre as usuario_nombre
     FROM pedidos p
     LEFT JOIN usuarios u ON p.usuario_id = u.id
     $where_p
     ORDER BY p.fecha_pedido DESC"
);

$active_tab = $_GET['tab'] ?? 'ventas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin — Farmacia del Amor</title>
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
        body { font-family: 'Poppins', sans-serif; background: var(--light); margin: 0; }

        /* Navbar */
        .navbar-admin { background: linear-gradient(135deg,var(--primary),var(--secondary)); padding: .75rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 20px rgba(0,0,0,.2); position: sticky; top: 0; z-index: 100; }
        .navbar-brand-admin { color: #fff; font-size: 1.3rem; font-weight: 700; text-decoration: none; }
        .navbar-right { display: flex; align-items: center; gap: .8rem; }
        .navbar-user  { color: rgba(255,255,255,.85); font-size: .9rem; }
        .btn-nav { padding: .38rem 1rem; border-radius: 20px; font-size: .83rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all .2s; }
        .btn-nav-site   { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2); }
        .btn-nav-site:hover  { background: var(--accent); color: #fff; }
        .btn-nav-logout { background: #e53e3e; color: #fff; }
        .btn-nav-logout:hover { background: #c53030; color: #fff; }

        /* Layout */
        .admin-wrap  { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .page-title  { font-size: 1.6rem; font-weight: 700; color: var(--primary); margin-bottom: 1.8rem; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(190px,1fr)); gap: 1.1rem; margin-bottom: 2rem; }
        .stat-card  { background: #fff; border-radius: 14px; padding: 1.3rem 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,.07); display: flex; align-items: center; gap: 1rem; transition: transform .2s,box-shadow .2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        .stat-icon  { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .ic-green  { background: #e8f5ee; }
        .ic-blue   { background: #e8f0fe; }
        .ic-orange { background: #fff3e0; }
        .ic-purple { background: #f3e8ff; }
        .ic-red    { background: #fff0f0; }
        .stat-value { font-size: 1.55rem; font-weight: 700; color: var(--primary); line-height: 1.2; }
        .stat-label { font-size: .76rem; color: #888; font-weight: 500; }

        /* Tabs */
        .tabs { display: flex; gap: .45rem; margin-bottom: 1.4rem; flex-wrap: wrap; }
        .tab-btn { padding: .52rem 1.25rem; border-radius: 30px; border: 2px solid #e0e0e0; background: #fff; color: #666; font-size: .86rem; font-weight: 500; cursor: pointer; transition: all .2s; font-family: 'Poppins',sans-serif; }
        .tab-btn:hover, .tab-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }

        /* Section card */
        .section-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.07); overflow: hidden; margin-bottom: 2rem; }
        .section-head { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0; background: linear-gradient(135deg,var(--primary),var(--secondary)); color: #fff; }
        .section-head h2 { font-size: .98rem; font-weight: 600; margin: 0; }

        /* Table */
        .admin-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
        .admin-table th { background: #f8f9fa; padding: .75rem 1rem; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #f0f0f0; white-space: nowrap; }
        .admin-table td { padding: .7rem 1rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        .admin-table tr:hover td { background: #fafffe; }
        .admin-table tr:last-child td { border: none; }

        /* Badges */
        .badge-e { padding: .25rem .7rem; border-radius: 20px; font-size: .74rem; font-weight: 600; white-space: nowrap; }
        .badge-pendiente  { background: #fff3cd; color: #856404; }
        .badge-cerrado    { background: #e8f5ee; color: #00754a; }
        .badge-confirmado { background: #d1ecf1; color: #0c5460; }
        .badge-en_camino  { background: #d4edda; color: #155724; }
        .badge-entregado  { background: #e8f5ee; color: #00754a; }
        .badge-cancelado  { background: #f8d7da; color: #721c24; }

        /* Buttons */
        .btn-a { padding: .28rem .7rem; border-radius: 8px; font-size: .77rem; font-weight: 600; border: none; cursor: pointer; transition: all .2s; font-family: 'Poppins',sans-serif; display: inline-flex; align-items: center; gap: .25rem; }
        .btn-view    { background: #e8f5ee; color: var(--accent-hover); }
        .btn-view:hover   { background: var(--accent); color: #fff; }
        .btn-close   { background: #d1ecf1; color: #0c5460; }
        .btn-close:hover  { background: #0c5460; color: #fff; }
        .btn-edit    { background: #fff3cd; color: #856404; }
        .btn-edit:hover   { background: #856404; color: #fff; }
        .btn-delete  { background: #fff0f0; color: #e53e3e; }
        .btn-delete:hover { background: #e53e3e; color: #fff; }
        .btn-danger  { background: #e53e3e; color: #fff; padding: .5rem 1.2rem; border-radius: 10px; font-size: .85rem; }
        .btn-danger:hover { background: #c53030; }
        .btn-success { background: var(--accent); color: #fff; padding: .5rem 1.2rem; border-radius: 10px; font-size: .85rem; }
        .btn-success:hover { background: var(--accent-hover); }

        .select-estado { padding: .25rem .55rem; border-radius: 8px; border: 1.5px solid #e0e0e0; font-size: .78rem; font-family: 'Poppins',sans-serif; cursor: pointer; background: #fff; }
        .select-estado:focus { outline: none; border-color: var(--accent); }

        /* Filter bar */
        .filter-bar { display: flex; gap: .45rem; flex-wrap: wrap; margin-bottom: 1rem; align-items: center; }
        .filter-a { padding: .28rem .85rem; border-radius: 20px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; border: 1.5px solid transparent; }
        .fa-all  { background: #f0f0f0; color: #555; }
        .fa-pend { background: #fff3cd; color: #856404; border-color: #ffc107; }
        .fa-cerr { background: #e8f5ee; color: #00754a; border-color: var(--accent); }
        .fa-conf { background: #d1ecf1; color: #0c5460; border-color: #17a2b8; }
        .fa-entr { background: #d4edda; color: #155724; border-color: #28a745; }
        .fa-canc { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        .filter-a:hover, .filter-a.active { opacity: .75; transform: translateY(-1px); }

        /* Modal */
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 2000; align-items: center; justify-content: center; }
        .modal-bg.show { display: flex; }
        .modal-box { background: #fff; border-radius: 18px; width: min(540px,95vw); max-height: 88vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,.22); animation: popIn .3s cubic-bezier(.175,.885,.32,1.275); }
        .modal-box::-webkit-scrollbar { width: 4px; }
        .modal-box::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
        @keyframes popIn { from{transform:scale(.95);opacity:0} to{transform:scale(1);opacity:1} }
        .modal-head { background: linear-gradient(135deg,var(--primary),var(--secondary)); color: #fff; padding: 1.2rem 1.4rem; display: flex; justify-content: space-between; align-items: center; border-radius: 18px 18px 0 0; }
        .modal-head h3 { margin: 0; font-size: 1rem; font-weight: 600; }
        .modal-x { background: rgba(255,255,255,.15); border: none; color: #fff; width: 28px; height: 28px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .modal-x:hover { background: rgba(255,255,255,.3); }
        .modal-body { padding: 1.4rem; }

        .d-row { display: flex; justify-content: space-between; padding: .45rem 0; border-bottom: 1px solid #f5f5f5; font-size: .86rem; }
        .d-row:last-child { border: none; }
        .d-lbl { color: #888; font-weight: 500; }
        .d-val { color: #333; font-weight: 600; text-align: right; }
        .sec-sub { font-size: .78rem; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: .5px; margin: 1rem 0 .5rem; }
        .pi-row { display: flex; align-items: center; gap: .8rem; padding: .6rem 0; border-bottom: 1px solid #f5f5f5; }
        .pi-row:last-child { border: none; }
        .pi-row img { width: 44px; height: 44px; object-fit: cover; border-radius: 8px; background: #eee; flex-shrink: 0; }
        .pi-name { font-size: .85rem; font-weight: 600; color: #333; }
        .pi-qty  { font-size: .78rem; color: #888; }
        .pi-price { font-size: .88rem; font-weight: 700; color: var(--accent-hover); margin-left: auto; white-space: nowrap; }
        .total-row-final { display: flex; justify-content: space-between; font-size: 1rem; font-weight: 700; color: var(--primary); margin-top: .8rem; padding-top: .8rem; border-top: 2px solid #f0f0f0; }

        /* PIN Modal */
        .pin-inputs { display: flex; gap: .6rem; justify-content: center; margin: 1.2rem 0; }
        .pin-input  { width: 52px; height: 52px; border: 2px solid #e0e0e0; border-radius: 10px; text-align: center; font-size: 1.4rem; font-weight: 700; font-family: 'Poppins',sans-serif; transition: border-color .2s; }
        .pin-input:focus { outline: none; border-color: var(--accent); }
        .pin-error { color: #e53e3e; font-size: .83rem; text-align: center; min-height: 1.2rem; }

        /* Empty */
        .empty-st { text-align: center; padding: 2.5rem; color: #aaa; }
        .empty-st .ei { font-size: 2.2rem; }

        @media(max-width:768px) {
            .admin-wrap { padding: 1rem; }
            .admin-table { font-size: .76rem; }
            .admin-table th, .admin-table td { padding: .45rem .55rem; }
        }
    </style>
</head>
<body>

<nav class="navbar-admin">
    <a href="index.php" class="navbar-brand-admin">🏥 Farmacia Admin</a>
    <div class="navbar-right">
        <span class="navbar-user">👤 <?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <a href="index.php" class="btn-nav btn-nav-site">← Ver sitio</a>
        <a href="funcs/logout.php" class="btn-nav btn-nav-logout">Cerrar Sesión</a>
    </div>
</nav>

<div class="admin-wrap">
    <h1 class="page-title">Panel de Control</h1>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon ic-green">💊</div><div><div class="stat-value"><?= $total_productos ?></div><div class="stat-label">Productos</div></div></div>
        <div class="stat-card"><div class="stat-icon ic-blue">👥</div><div><div class="stat-value"><?= $total_usuarios ?></div><div class="stat-label">Usuarios</div></div></div>
        <div class="stat-card"><div class="stat-icon ic-orange">🛒</div><div><div class="stat-value"><?= $total_ventas ?></div><div class="stat-label">Ventas</div></div></div>
        <div class="stat-card"><div class="stat-icon ic-purple">📦</div><div><div class="stat-value"><?= $total_pedidos ?></div><div class="stat-label">Pedidos</div></div></div>
        <div class="stat-card"><div class="stat-icon ic-green">💰</div><div><div class="stat-value">RD$<?= number_format($ingresos_total,0) ?></div><div class="stat-label">Ingresos totales</div></div></div>
        <div class="stat-card"><div class="stat-icon ic-orange">⏳</div><div><div class="stat-value"><?= $pedidos_pendientes + $ventas_pendientes ?></div><div class="stat-label">Pendientes</div></div></div>
        <?php if ($stock_bajo_count > 0): ?>
        <div class="stat-card" style="border:2px solid #fca5a5;">
            <div class="stat-icon ic-red">⚠️</div>
            <div>
                <div class="stat-value" style="color:#e53e3e;"><?= $stock_bajo_count ?></div>
                <div class="stat-label">Stock bajo</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn <?= $active_tab==='ventas'   ? 'active':'' ?>" onclick="showTab('tab-ventas',this)">🛒 Ventas</button>
        <button class="tab-btn <?= $active_tab==='pedidos'  ? 'active':'' ?>" onclick="showTab('tab-pedidos',this)">📦 Pedidos</button>
        <button class="tab-btn <?= $active_tab==='acciones' ? 'active':'' ?>" onclick="showTab('tab-acciones',this)">⚙️ Acciones</button>
    </div>

    <!-- ══ TAB VENTAS ══ -->
    <div id="tab-ventas" class="tab-content" style="<?= $active_tab!=='ventas' ? 'display:none' : '' ?>">

        <div class="filter-bar">
            <a href="?tab=ventas&venta_estado=todos"     class="filter-a fa-all  <?= $filtro_venta==='todos'     ?'active':'' ?>">Todas (<?= $total_ventas ?>)</a>
            <a href="?tab=ventas&venta_estado=pendiente" class="filter-a fa-pend <?= $filtro_venta==='pendiente' ?'active':'' ?>">⏳ Pendiente (<?= $ventas_pendientes ?>)</a>
            <a href="?tab=ventas&venta_estado=cerrado"   class="filter-a fa-cerr <?= $filtro_venta==='cerrado'   ?'active':'' ?>">✅ Cerrado (<?= $ventas_cerradas ?>)</a>
            <button class="btn-a btn-delete" style="margin-left:auto;" onclick="confirmarResetear()">🗑 Resetear tabla</button>
        </div>

        <div class="section-card">
            <div class="section-head"><h2>🛒 Gestión de Ventas</h2></div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($ventas && $ventas->num_rows > 0):
                        while ($v = $ventas->fetch_assoc()):
                            $estado_v = $v['estado'] ?? 'pendiente';
                    ?>
                        <tr id="venta-row-<?= $v['id'] ?>">
                            <td><strong>#<?= $v['id'] ?></strong></td>
                            <td><?= htmlspecialchars($v['usuario_nombre']) ?></td>
                            <td style="font-size:.8rem;white-space:nowrap;"><?= $v['fecha'] ?></td>
                            <td><strong style="color:var(--accent-hover);">RD$<?= number_format($v['total'],2) ?></strong></td>
                            <td>
                                <span class="badge-e badge-<?= $estado_v ?>" id="badge-venta-<?= $v['id'] ?>">
                                    <?= $estado_v === 'cerrado' ? '✅ Cerrado' : '⏳ Pendiente' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                                    <!-- Ver productos -->
                                    <button class="btn-a btn-view" onclick="verProductosVenta(<?= $v['id'] ?>)">👁 Ver</button>

                                    <?php if ($estado_v === 'pendiente'): ?>
                                    <!-- Cerrar venta -->
                                    <button class="btn-a btn-close" onclick="cerrarVenta(<?= $v['id'] ?>)">🔒 Cerrar</button>
                                    <?php else: ?>
                                    <!-- Modificar (requiere PIN) -->
                                    <button class="btn-a btn-edit" onclick="abrirPinModal(<?= $v['id'] ?>)">✏️ Modificar</button>
                                    <?php endif; ?>

                                    <!-- Eliminar -->
                                    <button class="btn-a btn-delete" onclick="eliminarVenta(<?= $v['id'] ?>)">🗑</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6"><div class="empty-st"><div class="ei">🛒</div><p>No hay ventas registradas</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══ TAB PEDIDOS ══ -->
    <div id="tab-pedidos" class="tab-content" style="<?= $active_tab!=='pedidos' ? 'display:none' : '' ?>">

        <div class="filter-bar">
            <a href="?tab=pedidos&pedido_estado=todos"      class="filter-a fa-all  <?= $filtro_pedido==='todos'      ?'active':'' ?>">Todos (<?= $total_pedidos ?>)</a>
            <a href="?tab=pedidos&pedido_estado=pendiente"  class="filter-a fa-pend <?= $filtro_pedido==='pendiente'  ?'active':'' ?>">⏳ Pendiente (<?= $pedidos_pendientes ?>)</a>
            <a href="?tab=pedidos&pedido_estado=confirmado" class="filter-a fa-conf <?= $filtro_pedido==='confirmado' ?'active':'' ?>">✅ Confirmado (<?= $pedidos_confirmados ?>)</a>
            <a href="?tab=pedidos&pedido_estado=entregado"  class="filter-a fa-entr <?= $filtro_pedido==='entregado'  ?'active':'' ?>">📬 Entregado (<?= $pedidos_entregados ?>)</a>
            <a href="?tab=pedidos&pedido_estado=cancelado"  class="filter-a fa-canc <?= $filtro_pedido==='cancelado'  ?'active':'' ?>">❌ Cancelado (<?= $pedidos_cancelados ?>)</a>
        </div>

        <div class="section-card">
            <div class="section-head"><h2>📦 Gestión de Pedidos</h2></div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Cliente</th><th>Envío</th><th>Pago</th>
                            <th>Total</th><th>Fecha</th><th>Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($pedidos && $pedidos->num_rows > 0):
                        while ($p = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($p['nombre'] ?? 'N/A') ?></div>
                                <div style="font-size:.73rem;color:#888;"><?= htmlspecialchars($p['telefono'] ?? '') ?></div>
                            </td>
                            <td style="font-size:.8rem;"><?= htmlspecialchars($p['metodo_envio'] ?? '-') ?></td>
                            <td style="font-size:.8rem;"><?= htmlspecialchars($p['metodo_pago']  ?? '-') ?></td>
                            <td><strong style="color:var(--accent-hover);">RD$<?= number_format($p['total'],2) ?></strong></td>
                            <td style="font-size:.78rem;white-space:nowrap;"><?= $p['fecha_pedido'] ?></td>
                            <td><span class="badge-e badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                            <td>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:center;">
                                    <button class="btn-a btn-view" onclick="verDetallesPedido(<?= $p['id'] ?>,'<?= addslashes($p['nombre']??'') ?>','<?= addslashes($p['email']??'') ?>','<?= addslashes($p['telefono']??'') ?>','<?= addslashes($p['direccion']??'') ?>','<?= addslashes($p['ciudad']??'') ?>','<?= addslashes($p['metodo_envio']??'') ?>','<?= addslashes($p['metodo_pago']??'') ?>',<?= $p['total'] ?>,'<?= $p['estado'] ?>','<?= $p['fecha_pedido'] ?>')">👁 Ver</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="cambiar_estado_pedido">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <select name="estado" class="select-estado" onchange="this.form.submit()">
                                            <option value="pendiente"  <?= $p['estado']==='pendiente'  ?'selected':'' ?>>⏳ Pendiente</option>
                                            <option value="confirmado" <?= $p['estado']==='confirmado' ?'selected':'' ?>>✅ Confirmado</option>
                                            <option value="en_camino"  <?= $p['estado']==='en_camino'  ?'selected':'' ?>>🚚 En camino</option>
                                            <option value="entregado"  <?= $p['estado']==='entregado'  ?'selected':'' ?>>📬 Entregado</option>
                                            <option value="cancelado"  <?= $p['estado']==='cancelado'  ?'selected':'' ?>>❌ Cancelado</option>
                                        </select>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar pedido #<?= $p['id'] ?>? Esto no restaura el stock.')">
                                        <input type="hidden" name="action" value="eliminar_pedido">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-a btn-delete">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="8"><div class="empty-st"><div class="ei">📭</div><p>No hay pedidos</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ══ TAB ACCIONES ══ -->
    <div id="tab-acciones" class="tab-content" style="<?= $active_tab!=='acciones' ? 'display:none' : '' ?>">

        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.7rem;">
                <div style="font-weight:700;color:var(--primary);font-size:1rem;">⚙️ Gestión de Inventario</div>
                <p style="font-size:.82rem;color:#888;margin:0;">Agrega, edita o elimina productos desde el panel de productos.</p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <a href="admin_products.php" class="btn-a btn-view" style="padding:.55rem 1.1rem;font-size:.86rem;">💊 Gestionar Productos</a>
                </div>
            </div>
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.4rem;">
                <div style="font-weight:600;font-size:.9rem;color:#555;">📊 Resumen ventas</div>
                <div style="font-size:.83rem;color:#666;line-height:2;">
                    ⏳ Pendientes: <strong><?= $ventas_pendientes ?></strong><br>
                    ✅ Cerradas: <strong><?= $ventas_cerradas ?></strong><br>
                    💰 Ingresos ventas: <strong>RD$<?= number_format($ingresos_ventas,2) ?></strong>
                </div>
            </div>
            <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:.4rem;">
                <div style="font-weight:600;font-size:.9rem;color:#555;">📊 Resumen pedidos</div>
                <div style="font-size:.83rem;color:#666;line-height:2;">
                    ⏳ Pendientes: <strong><?= $pedidos_pendientes ?></strong><br>
                    ✅ Confirmados: <strong><?= $pedidos_confirmados ?></strong><br>
                    📬 Entregados: <strong><?= $pedidos_entregados ?></strong><br>
                    ❌ Cancelados: <strong><?= $pedidos_cancelados ?></strong>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-head" style="<?= $stock_bajo_count > 0 ? 'background:linear-gradient(135deg,#7f1d1d,#b91c1c)' : '' ?>">
                <h2>
                    <?= $stock_bajo_count > 0 ? '⚠️' : '✅' ?> Stock bajo
                    <?php if ($stock_bajo_count > 0): ?>
                        <span style="background:rgba(255,255,255,.2);padding:.1rem .6rem;border-radius:20px;font-size:.8rem;margin-left:.5rem;">
                            <?= $stock_bajo_count ?> producto<?= $stock_bajo_count > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                </h2>
                <a href="admin_products.php" class="btn-a btn-view" style="font-size:.8rem;padding:.3rem .8rem;">Gestionar →</a>
            </div>

            <?php if ($stock_bajo_count === 0): ?>
                <div class="empty-st"><div class="ei">✅</div><p>Todos los productos tienen stock suficiente</p></div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Producto</th><th>Stock actual</th><th>Nivel</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                        <?php $stock_bajo->data_seek(0); while ($p = $stock_bajo->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.7rem;">
                                        <img src="<?= htmlspecialchars($p['imagen'] ?? 'assets/img/default.jpg') ?>"
                                             onerror="this.src='assets/img/default.jpg'"
                                             style="width:36px;height:36px;object-fit:cover;border-radius:8px;background:#eee;">
                                        <strong style="font-size:.88rem;"><?= htmlspecialchars($p['nombre']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <strong style="font-size:1.1rem;color:<?= $p['stock'] == 0 ? '#e53e3e' : ($p['stock'] <= 2 ? '#d97706' : '#856404') ?>;">
                                        <?= $p['stock'] ?>
                                    </strong>
                                    <span style="font-size:.75rem;color:#aaa;"> unidades</span>
                                </td>
                                <td>
                                    <?php if ($p['stock'] == 0): ?>
                                        <span class="badge-e badge-cancelado">🔴 Agotado</span>
                                    <?php elseif ($p['stock'] <= 2): ?>
                                        <span class="badge-e badge-pendiente">🟠 Crítico</span>
                                    <?php else: ?>
                                        <span class="badge-e" style="background:#fff3cd;color:#856404;">🟡 Bajo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="admin_edit_product.php?id=<?= $p['id'] ?>" class="btn-a btn-edit" style="font-size:.8rem;">✏️ Editar stock</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ MODAL: VER PRODUCTOS DE VENTA ══ -->
<div id="modal-venta" class="modal-bg">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modal-venta-title">Productos de la Venta</h3>
            <button class="modal-x" onclick="cerrarModal('modal-venta')">×</button>
        </div>
        <div class="modal-body" id="modal-venta-body">Cargando...</div>
    </div>
</div>

<!-- ══ MODAL: VER DETALLES PEDIDO ══ -->
<div id="modal-pedido" class="modal-bg">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modal-pedido-title">Detalles del Pedido</h3>
            <button class="modal-x" onclick="cerrarModal('modal-pedido')">×</button>
        </div>
        <div class="modal-body" id="modal-pedido-body">Cargando...</div>
    </div>
</div>

<!-- ══ MODAL: PIN ══ -->
<div id="modal-pin" class="modal-bg">
    <div class="modal-box" style="max-width:360px;">
        <div class="modal-head">
            <h3>🔐 PIN de Administrador</h3>
            <button class="modal-x" onclick="cerrarModal('modal-pin')">×</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <p style="color:#666;font-size:.88rem;margin-bottom:.5rem;">Ingresa el PIN para modificar esta venta cerrada</p>
            <div class="pin-inputs">
                <input type="password" class="pin-input" maxlength="1" oninput="pinNext(this,0)" id="pin0">
                <input type="password" class="pin-input" maxlength="1" oninput="pinNext(this,1)" id="pin1">
                <input type="password" class="pin-input" maxlength="1" oninput="pinNext(this,2)" id="pin2">
                <input type="password" class="pin-input" maxlength="1" oninput="pinNext(this,3)" id="pin3">
            </div>
            <p class="pin-error" id="pin-error"></p>
            <button class="btn-a btn-view" style="padding:.6rem 1.5rem;font-size:.88rem;margin-top:.5rem;" onclick="verificarPin()">Verificar PIN</button>
        </div>
    </div>
</div>

<!-- ══ MODAL: MODIFICAR VENTA ══ -->
<div id="modal-modificar" class="modal-bg">
    <div class="modal-box" style="max-width:360px;">
        <div class="modal-head">
            <h3>✏️ Modificar Venta <span id="mod-venta-id"></span></h3>
            <button class="modal-x" onclick="cerrarModal('modal-modificar')">×</button>
        </div>
        <div class="modal-body">
            <p style="color:#666;font-size:.85rem;margin-bottom:1rem;">Selecciona el nuevo estado de la venta:</p>
            <select id="mod-estado-select" style="width:100%;padding:.6rem;border:1.5px solid #e0e0e0;border-radius:8px;font-family:'Poppins',sans-serif;font-size:.88rem;margin-bottom:1rem;">
                <option value="pendiente">⏳ Pendiente</option>
                <option value="cerrado">✅ Cerrado</option>
            </select>
            <button class="btn-a btn-success" style="width:100%;justify-content:center;padding:.7rem;" onclick="guardarModificacion()">Guardar cambio</button>
        </div>
    </div>
</div>

<script>
let ventaIdActual = null;

// ── Tabs ──────────────────────────────────────
function showTab(id, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    btn.classList.add('active');
}

// ── Modales ───────────────────────────────────
function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('show'); }

document.querySelectorAll('.modal-bg').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});

// ── Ver productos de venta ────────────────────
function verProductosVenta(ventaId) {
    document.getElementById('modal-venta-title').textContent = `Productos — Venta #${ventaId}`;
    document.getElementById('modal-venta-body').innerHTML = '<p style="text-align:center;color:#aaa;padding:2rem;">Cargando...</p>';
    abrirModal('modal-venta');

    fetch(`funcs/get_sale_items.php?venta_id=${ventaId}`)
        .then(r => r.json())
        .then(items => {
            if (!items.length) {
                document.getElementById('modal-venta-body').innerHTML =
                    '<p style="text-align:center;color:#aaa;">Sin productos registrados</p>';
                return;
            }
            let total = 0;
            const html = items.map(item => {
                const sub = parseFloat(item.precio) * parseInt(item.cantidad);
                total += sub;
                return `<div class="pi-row">
                    <img src="${item.imagen||'assets/img/default.jpg'}" onerror="this.src='assets/img/default.jpg'" alt="${item.nombre}">
                    <div><div class="pi-name">${item.nombre}</div><div class="pi-qty">×${item.cantidad} · RD$${parseFloat(item.precio).toFixed(2)} c/u</div></div>
                    <div class="pi-price">RD$${sub.toFixed(2)}</div>
                </div>`;
            }).join('');
            document.getElementById('modal-venta-body').innerHTML =
                html + `<div class="total-row-final"><span>Total</span><span style="color:var(--accent-hover);">RD$${total.toFixed(2)}</span></div>`;
        })
        .catch(() => {
            document.getElementById('modal-venta-body').innerHTML =
                '<p style="text-align:center;color:#e53e3e;">Error cargando productos</p>';
        });
}

// ── Cerrar venta ──────────────────────────────
function cerrarVenta(ventaId) {
    if (!confirm(`¿Estás seguro de cerrar la Venta #${ventaId}?\nEsta acción marca la venta como completada.`)) return;

    fetch('funcs/ventas_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cerrar_venta', venta_id: ventaId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Actualizar badge y botones en la tabla sin recargar
            const badge = document.getElementById(`badge-venta-${ventaId}`);
            if (badge) {
                badge.className = 'badge-e badge-cerrado';
                badge.textContent = '✅ Cerrado';
            }
            // Recargar para actualizar botones
            setTimeout(() => location.reload(), 800);
            mostrarNotif(res.message, 'success');
        } else {
            mostrarNotif(res.error, 'error');
        }
    })
    .catch(() => mostrarNotif('Error de conexión', 'error'));
}

// ── Eliminar venta ────────────────────────────
function eliminarVenta(ventaId) {
    if (!confirm(`¿Eliminar Venta #${ventaId}?\n\n⚠️ El stock de los productos será restaurado automáticamente.`)) return;

    fetch('funcs/ventas_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'eliminar_venta', venta_id: ventaId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const row = document.getElementById(`venta-row-${ventaId}`);
            if (row) row.style.display = 'none';
            mostrarNotif(res.message, 'success');
        } else {
            mostrarNotif(res.error, 'error');
        }
    })
    .catch(() => mostrarNotif('Error de conexión', 'error'));
}

// ── Resetear tabla ventas ─────────────────────
function confirmarResetear() {
    const msg = `⚠️ ATENCIÓN — Esto eliminará TODAS las ventas.\n\nEl stock de todos los productos será restaurado.\n\n¿Estás completamente seguro?`;
    if (!confirm(msg)) return;
    if (!confirm('¿CONFIRMAS que deseas resetear la tabla de ventas? Esta acción es irreversible.')) return;

    fetch('funcs/ventas_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'resetear_ventas' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            mostrarNotif(res.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            mostrarNotif(res.error, 'error');
        }
    })
    .catch(() => mostrarNotif('Error de conexión', 'error'));
}

// ── PIN Modal ─────────────────────────────────
function abrirPinModal(ventaId) {
    ventaIdActual = ventaId;
    ['pin0','pin1','pin2','pin3'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('pin-error').textContent = '';
    abrirModal('modal-pin');
    setTimeout(() => document.getElementById('pin0').focus(), 200);
}

function pinNext(input, index) {
    if (input.value.length === 1 && index < 3) {
        document.getElementById(`pin${index + 1}`).focus();
    }
    // Auto-verificar cuando se llena el último
    if (index === 3 && input.value.length === 1) {
        setTimeout(verificarPin, 100);
    }
}

function verificarPin() {
    const pin = ['pin0','pin1','pin2','pin3'].map(id => document.getElementById(id).value).join('');
    if (pin.length < 4) {
        document.getElementById('pin-error').textContent = 'Ingresa los 4 dígitos del PIN';
        return;
    }

    fetch('funcs/ventas_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'verificar_pin', pin })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cerrarModal('modal-pin');
            // Abrir modal de modificación
            document.getElementById('mod-venta-id').textContent = `#${ventaIdActual}`;
            abrirModal('modal-modificar');
        } else {
            document.getElementById('pin-error').textContent = res.error;
            ['pin0','pin1','pin2','pin3'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('pin0').focus();
        }
    })
    .catch(() => {
        document.getElementById('pin-error').textContent = 'Error de conexión';
    });
}

function guardarModificacion() {
    const estado = document.getElementById('mod-estado-select').value;

    fetch('funcs/ventas_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'modificar_venta', venta_id: ventaIdActual, estado })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cerrarModal('modal-modificar');
            mostrarNotif(res.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else if (res.need_pin) {
            cerrarModal('modal-modificar');
            abrirPinModal(ventaIdActual);
        } else {
            mostrarNotif(res.error, 'error');
        }
    })
    .catch(() => mostrarNotif('Error de conexión', 'error'));
}

// ── Ver detalles pedido ───────────────────────
function verDetallesPedido(id, nombre, email, telefono, direccion, ciudad, metodoEnvio, metodoPago, total, estado, fecha) {
    document.getElementById('modal-pedido-title').textContent = `Pedido #${id}`;
    document.getElementById('modal-pedido-body').innerHTML = `
        <p class="sec-sub">Cliente</p>
        <div class="d-row"><span class="d-lbl">Nombre</span><span class="d-val">${nombre||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Email</span><span class="d-val">${email||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Teléfono</span><span class="d-val">${telefono||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Dirección</span><span class="d-val">${direccion||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Ciudad</span><span class="d-val">${ciudad||'-'}</span></div>
        <p class="sec-sub" style="margin-top:1rem;">Pedido</p>
        <div class="d-row"><span class="d-lbl">Método envío</span><span class="d-val">${metodoEnvio||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Método pago</span><span class="d-val">${metodoPago||'-'}</span></div>
        <div class="d-row"><span class="d-lbl">Fecha</span><span class="d-val">${fecha}</span></div>
        <div class="d-row"><span class="d-lbl">Estado</span><span class="d-val">${estado}</span></div>
        <p class="sec-sub" style="margin-top:1rem;">Productos</p>
        <div id="pedido-items-wrap">Cargando...</div>
        <div class="total-row-final"><span>Total</span><span style="color:var(--accent-hover);">RD$${parseFloat(total).toFixed(2)}</span></div>
    `;
    abrirModal('modal-pedido');

    fetch(`funcs/get_order_items.php?pedido_id=${id}`)
        .then(r => r.json())
        .then(items => {
            const wrap = document.getElementById('pedido-items-wrap');
            if (!items.length) { wrap.innerHTML = '<p style="color:#aaa;font-size:.83rem;">Sin productos registrados</p>'; return; }
            wrap.innerHTML = items.map(item => `
                <div class="pi-row">
                    <img src="${item.imagen||'assets/img/default.jpg'}" onerror="this.src='assets/img/default.jpg'" alt="${item.nombre}">
                    <div><div class="pi-name">${item.nombre}</div><div class="pi-qty">×${item.cantidad} · RD$${parseFloat(item.precio).toFixed(2)} c/u</div></div>
                    <div class="pi-price">RD$${(parseFloat(item.precio)*parseInt(item.cantidad)).toFixed(2)}</div>
                </div>`).join('');
        })
        .catch(() => { document.getElementById('pedido-items-wrap').innerHTML = '<p style="color:#aaa;font-size:.83rem;">No se pudieron cargar</p>'; });
}

// ── Notificaciones ────────────────────────────
function mostrarNotif(msg, tipo) {
    const n = document.createElement('div');
    n.style.cssText = `position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;padding:.85rem 1.3rem;border-radius:12px;font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.15);max-width:340px;border-left:4px solid ${tipo==='success'?'#00a86b':'#e53e3e'};background:#fff;animation:slideInToast .3s ease;`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3500);
}
</script>
</body>
</html>