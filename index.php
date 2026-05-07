<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FarmaExpress</title>
  <link rel="shortcut icon" href="assets/img/logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="styls.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>


<div id="toast-container" class="toast-container"></div>

<div id="cart-overlay" onclick="closeCartSidebar()"></div>
<div id="cart-sidebar" class="cart-sidebar">
  <div class="cart-header">
    <div class="cart-title-group">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2"/><path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <h3>Tu Carrito</h3>
    </div>
    <button id="close-cart" class="close-cart" onclick="closeCartSidebar()">×</button>
  </div>

  <div id="cart-items" class="cart-items">
    <div class="empty-cart" id="cart-empty-state">
      <span class="empty-cart-icon">🛒</span>
      <p>Tu carrito está vacío</p>
      <small>¡Explora nuestros productos!</small>
    </div>
  </div>

  <div class="cart-footer">
    <div class="cart-total-row">
      <span>Subtotal:</span>
      <span id="cart-total">RD$0.00</span>
    </div>
    <div class="cart-total-row cart-shipping-row">
      <span>Envío:</span>
      <span class="shipping-pending">Calculado al finalizar</span>
    </div>
    <button id="checkout-btn" class="checkout-btn" onclick="openCheckout()">
      Proceder al pago →
    </button>
    <button id="clear-cart" class="clear-cart clear-cart-btn" onclick="clearCartServer()">
      Vaciar carrito
    </button>
  </div>
</div>


<div id="checkout-overlay-modal" class="checkout-overlay-modal" onclick="closeCheckout()"></div>
<div id="checkout-modal" class="checkout-modal">
  <button class="checkout-modal-close" onclick="closeCheckout()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
  </button>

 
  <div class="steps-bar">
    <div class="step active" id="step-ind-1"><div class="step-circle">1</div><span>Envío</span></div>
    <div class="step-line"></div>
    <div class="step" id="step-ind-2"><div class="step-circle">2</div><span>Pago</span></div>
    <div class="step-line"></div>
    <div class="step" id="step-ind-3"><div class="step-circle">3</div><span>Confirmar</span></div>
  </div>

 
  <div id="checkout-step-1" class="checkout-step">
    <h3 class="step-title">Información de Envío</h3>

    <div class="delivery-selector">
      <label class="delivery-opt" id="opt-home">
        <input type="radio" name="delivery" value="home" checked onchange="selectDelivery('home')">
        <div class="delivery-card active">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><rect x="1" y="3" width="15" height="13" rx="1" stroke="currentColor" stroke-width="2"/><path d="M16 8h4l3 5v3h-7V8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="5.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="2"/><circle cx="18.5" cy="18.5" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
          <strong>Envío a domicilio</strong>
          <span>Desde RD$150 · 1-3 días</span>
        </div>
      </label>
      <label class="delivery-opt" id="opt-pickup">
        <input type="radio" name="delivery" value="pickup" onchange="selectDelivery('pickup')">
        <div class="delivery-card">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
          <strong>Recoger en tienda</strong>
          <span>Gratis · Listo en 2 horas</span>
        </div>
      </label>
    </div>

  
    <div id="form-home" class="form-grid">
      <div class="form-group span-2"><label>Nombre completo *</label><input type="text" id="sh-name" placeholder="Juan Pérez"></div>
      <div class="form-group"><label>Teléfono *</label><input type="tel" id="sh-phone" placeholder="+1 (809) 000-0000"></div>
      <div class="form-group"><label>Correo electrónico *</label><input type="email" id="sh-email" placeholder="correo@ejemplo.com"></div>
      <div class="form-group span-2"><label>Dirección *</label><input type="text" id="sh-address" placeholder="Calle, número, sector"></div>
      <div class="form-group"><label>Ciudad *</label><input type="text" id="sh-city" placeholder="Santiago"></div>
      <div class="form-group"><label>Provincia</label>
        <select id="sh-province">
          <option value="">Selecciona...</option>
          <option>Distrito Nacional</option><option>Santiago</option><option>La Vega</option>
          <option>San Pedro de Macorís</option><option>La Romana</option>
          <option>Puerto Plata</option><option>Otra</option>
        </select>
      </div>
      <div class="form-group span-2"><label>Referencia / Instrucciones</label><input type="text" id="sh-notes" placeholder="Color de puerta, punto de referencia..."></div>
    </div>

    
    <div id="form-pickup" style="display:none;">
      <div class="pickup-info-box">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="#00a86b" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="#00a86b" stroke-width="2"/></svg>
        <div>
          <strong>FarmaExpress — Sucursal Principal</strong>
          <p>Av. Principal #123, Santiago, RD</p>
          <p class="store-hours">Lun–Vie: 8am–8pm · Sáb–Dom: 9am–6pm</p>
        </div>
      </div>
      <div class="form-grid" style="margin-top:1rem;">
        <div class="form-group"><label>Nombre quien recoge *</label><input type="text" id="pk-name" placeholder="Nombre completo"></div>
        <div class="form-group"><label>Teléfono de contacto *</label><input type="tel" id="pk-phone" placeholder="+1 (809) 000-0000"></div>
      </div>
    </div>

    <div class="step-nav">
      <div></div>
      <button class="btn-step-next" onclick="goStep(2)">
        Continuar al pago
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </div>

  <div id="checkout-step-2" class="checkout-step" style="display:none;">
    <h3 class="step-title">Método de Pago</h3>

    <div class="payment-opts">
      <label class="payment-opt" id="pay-card-opt">
        <input type="radio" name="payment" value="card" checked onchange="selectPayment('card')">
        <div class="payment-card-item active">
          <svg width="34" height="24" viewBox="0 0 34 24" fill="none"><rect width="34" height="24" rx="4" fill="#1a1f36"/><rect y="7" width="34" height="6" fill="#2d3561"/><rect x="4" y="16" width="8" height="3" rx="1" fill="#fff" opacity="0.4"/></svg>
          <strong>Tarjeta crédito / débito</strong>
          <span>Visa · Mastercard · Amex</span>
        </div>
      </label>
      <label class="payment-opt" id="pay-transfer-opt">
        <input type="radio" name="payment" value="transfer" onchange="selectPayment('transfer')">
        <div class="payment-card-item">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="#00a86b" stroke-width="2" stroke-linecap="round"/></svg>
          <strong>Transferencia bancaria</strong>
          <span>Popular · BHD · Banreservas</span>
        </div>
      </label>
      <label class="payment-opt" id="pay-cash-opt">
        <input type="radio" name="payment" value="cash" onchange="selectPayment('cash')">
        <div class="payment-card-item">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="2" stroke="#00a86b" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="#00a86b" stroke-width="2"/></svg>
          <strong>Efectivo</strong>
          <span>Al recoger o contra entrega</span>
        </div>
      </label>
    </div>

    
    <div id="form-card" class="pay-detail">
      <div class="form-grid">
        <div class="form-group span-2">
          <label>Número de tarjeta *</label>
          <div class="card-field-wrap">
            <input type="text" id="card-number" placeholder="0000 0000 0000 0000" maxlength="19" oninput="fmtCard(this)">
            <span id="card-brand" class="card-brand-badge"></span>
          </div>
        </div>
        <div class="form-group span-2"><label>Nombre en la tarjeta *</label><input type="text" id="card-name" placeholder="Como aparece en la tarjeta"></div>
        <div class="form-group"><label>Vencimiento *</label><input type="text" id="card-exp" placeholder="MM / AA" maxlength="7" oninput="fmtExp(this)"></div>
        <div class="form-group"><label>CVV *</label>
          <div class="cvv-wrap">
            <input type="password" id="card-cvv" placeholder="•••" maxlength="4">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          </div>
        </div>
      </div>
    </div>

    
    <div id="form-transfer" class="pay-detail" style="display:none;">
      <div class="bank-box">
        <h4>Datos bancarios</h4>
        <div class="bank-row"><span>Banco</span><strong>Banco Popular Dominicano</strong></div>
        <div class="bank-row"><span>Cuenta</span><strong>123-456789-0</strong></div>
        <div class="bank-row"><span>Tipo</span><strong>Cuenta Corriente</strong></div>
        <div class="bank-row"><span>Titular</span><strong>FarmaExpress S.R.L.</strong></div>
        <div class="bank-row"><span>RNC</span><strong>1-00-12345-6</strong></div>
      </div>
      <div class="form-group span-2" style="margin-top:1rem;">
        <label>Número de comprobante *</label>
        <input type="text" id="transfer-ref" placeholder="Ej: 20240312-001234">
      </div>
      <p class="transfer-note">Tu pedido será confirmado en menos de 2 horas hábiles tras verificar el pago.</p>
    </div>

    <!-- Efectivo -->
    <div id="form-cash" class="pay-detail" style="display:none;">
      <div class="cash-box">
        <svg width="38" height="38" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#00a86b" stroke-width="2"/></svg>
        <div>
          <strong>Pago al recibir</strong>
          <p>Prepara el monto exacto. El pago se realiza al momento de la entrega o retiro.</p>
        </div>
      </div>
    </div>

    <div class="step-nav">
      <button class="btn-step-back" onclick="goStep(1)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Volver
      </button>
      <button class="btn-step-next" onclick="goStep(3)">
        Revisar pedido
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </div>

  <!-- ── PASO 3: Confirmación ── -->
  <div id="checkout-step-3" class="checkout-step" style="display:none;">
    <h3 class="step-title">Confirmar Pedido</h3>
    <div class="confirm-cols">
      <div class="confirm-col">
        <h4 class="confirm-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>
          Entrega
        </h4>
        <div id="confirm-shipping" class="confirm-info"></div>
      </div>
      <div class="confirm-col">
        <h4 class="confirm-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg>
          Pago
        </h4>
        <div id="confirm-payment" class="confirm-info"></div>
      </div>
    </div>
    <div class="confirm-products">
      <h4 class="confirm-label">Productos</h4>
      <div id="confirm-items"></div>
    </div>
    <div class="confirm-totals">
      <div class="confirm-total-row"><span>Subtotal</span><span id="conf-subtotal"></span></div>
      <div class="confirm-total-row"><span>Envío</span><span id="conf-shipping"></span></div>
      <div class="confirm-total-row grand"><span>Total</span><span id="conf-total"></span></div>
    </div>
    <div class="step-nav">
      <button class="btn-step-back" onclick="goStep(2)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Volver
      </button>
      <button class="btn-confirm-order" id="btn-confirm" onclick="submitOrder()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/></svg>
        Confirmar pedido
      </button>
    </div>
  </div>

  <!-- ── ÉXITO ── -->
  <div id="checkout-success" class="checkout-step success-step" style="display:none;">
    <div class="success-inner">
      <div class="success-icon-wrap">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#00a86b" stroke-width="1.5"/></svg>
      </div>
      <h3>¡Pedido confirmado!</h3>
      <p>Gracias por tu compra. Te contactaremos pronto con los detalles.</p>
      <div class="order-badge">Pedido #<span id="order-id-display"></span></div>
      <button class="btn-step-next" onclick="closeCheckout()" style="margin-top:1.8rem;width:100%;justify-content:center;">
        Volver a la tienda
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     HEADER
═══════════════════════════════════════════ -->
<header>
  <div class="header-container">
    <div class="logo-container">
      <img class="logo" src="assets/img/logo.webp" alt="Logo Farmacia" />
      <span class="brand-name">FarmaExpress</span>
    </div>

    <nav>
      <ul>
        <li><a href="#S1" class="nav-link active">Inicio</a></li>
        <li><a href="#S2" class="nav-link">Historia</a></li>
        <li><a href="#S3" class="nav-link">Medicamentos</a></li>

        <!-- Botón carrito -->
        <li class="cart-li">
          <button id="cart-button" class="cart-button" onclick="openCartSidebar()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2"/><path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <span id="cart-count" class="cart-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
          </button>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="user-info-li">
            <button id="user-menu-button" class="user-menu-button">
              <span class="user-avatar">👤</span>
              <span class="user-name"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
              <span class="dropdown-icon">▼</span>
            </button>
            <div id="user-dropdown" class="user-dropdown">
              <div class="dropdown-header">
                <p class="welcome-text">¡Bienvenido de nuevo!</p>
                <p class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
              </div>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">Mi Perfil</a>
              <a href="#" class="dropdown-item">Mis Pedidos</a>
              <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="dropdown-item">Panel Admin</a>
              <?php endif; ?>
              <div class="dropdown-divider"></div>
              <a href="funcs/logout.php" class="dropdown-item logout">Cerrar Sesión</a>
            </div>
          </li>
        <?php else: ?>
          <li><a href="register.php" class="nav-link">Registrarse</a></li>
          <li class="btn-login"><a href="login.php">Iniciar Sesión</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>

<!-- Welcome Modal -->
<?php if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true): ?>
<div id="welcome-modal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="welcome-icon">👋</span>
      <h2>¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h2>
    </div>
    <div class="modal-body">
      <p>Nos alegra verte de nuevo en <strong>FarmaExpress</strong></p>
      <div class="welcome-stats">
        <div class="stat-item"><span class="stat-icon">⭐</span><span class="stat-text">Cliente desde 2024</span></div>
        <div class="stat-item"><span class="stat-icon">🛒</span><span class="stat-text">Carrito listo para usar</span></div>
        <div class="stat-item"><span class="stat-icon">💊</span><span class="stat-text">Descubre nuestros productos</span></div>
      </div>
    </div>
    <div class="modal-footer">
      <button id="close-modal" class="modal-btn">Comenzar a comprar</button>
    </div>
  </div>
</div>
<?php
  $_SESSION['just_logged_in'] = false;
endif;
?>

<!-- ═══════════════════════════════════════════
     SECCIONES
═══════════════════════════════════════════ -->
<section id="S1">
  <div class="container">
    <div class="info">
      <h1>La mejor solución para tu salud y bienestar</h1>
      <p class="info-subtitle">Más de 25 años cuidando de ti y tu familia</p>
      <a class="cta-button" href="#S3">Ver productos</a>
    </div>
    <div class="img"><img src="assets/img/famacia.jpg" alt="Farmacia" /></div>
  </div>
</section>

<section id="S2">
  <div class="section-header">
    <h2 class="section-title">Nuestra Esencia</h2>
    <div class="section-divider"></div>
    <p class="section-subtitle">Conoce los pilares que nos definen como farmacia de confianza</p>
  </div>
  <div class="empresa-container">
    <div class="card-empresa"><div class="card-icon">📜</div><h3>Historia</h3><div class="card-content"><p>Fundada en 1995, nuestra farmacia nació con el sueño de brindar salud y bienestar a la comunidad. Lo que comenzó como un pequeño local familiar, hoy es un referente de confianza y calidez en toda la región.</p></div><div class="card-footer"><span class="decor-dot"></span><span class="decor-dot"></span><span class="decor-dot"></span></div></div>
    <div class="card-empresa"><div class="card-icon">🎯</div><h3>Misión</h3><div class="card-content"><p>Mejorar la calidad de vida de nuestros clientes ofreciendo productos farmacéuticos de alta calidad y un servicio personalizado, con un equipo comprometido y en constante actualización profesional.</p></div><div class="card-footer"><span class="decor-dot"></span><span class="decor-dot"></span><span class="decor-dot"></span></div></div>
    <div class="card-empresa"><div class="card-icon">🔭</div><h3>Visión</h3><div class="card-content"><p>Ser la farmacia de referencia en la región, reconocida por nuestra cercanía, innovación y responsabilidad social, expandiendo nuestros servicios para el cuidado integral de la salud.</p></div><div class="card-footer"><span class="decor-dot"></span><span class="decor-dot"></span><span class="decor-dot"></span></div></div>
    <div class="card-empresa"><div class="card-icon">💚</div><h3>Valores</h3><div class="card-content"><p>Ética profesional, empatía, honestidad y compromiso con la salud son los pilares que guían nuestro día a día. La transparencia y el respeto nos definen en cada interacción.</p></div><div class="card-footer"><span class="decor-dot"></span><span class="decor-dot"></span><span class="decor-dot"></span></div></div>
  </div>
</section>

<section id="S3">
  <div class="containerc">
    <div class="section-header">
      <h2 class="section-title">Nuestros Medicamentos</h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Productos de alta calidad para tu bienestar</p>
    </div>
    <div class="search-container">
      <input type="text" id="search-input" placeholder="Buscar medicamento..." class="search-input">
      <i class="search-icon">🔍</i>
    </div>
    <div class="products-grid" id="products-container"></div>
    <div class="load-more-container">
      <button id="load-more" class="load-more-btn" style="display:none;">Ver más productos</button>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-column"><h3>Sobre Nosotros</h3><a href="#">Nuestra Historia</a><a href="#">Responsabilidad Social</a><a href="#">Trabaja con Nosotros</a><a href="#">Prensa</a></div>
    <div class="footer-column"><h3>Servicio al Cliente</h3><a href="#">Contáctanos</a><a href="#">Soporte</a><a href="#">Preguntas Frecuentes</a><a href="#">Envíos</a></div>
    <div class="footer-column"><h3>Tiendas</h3><a href="#">Localizador</a><a href="#">Nuevos Productos</a><a href="#">Promociones</a></div>
    <div class="footer-column"><h3>Conéctate</h3><div class="social"><a href="#">Facebook</a><a href="#">Instagram</a><a href="#">YouTube</a><a href="#">TikTok</a></div></div>
  </div>
  <div class="footer-bottom"><p>© 2025 FarmaExpress — Cuidando de tu salud con amor y dedicación</p></div>
</footer>

<!-- ═══════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════ -->
<script>
// ── Estado global (solo UI — fuente de verdad: servidor) ──
let cartItems       = <?= json_encode(array_values($_SESSION['cart'])) ?>;
let currentDelivery = 'home';
let currentPayment  = 'card';
const SHIPPING      = 150;

function fmt(v) { return `RD$${parseFloat(v).toFixed(2)}`; }

// ══════════════════════════════════════════════
// TOASTS
// ══════════════════════════════════════════════
function showToast(message, type = 'success') {
  const c = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `<span class="toast-message">${message}</span>`;
  c.appendChild(t);
  setTimeout(() => { t.classList.add('fade-out'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ══════════════════════════════════════════════
// CART — TODAS LAS OPERACIONES VAN AL SERVIDOR
// ══════════════════════════════════════════════
function cartRequest(formData, callback) {
  fetch('funcs/cart_functions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (res.cart !== undefined) {
        cartItems = res.cart;
        updateCartUI();
      }
      if (callback) callback(res);
    })
    .catch(() => showToast('❌ Error de conexión', 'error'));
}

// Llamado desde los botones de los productos (generados en get_products.php)
function addToCartItem(product) {
  const fd = new FormData();
  fd.append('action', 'add');
  fd.append('id',     product.id);
  fd.append('name',   product.name);
  fd.append('price',  product.price);
  fd.append('image',  product.image);

  cartRequest(fd, res => {
    if (res.success) {
      showToast(`✅ <strong>${product.name}</strong> añadido al carrito`, 'success');
    } else {
      showToast(`❌ ${res.message}`, 'error');
    }
  });
}

function updateQtyServer(id, newQty) {
  const fd = new FormData();
  fd.append('action',   'update');
  fd.append('id',       id);
  fd.append('quantity', newQty);

  cartRequest(fd, res => {
    if (!res.success) showToast(`❌ ${res.message}`, 'error');
  });
}

function removeItemServer(id) {
  const fd = new FormData();
  fd.append('action', 'remove');
  fd.append('id', id);
  cartRequest(fd, () => showToast('🗑️ Producto eliminado', 'info'));
}

function clearCartServer() {
  const fd = new FormData();
  fd.append('action', 'clear');
  cartRequest(fd, () => showToast('🛒 Carrito vaciado', 'info'));
}

// Compatibilidad con el CartManager antiguo (por si algún otro archivo lo llama)
const cartManager = {
  addItem: addToCartItem,
  showToast: showToast
};

// ══════════════════════════════════════════════
// CART UI
// ══════════════════════════════════════════════
function updateCartUI() {
  const count    = cartItems.reduce((s, i) => s + parseInt(i.quantity), 0);
  const subtotal = cartItems.reduce((s, i) => s + parseFloat(i.price) * parseInt(i.quantity), 0);

  // Badge
  const badge = document.getElementById('cart-count');
  badge.textContent = count;
  badge.style.display = count > 0 ? 'flex' : 'none';

  // Total en sidebar
  document.getElementById('cart-total').textContent = fmt(subtotal);

  // Render items
  const wrap  = document.getElementById('cart-items');
  const empty = document.getElementById('cart-empty-state');

  if (cartItems.length === 0) {
    wrap.innerHTML = '';
    wrap.appendChild(empty);
    empty.style.display = 'flex';
    return;
  }

  empty.style.display = 'none';
  wrap.innerHTML = cartItems.map(item => `
    <div class="cart-item-row">
      <img src="${item.image}" alt="${item.name}" class="cart-item-image" onerror="this.src='assets/img/default.jpg'">
      <div class="cart-item-details">
        <h4 class="cart-item-title">${item.name}</h4>
        <p class="cart-item-price">${fmt(item.price)}</p>
        <div class="cart-item-quantity">
          <button class="quantity-btn" onclick="updateQtyServer(${item.id}, ${parseInt(item.quantity) - 1})">−</button>
          <span class="quantity-value">${item.quantity}</span>
          <button class="quantity-btn" onclick="updateQtyServer(${item.id}, ${parseInt(item.quantity) + 1})">+</button>
        </div>
      </div>
      <button class="remove-item-btn" onclick="removeItemServer(${item.id})">×</button>
    </div>
  `).join('');
  wrap.appendChild(empty);
}

function openCartSidebar() {
  // Sincronizar con el servidor antes de mostrar
  fetch('funcs/cart_functions.php?action=get')
    .then(r => r.json())
    .then(res => { cartItems = res.cart; updateCartUI(); });

  document.getElementById('cart-sidebar').classList.add('open');
  document.getElementById('cart-overlay').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeCartSidebar() {
  document.getElementById('cart-sidebar').classList.remove('open');
  document.getElementById('cart-overlay').style.display = 'none';
  document.body.style.overflow = '';
}

// ══════════════════════════════════════════════
// CHECKOUT
// ══════════════════════════════════════════════
function openCheckout() {
  if (cartItems.length === 0) { showToast('🛒 Tu carrito está vacío', 'info'); return; }

  <?php if (!isset($_SESSION['user_id'])): ?>
  showToast('🔑 Por favor inicia sesión para continuar', 'info');
  setTimeout(() => window.location.href = 'login.php', 1500);
  return;
  <?php endif; ?>

  closeCartSidebar();
  goStep(1);
  document.getElementById('checkout-modal').classList.add('open');
  document.getElementById('checkout-overlay-modal').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeCheckout() {
  document.getElementById('checkout-modal').classList.remove('open');
  document.getElementById('checkout-overlay-modal').style.display = 'none';
  document.body.style.overflow = '';
}

function goStep(step) {
  if (step === 2 && !validateStep1()) return;
  if (step === 3 && !validateStep2()) return;
  if (step === 3) buildConfirmStep();

  [1,2,3].forEach(n => {
    document.getElementById(`checkout-step-${n}`).style.display = 'none';
    const ind = document.getElementById(`step-ind-${n}`);
    ind.classList.remove('active','done');
    if (n < step) ind.classList.add('done');
    if (n === step) ind.classList.add('active');
  });
  document.getElementById('checkout-success').style.display = 'none';
  document.getElementById(`checkout-step-${step}`).style.display = 'block';
  document.getElementById('checkout-modal').scrollTop = 0;
}

function validateStep1() {
  if (currentDelivery === 'home') {
    const fields = [['sh-name','Nombre'],['sh-phone','Teléfono'],['sh-email','Correo'],['sh-address','Dirección'],['sh-city','Ciudad']];
    for (const [id, label] of fields) {
      if (!document.getElementById(id).value.trim()) {
        showToast(`⚠️ Completa el campo: ${label}`, 'error');
        document.getElementById(id).focus();
        return false;
      }
    }
  } else {
    if (!document.getElementById('pk-name').value.trim()) {
      showToast('⚠️ Ingresa el nombre de quien recoge', 'error');
      return false;
    }
  }
  return true;
}

function validateStep2() {
  if (currentPayment === 'card') {
    if (document.getElementById('card-number').value.replace(/\s/g,'').length < 16) { showToast('⚠️ Número de tarjeta inválido', 'error'); return false; }
    if (!document.getElementById('card-name').value.trim())                          { showToast('⚠️ Ingresa el nombre en la tarjeta', 'error'); return false; }
    if (!document.getElementById('card-exp').value.trim())                           { showToast('⚠️ Ingresa la fecha de vencimiento', 'error'); return false; }
    if (document.getElementById('card-cvv').value.length < 3)                        { showToast('⚠️ CVV inválido', 'error'); return false; }
  } else if (currentPayment === 'transfer') {
    if (!document.getElementById('transfer-ref').value.trim()) { showToast('⚠️ Ingresa el número de comprobante', 'error'); return false; }
  }
  return true;
}

function buildConfirmStep() {
  // Info envío
  let shHtml = '';
  if (currentDelivery === 'home') {
    shHtml = `<p><strong>${document.getElementById('sh-name').value}</strong></p>
              <p>${document.getElementById('sh-address').value}, ${document.getElementById('sh-city').value}</p>
              <p>${document.getElementById('sh-phone').value}</p>
              <span class="badge-delivery">🚚 Envío a domicilio · ${fmt(SHIPPING)}</span>`;
  } else {
    shHtml = `<p><strong>${document.getElementById('pk-name').value}</strong></p>
              <p>Av. Principal #123, Santiago, RD</p>
              <p>${document.getElementById('pk-phone').value}</p>
              <span class="badge-delivery">🏠 Retiro en tienda · Gratis</span>`;
  }
  document.getElementById('confirm-shipping').innerHTML = shHtml;

  const payLabels = { card:'💳 Tarjeta crédito/débito', transfer:'🏦 Transferencia bancaria', cash:'💵 Efectivo' };
  document.getElementById('confirm-payment').innerHTML = `<p>${payLabels[currentPayment]}</p>`;

  document.getElementById('confirm-items').innerHTML = cartItems.map(i => `
    <div class="confirm-item-row">
      <img src="${i.image}" alt="${i.name}" onerror="this.src='assets/img/default.jpg'">
      <div><p>${i.name}</p><p class="confirm-qty">×${i.quantity}</p></div>
      <strong>${fmt(parseFloat(i.price) * parseInt(i.quantity))}</strong>
    </div>`).join('');

  const subtotal = cartItems.reduce((s,i) => s + parseFloat(i.price) * parseInt(i.quantity), 0);
  const shipping = currentDelivery === 'home' ? SHIPPING : 0;
  document.getElementById('conf-subtotal').textContent = fmt(subtotal);
  document.getElementById('conf-shipping').textContent = shipping === 0 ? 'Gratis' : fmt(shipping);
  document.getElementById('conf-total').textContent    = fmt(subtotal + shipping);
}

function submitOrder() {
  const btn = document.getElementById('btn-confirm');
  btn.disabled = true;
  btn.textContent = 'Procesando...';

  const data = {
    delivery: currentDelivery,
    payment:  currentPayment,
    shipping: currentDelivery === 'home' ? {
      name:     document.getElementById('sh-name').value,
      phone:    document.getElementById('sh-phone').value,
      email:    document.getElementById('sh-email').value,
      address:  document.getElementById('sh-address').value,
      city:     document.getElementById('sh-city').value,
      province: document.getElementById('sh-province').value,
      notes:    document.getElementById('sh-notes').value,
    } : {
      name:  document.getElementById('pk-name').value,
      phone: document.getElementById('pk-phone').value,
    }
  };

  fetch('funcs/process_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(r => r.json())
  .then(res => {
    if (!res.success) {
      showToast(`❌ ${res.error || 'Error al procesar el pedido'}`, 'error');
      btn.disabled = false;
      btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/></svg> Confirmar pedido';
      return;
    }
    document.getElementById('order-id-display').textContent = res.order_id;
    [1,2,3].forEach(n => { document.getElementById(`checkout-step-${n}`).style.display = 'none'; document.getElementById(`step-ind-${n}`).classList.add('done'); });
    document.getElementById('checkout-success').style.display = 'flex';
    // Limpiar carrito local
    cartItems = [];
    updateCartUI();
  })
  .catch(() => {
    showToast('❌ Error de conexión. Intenta de nuevo.', 'error');
    btn.disabled = false;
  });
}

// ══════════════════════════════════════════════
// SELECTORES
// ══════════════════════════════════════════════
function selectDelivery(type) {
  currentDelivery = type;
  document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('active'));
  document.getElementById(`opt-${type}`).querySelector('.delivery-card').classList.add('active');
  document.getElementById('form-home').style.display   = type === 'home'   ? 'grid'  : 'none';
  document.getElementById('form-pickup').style.display = type === 'pickup' ? 'block' : 'none';
}

function selectPayment(type) {
  currentPayment = type;
  document.querySelectorAll('.payment-card-item').forEach(c => c.classList.remove('active'));
  document.getElementById(`pay-${type}-opt`).querySelector('.payment-card-item').classList.add('active');
  document.getElementById('form-card').style.display     = type === 'card'     ? 'block' : 'none';
  document.getElementById('form-transfer').style.display = type === 'transfer' ? 'block' : 'none';
  document.getElementById('form-cash').style.display     = type === 'cash'     ? 'block' : 'none';
}

function fmtCard(input) {
  let v = input.value.replace(/\D/g,'').substring(0,16);
  input.value = v.replace(/(.{4})/g,'$1 ').trim();
  const b = document.getElementById('card-brand');
  b.textContent = v.startsWith('4') ? 'VISA' : v.startsWith('5') ? 'MC' : v.startsWith('3') ? 'AMEX' : '';
}

function fmtExp(input) {
  let v = input.value.replace(/\D/g,'').substring(0,4);
  if (v.length >= 2) v = v.substring(0,2) + ' / ' + v.substring(2);
  input.value = v;
}

// ══════════════════════════════════════════════
// PRODUCTS LOADING
// ══════════════════════════════════════════════
document.addEventListener("DOMContentLoaded", function () {
  // Sincronizar badge con servidor al cargar
  fetch('funcs/cart_functions.php?action=get')
    .then(r => r.json())
    .then(res => { cartItems = res.cart; updateCartUI(); });

  let offset = 0;
  const LIMIT = 6;
  const container   = document.getElementById("products-container");
  const loadMoreBtn = document.getElementById("load-more");
  const searchInput = document.getElementById("search-input");
  let currentSearch = "";
  let isLoading     = false;

  // Nav activo en scroll
  const sections = document.querySelectorAll('section');
  const navLinks = document.querySelectorAll('.nav-link');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => { if (window.pageYOffset >= s.offsetTop - 150) current = s.getAttribute('id'); });
    navLinks.forEach(l => { l.classList.remove('active'); if (l.getAttribute('href') === `#${current}`) l.classList.add('active'); });
  });

  function loadProducts(reset = false) {
    if (isLoading) return;
    isLoading = true;
    if (reset) { container.innerHTML = '<div class="loading-spinner">Cargando...</div>'; offset = 0; }
    fetch(`funcs/get_products.php?offset=${offset}&search=${encodeURIComponent(currentSearch)}`)
      .then(r => r.text())
      .then(html => {
        if (reset) container.innerHTML = '';
        if (html && html.trim() && !html.includes('no-results')) {
          container.insertAdjacentHTML('beforeend', html);
          offset += LIMIT;
          loadMoreBtn.style.display = 'inline-block';
        } else {
          loadMoreBtn.style.display = 'none';
          if (reset) container.innerHTML = "<p class='no-results'>No se encontraron resultados.</p>";
        }
      })
      .catch(() => { container.innerHTML = "<p class='no-results'>Error al cargar productos.</p>"; })
      .finally(() => { isLoading = false; });
  }

  loadProducts();

  let st;
  searchInput.addEventListener("input", e => {
    clearTimeout(st);
    st = setTimeout(() => { currentSearch = e.target.value; loadProducts(true); }, 300);
  });

  loadMoreBtn.addEventListener("click", () => loadProducts());

  // User dropdown
  const userMenuBtn = document.getElementById('user-menu-button');
  const userDropdown = document.getElementById('user-dropdown');
  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => { e.stopPropagation(); userDropdown.classList.toggle('show'); });
    document.addEventListener('click', e => { if (userDropdown && !userMenuBtn.contains(e.target)) userDropdown.classList.remove('show'); });
  }

  // Welcome modal
  const closeModal = document.getElementById('close-modal');
  const welcomeModal = document.getElementById('welcome-modal');
  if (closeModal && welcomeModal) {
    closeModal.addEventListener('click', () => {
      welcomeModal.classList.add('fade-out');
      setTimeout(() => welcomeModal.style.display = 'none', 300);
    });
  }
});
</script>
</body>
</html>