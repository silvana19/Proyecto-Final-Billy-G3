<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farmacia del Amor</title>
  <link rel="shortcut icon" href="assets/img/logo.png" type="image/x-icon">
  <link rel="stylesheet" href="styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <?php session_start(); ?>

  <!-- Notificaciones Toast -->
  <div id="toast-notification" class="toast-notification">
    <div class="toast-content">
      <i class="toast-icon"></i>
      <span class="toast-message"></span>
    </div>
  </div>

  <header>
    <div class="header-container">
      <div class="logo-container">
        <img class="logo" src="assets/img/logo.png" alt="Logo Farmacia" />
        <span class="brand-name">Farmacia del Amor</span>
      </div>

      <nav>
        <ul>
          <li><a href="#S1" class="nav-link">Inicio</a></li>
          <li><a href="#S2" class="nav-link">Historia</a></li>
          <li><a href="#S3" class="nav-link">Medicamentos</a></li>
          
          <!-- Carrito de compras -->
          <li class="cart-container">
            <button class="cart-button" onclick="toggleCart()">
              <i class="fas fa-shopping-cart"></i>
              <span id="cart-count" class="cart-count">0</span>
            </button>
          </li>

          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="user-info">
              <span>Hola, <?php echo $_SESSION['nombre']; ?></span>
            </li>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
              <li><a href="admin_dashboard.php" class="nav-link admin-link">Panel Admin</a></li>
            <?php endif; ?>
            <li class="btn-logout"><a href="funcs/logout.php">Cerrar Sesión</a></li>
          <?php else: ?>
            <li><a href="register.php" class="nav-link">Registrarse</a></li>
            <li class="btn-login"><a href="login.php">Iniciar Sesión</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <!-- Sidebar del Carrito -->
  <div id="cart-sidebar" class="cart-sidebar">
    <div class="cart-header">
      <h3>Tu Carrito</h3>
      <button class="close-cart" onclick="toggleCart()">&times;</button>
    </div>
    <div class="cart-items" id="cart-items">
      <p style="text-align: center; color: #666; padding: 20px;">Cargando carrito...</p>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span>Total:</span>
        <span id="cart-total">$0.00</span>
      </div>
      <button class="checkout-btn" onclick="proceedToCheckout()">Proceder al Pago</button>
    </div>
  </div>

  <!-- Modal de Checkout -->
  <div id="checkout-modal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Finalizar Compra</h2>
        <button class="close-modal" onclick="closeCheckout()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="checkout-form" onsubmit="event.preventDefault(); processOrder()">
          <!-- Resumen del pedido -->
          <div class="order-summary">
            <h3>Resumen del Pedido</h3>
            <div id="order-items"></div>
            <div class="summary-total">
              <span>Total a pagar:</span>
              <span id="order-total">$0.00</span>
            </div>
          </div>

          <!-- Información de contacto -->
          <div class="form-section">
            <h3>Información de Contacto</h3>
            <div class="form-group">
              <input type="text" id="nombre" name="nombre" placeholder="Nombre completo" required 
                     value="<?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : ''; ?>">
            </div>
            <div class="form-group">
              <input type="email" id="email" name="email" placeholder="Correo electrónico" required
                     value="<?php echo isset($_SESSION['correo']) ? $_SESSION['correo'] : ''; ?>">
            </div>
            <div class="form-group">
              <input type="tel" id="telefono" name="telefono" placeholder="Teléfono" required>
            </div>
          </div>

          <!-- Dirección de envío -->
          <div class="form-section">
            <h3>Dirección de Envío</h3>
            <div class="form-group">
              <input type="text" id="direccion" name="direccion" placeholder="Dirección completa" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <input type="text" id="ciudad" name="ciudad" placeholder="Ciudad" required>
              </div>
              <div class="form-group">
                <input type="text" id="codigo_postal" name="codigo_postal" placeholder="Código Postal" required>
              </div>
            </div>
          </div>

          <!-- Método de envío -->
          <div class="form-section">
            <h3>Método de Envío</h3>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="envio" value="estandar" checked>
                <div class="radio-content">
                  <span class="radio-title">Envío Estándar</span>
                  <span class="radio-desc">Entrega en 3-5 días hábiles</span>
                  <span class="radio-price">$150.00</span>
                </div>
              </label>
              <label class="radio-option">
                <input type="radio" name="envio" value="express">
                <div class="radio-content">
                  <span class="radio-title">Envío Express</span>
                  <span class="radio-desc">Entrega en 24-48 horas</span>
                  <span class="radio-price">$250.00</span>
                </div>
              </label>
              <label class="radio-option">
                <input type="radio" name="envio" value="recojo">
                <div class="radio-content">
                  <span class="radio-title">Recojo en Tienda</span>
                  <span class="radio-desc">Recoge en nuestra farmacia</span>
                  <span class="radio-price">Gratis</span>
                </div>
              </label>
            </div>
          </div>

          <!-- Método de pago -->
          <div class="form-section">
            <h3>Método de Pago</h3>
            <div class="payment-methods">
              <label class="payment-option">
                <input type="radio" name="pago" value="tarjeta" checked>
                <div class="payment-content">
                  <i class="fas fa-credit-card"></i>
                  <span>Tarjeta de Crédito/Débito</span>
                </div>
              </label>
              <div class="card-details" id="card-details">
                <div class="form-group">
                  <input type="text" placeholder="Número de tarjeta" id="card-number">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <input type="text" placeholder="MM/AA" id="card-expiry">
                  </div>
                  <div class="form-group">
                    <input type="text" placeholder="CVV" id="card-cvv">
                  </div>
                </div>
              </div>

              <label class="payment-option">
                <input type="radio" name="pago" value="transferencia">
                <div class="payment-content">
                  <i class="fas fa-university"></i>
                  <span>Transferencia Bancaria</span>
                </div>
              </label>
              <div class="transfer-details" id="transfer-details" style="display: none;">
                <p>Banco: Banco de la Nación</p>
                <p>Cuenta: 123-456789-0-12</p>
                <p>CCI: 123456789012345678</p>
                <p>Titular: Farmacia del Amor S.A.C.</p>
              </div>

              <label class="payment-option">
                <input type="radio" name="pago" value="efectivo">
                <div class="payment-content">
                  <i class="fas fa-money-bill-wave"></i>
                  <span>Efectivo (Contra entrega)</span>
                </div>
              </label>
            </div>
          </div>

          <button type="submit" class="submit-order-btn">Confirmar Compra</button>
        </form>
      </div>
    </div>
  </div>

  <section id="S1">
    <div class="container">
      <div class="info">
        <h1>La mejor solución para tu salud y bienestar</h1>
        <p class="info-subtitle">Más de 25 años cuidando de ti y tu familia</p>
        <a class="cta-button" href="#" onclick="showNotification('¡Bienvenido a LOVE REWARDS!', 'success')">Descubre LOVE REWARDS</a>
      </div>
      <div class="img">
        <img src="assets/img/famacia.jpg" alt="Farmacia" />
      </div>
    </div>
  </section>

  <section id="S2">
    <div class="section-header">
      <h2 class="section-title">Nuestra Esencia</h2>
      <div class="section-divider"></div>
      <p class="section-subtitle">Conoce los pilares que nos definen como farmacia de confianza</p>
    </div>

    <div class="empresa-container">
      <!-- Tarjeta de Historia -->
      <div class="card-empresa">
        <div class="card-icon">
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#00a86b" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h3>Historia</h3>
        <div class="card-content">
          <p>Fundada en 1995, nuestra farmacia nació con el sueño de brindar salud y bienestar a la comunidad. Lo que comenzó como un pequeño local familiar, hoy es un referente de confianza y calidez.</p>
        </div>
        <div class="card-footer">
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
        </div>
      </div>

      <!-- Tarjeta de Misión -->
      <div class="card-empresa">
        <div class="card-icon">
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#00a86b" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <h3>Misión</h3>
        <div class="card-content">
          <p>Mejorar la calidad de vida de nuestros clientes ofreciendo productos farmacéuticos de alta calidad y un servicio personalizado, con un equipo comprometido y en constante actualización.</p>
        </div>
        <div class="card-footer">
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
        </div>
      </div>

      <!-- Tarjeta de Visión -->
      <div class="card-empresa">
        <div class="card-icon">
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="#00a86b" stroke-width="2"/>
            <path d="M12 5C7.5 5 3.5 8 2 12C3.5 16 7.5 19 12 19C16.5 19 20.5 16 22 12C20.5 8 16.5 5 12 5Z" stroke="#00a86b" stroke-width="2"/>
          </svg>
        </div>
        <h3>Visión</h3>
        <div class="card-content">
          <p>Ser la farmacia de referencia en la región, reconocida por nuestra cercanía, innovación y responsabilidad social, expandiendo nuestros servicios para el cuidado integral de la salud.</p>
        </div>
        <div class="card-footer">
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
        </div>
      </div>

      <!-- Tarjeta de Valores -->
      <div class="card-empresa">
        <div class="card-icon">
          <svg class="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 21C12 21 20 16.5 20 10C20 6 16 3 12 3C8 3 4 6 4 10C4 16.5 12 21 12 21Z" stroke="#00a86b" stroke-width="2"/>
            <path d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z" stroke="#00a86b" stroke-width="2"/>
          </svg>
        </div>
        <h3>Valores</h3>
        <div class="card-content">
          <p>Ética profesional, empatía, honestidad y compromiso con la salud son los pilares que guían nuestro día a día. La transparencia y el respeto nos definen en cada interacción.</p>
        </div>
        <div class="card-footer">
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
          <span class="decor-dot"></span>
        </div>
      </div>
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
        <span class="search-icon">🔍</span>
      </div>

      <div class="products-grid" id="products-container">
        <!-- Productos se cargarán aquí -->
      </div>

      <div class="load-more-container">
        <button id="load-more" class="load-more-btn" style="display:none;">Ver más productos</button>
      </div>
    </div>
  </section>

  <script>
// Sistema de notificaciones
function showNotification(message, type = 'info') {
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'toast-notification';
        document.body.appendChild(toast);
    }
    
    let bgColor = '#2196F3';
    let icon = 'fa-info-circle';
    
    if (type === 'success') {
        bgColor = '#4CAF50';
        icon = 'fa-check-circle';
    } else if (type === 'error') {
        bgColor = '#f44336';
        icon = 'fa-exclamation-circle';
    } else if (type === 'warning') {
        bgColor = '#ff9800';
        icon = 'fa-exclamation-triangle';
    }
    
    toast.style.background = bgColor;
    toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

document.addEventListener("DOMContentLoaded", function () {
    let offset = 0;
    const LIMIT = 3;
    const container = document.getElementById("products-container");
    const loadMoreBtn = document.getElementById("load-more");
    const searchInput = document.getElementById("search-input");
    let currentSearch = "";
    let isLoading = false;
    let hasMore = true;

    // Active nav link on scroll
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    function loadProducts(reset = false) {
        if (isLoading) return;
        
        isLoading = true;

        if (reset) {
            container.innerHTML = "";
            offset = 0;
            hasMore = true;
        }

        if (!hasMore && !reset) {
            isLoading = false;
            return;
        }

        loadMoreBtn.textContent = "Cargando...";
        loadMoreBtn.style.display = "inline-block";

        fetch(`funcs/get_products.php?offset=${offset}&search=${encodeURIComponent(currentSearch)}`)
            .then(response => response.text())
            .then(html => {
                if (html.trim() !== "" && !html.includes('No se encontraron productos')) {
                    const hasMoreIndicator = html.includes('<!-- HAS_MORE -->');
                    const cleanHtml = html.replace('<!-- HAS_MORE -->', '');
                    
                    container.insertAdjacentHTML('beforeend', cleanHtml);
                    
                    if (hasMoreIndicator) {
                        offset += LIMIT;
                        loadMoreBtn.style.display = "inline-block";
                        loadMoreBtn.textContent = "Ver más productos";
                        hasMore = true;
                    } else {
                        loadMoreBtn.style.display = "none";
                        hasMore = false;
                    }
                } else {
                    if (reset) {
                        container.innerHTML = '<p class="no-results">No se encontraron resultados.</p>';
                    }
                    loadMoreBtn.style.display = "none";
                    hasMore = false;
                }
            })
            .catch(err => {
                console.error("Error:", err);
            })
            .finally(() => {
                isLoading = false;
            });
    }

    // Carga inicial
    loadProducts();

    // Buscador con debounce
    let searchTimeout;
    searchInput.addEventListener("input", function (e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = e.target.value;
            loadProducts(true);
        }, 300);
    });

    // Ver Más
    loadMoreBtn.addEventListener("click", function () {
        if (!isLoading && hasMore) {
            loadProducts();
        }
    });

    // Cargar carrito al inicio
    loadCart();

    // Mostrar/ocultar detalles de pago
    const paymentRadios = document.querySelectorAll('input[name="pago"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const cardDetails = document.getElementById('card-details');
            const transferDetails = document.getElementById('transfer-details');
            
            if (cardDetails) cardDetails.style.display = 'none';
            if (transferDetails) transferDetails.style.display = 'none';
            
            if (this.value === 'tarjeta' && cardDetails) {
                cardDetails.style.display = 'block';
            } else if (this.value === 'transferencia' && transferDetails) {
                transferDetails.style.display = 'block';
            }
        });
    });
});

// Función para cambiar cantidad
function cambiarCantidad(productoId, cambio) {
    const input = document.getElementById(`cantidad_${productoId}`);
    if (!input) return;
    
    let nuevaCantidad = parseInt(input.value) + cambio;
    const max = parseInt(input.max);
    
    if (nuevaCantidad < 1) nuevaCantidad = 1;
    if (nuevaCantidad > max) nuevaCantidad = max;
    
    input.value = nuevaCantidad;
}

// Función para agregar al carrito
function agregarAlCarrito(productoId, nombre, precio, imagen) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        showNotification('Debes iniciar sesión para comprar', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1500);
        return;
    <?php endif; ?>
    
    const cantidad = parseInt(document.getElementById(`cantidad_${productoId}`).value);
    
    showNotification('Agregando al carrito...', 'info');
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('producto_id', productoId);
    formData.append('nombre', nombre);
    formData.append('precio', precio);
    formData.append('imagen', imagen);
    formData.append('cantidad', cantidad);
    
    fetch('funcs/cart_functions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('cart-count').textContent = data.cart_count;
        } else {
            showNotification(data.message || 'Error al agregar al carrito', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Función para cargar el carrito
function loadCart() {
    fetch('funcs/cart_functions.php?action=get')
    .then(response => response.json())
    .then(data => {
        const cartCountSpan = document.getElementById('cart-count');
        if (cartCountSpan) {
            cartCountSpan.textContent = data.count;
        }
        renderCart(data);
    })
    .catch(error => {
        console.error('Error loading cart:', error);
    });
}

// Función para alternar carrito
function toggleCart() {
    const sidebar = document.getElementById('cart-sidebar');
    sidebar.classList.toggle('open');
    if (sidebar.classList.contains('open')) {
        loadCart();
    }
}

// Función para renderizar carrito
function renderCart(data) {
    const cartItems = document.getElementById('cart-items');
    const cartTotalSpan = document.getElementById('cart-total');
    
    if (!cartItems) return;
    
    if (!data.items || data.items.length === 0) {
        cartItems.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Tu carrito está vacío</p>';
        cartTotalSpan.textContent = '$0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    data.items.forEach(item => {
        total += item.subtotal;
        
        html += `
            <div class="cart-item" data-id="${item.id}">
                <img src="${item.imagen}" alt="${item.nombre}" class="cart-item-img" 
                     onerror="this.src='assets/img/placeholder.jpg'">
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.nombre}</div>
                    <div class="cart-item-price">$${item.precio.toFixed(2)} c/u</div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.cantidad - 1})">-</button>
                        <span>${item.cantidad}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.cantidad + 1})">+</button>
                        <button class="remove-item" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="cart-item-subtotal">Subtotal: $${item.subtotal.toFixed(2)}</div>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    cartTotalSpan.textContent = '$' + total.toFixed(2);
}

// Función para actualizar cantidad
function updateQuantity(productoId, cantidad) {
    if (cantidad < 1) {
        removeFromCart(productoId);
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('producto_id', productoId);
    formData.append('cantidad', cantidad);
    
    fetch('funcs/cart_functions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        renderCart(data);
        document.getElementById('cart-count').textContent = data.count;
        showNotification('Cantidad actualizada', 'success');
    });
}

// Función para eliminar del carrito
function removeFromCart(productoId) {
    if (!confirm('¿Eliminar producto del carrito?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('producto_id', productoId);
    
    fetch('funcs/cart_functions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        renderCart(data);
        document.getElementById('cart-count').textContent = data.count;
        showNotification('Producto eliminado', 'success');
    });
}

// Función para ir al checkout
function proceedToCheckout() {
    <?php if (!isset($_SESSION['user_id'])): ?>
        showNotification('Debes iniciar sesión para continuar', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1500);
        return;
    <?php endif; ?>
    
    fetch('funcs/cart_functions.php?action=get')
    .then(response => response.json())
    .then(data => {
        if (data.count === 0) {
            showNotification('Tu carrito está vacío', 'warning');
            return;
        }
        
        const orderItems = document.getElementById('order-items');
        const orderTotal = document.getElementById('order-total');
        
        let html = '';
        let total = 0;
        
        data.items.forEach(item => {
            total += item.subtotal;
            html += `
                <div class="order-item">
                    <span>${item.nombre} x${item.cantidad}</span>
                    <span>$${item.subtotal.toFixed(2)}</span>
                </div>
            `;
        });
        
        orderItems.innerHTML = html;
        orderTotal.textContent = '$' + total.toFixed(2);
        
        document.getElementById('checkout-modal').classList.add('show');
    });
}

// Función para cerrar checkout
function closeCheckout() {
    document.getElementById('checkout-modal').classList.remove('show');
}

// Función para procesar orden
function processOrder() {
    const form = document.getElementById('checkout-form');
    
    // Validar campos requeridos
    const required = ['nombre', 'email', 'telefono', 'direccion', 'ciudad', 'codigo_postal'];
    for (let field of required) {
        const input = document.getElementById(field);
        if (!input || !input.value.trim()) {
            showNotification('Por favor complete todos los campos', 'warning');
            if (input) input.focus();
            return;
        }
    }
    
    const envio = document.querySelector('input[name="envio"]:checked')?.value;
    const pago = document.querySelector('input[name="pago"]:checked')?.value;
    
    if (!envio || !pago) {
        showNotification('Seleccione método de envío y pago', 'warning');
        return;
    }
    
    fetch('funcs/cart_functions.php?action=get')
    .then(response => response.json())
    .then(cartData => {
        const orderData = {
            items: cartData.items,
            nombre: document.getElementById('nombre').value,
            email: document.getElementById('email').value,
            telefono: document.getElementById('telefono').value,
            direccion: document.getElementById('direccion').value,
            ciudad: document.getElementById('ciudad').value,
            codigo_postal: document.getElementById('codigo_postal').value,
            envio: envio,
            pago: pago
        };
        
        showNotification('Procesando tu pedido...', 'info');
        
        fetch('funcs/process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('¡Pedido realizado con éxito!', 'success');
                closeCheckout();
                document.getElementById('cart-sidebar').classList.remove('open');
                document.getElementById('cart-count').textContent = '0';
                form.reset();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al procesar el pedido', 'error');
        });
    });
}

// Función para cerrar sesión
function logout() {
    if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
        showNotification('Cerrando sesión...', 'info');
        setTimeout(() => {
            window.location.href = 'funcs/logout.php';
        }, 1500);
    }
}
</script>
  <script src="script.js"></script>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-column">
        <h3>Sobre Nosotros</h3>
        <a href="#">Nuestra Historia</a>
        <a href="#">Responsabilidad Social</a>
        <a href="#">Trabaja con Nosotros</a>
        <a href="#">Prensa</a>
      </div>
      <div class="footer-column">
        <h3>Servicio al Cliente</h3>
        <a href="#">Contáctanos</a>
        <a href="#">Soporte</a>
        <a href="#">Preguntas Frecuentes</a>
        <a href="#">Envíos</a>
      </div>
      <div class="footer-column">
        <h3>Tiendas</h3>
        <a href="#">Localizador</a>
        <a href="#">Nuevos Productos</a>
        <a href="#">Promociones</a>
      </div>
      <div class="footer-column">
        <h3>Conéctate</h3>
        <div class="social-links">
          <a href="#" class="social-link">Facebook</a>
          <a href="#" class="social-link">Instagram</a>
          <a href="#" class="social-link">YouTube</a>
          <a href="#" class="social-link">TikTok</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 Farmacia del Amor — Cuidando de tu salud con amor y dedicación</p>
    </div>
  </footer>

</body>
</html>