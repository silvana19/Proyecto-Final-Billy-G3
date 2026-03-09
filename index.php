<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Farmacia</title>
  <link rel="shortcut icon" href="assets/img/logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="stylea.css" />
  <link rel="stylesheet" href="style.css" />

</head>

<body>
  <!-- Inicio Sesión PHP -->
  <?php session_start(); ?>

  <header>
    <img class="logo" src="assets/img/logo.webp" alt="Logo Farmacia" />

    <nav>
      <ul>
        <li><a href="#S1">Inicio</a></li>
        <li><a href="#S2">Desarrolladores</a></li>
        <li><a href="#S3">Stock</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="user-info">
            <span>Hola, <?php echo $_SESSION['nombre']; ?></span>
          </li>
          <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
            <li><a href="admin_dashboard.php">Panel Admin</a></li>
          <?php endif; ?>
          <li class="btn-logout"><a href="funcs/logout.php">Cerrar Sesión</a></li>
        <?php else: ?>
          <li><a href="register.php">Registrarse</a></li>
          <li class="btn-login"><a href="login.php">Iniciar Sesión</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>
  <section id="S1">
    <div class="container">
      <div class="info">
        <h1>La mejor soluci&oacute;n a tus problemas</h1>

        <a class="cl" href="#">Descubre LOVE REWARDS</a>
      </div>

      <div class="img">
        <img src="assets/img/famacia.jpg" alt="" />
      </div>
    </div>
  </section>


  </section>

  <section id="S2">
    <div class="carousel">
      <div class="containerp">
        <div class="cardsp">

          <div class="cardp">
            <img src="assets/img/Silvana.jpeg" alt="Persona 1" class="silvana" />
            <div class="infop">
              <h3>Silvana</h3>
              <p>Desarrolladora</p>
            </div>
          </div>

          <div class="cardp">
            <img class="joshua" src="assets/img/joshua.jpeg" alt="Persona 2" />
            <div class="infop">
              <h3>Joshual</h3>
              <p>Desarrollador</p>
            </div>
          </div>

          <div class="cardp">
            <img src="assets/img/holfrandy.jpg" alt="Persona 3" />
            <div class="infop">
              <h3>Holfrandy</h3>
              <p>Rol en la empresa</p>
            </div>
          </div>

          <div class="cardp">
            <img src="assets/img/estelvin.webp" alt="Persona 4" />
            <div class="infop">
              <h3>Estelvin</h3>
              <p>Rol en la empresa</p>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>




  <section id="S3">
    <div class="containerc">
      <div class="cards">
        <?php
        require_once "config/db.php";
        // Check connection
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }
        ?>

        <div style="text-align: center; margin-bottom: 30px;">
          <h2 style="color: #0d2919; font-size: 2.5rem; margin-bottom: 10px;">Medicamentos</h2>
          <input type="text" id="search-input" placeholder="Buscar medicamento..."
            style="padding: 10px 20px; width: 300px; border-radius: 20px; border: 1px solid #ccc; outline: none;">
        </div>

        <div class="cards" id="products-container">
          <!-- Productos se cargarán aquí -->
        </div>

        <div style="text-align: center; margin-top: 30px;">
          <button id="load-more"
            style="padding: 10px 30px; background: #00754a; color: white; border: none; border-radius: 20px; cursor: pointer; display: none;">Ver
            más</button>
        </div>

      </div>
  </section>

  <!-- Script para búsqueda y paginación -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      let offset = 0;
      const container = document.getElementById("products-container");
      const loadMoreBtn = document.getElementById("load-more");
      const searchInput = document.getElementById("search-input");
      let currentSearch = "";

      function loadProducts(reset = false) {
        if (reset) {
          container.innerHTML = "";
          offset = 0;
        }

        fetch(`funcs/get_products.php?offset=${offset}&search=${encodeURIComponent(currentSearch)}`)
          .then(response => response.text())
          .then(html => {
            if (html.trim() !== "") {
              container.insertAdjacentHTML('beforeend', html);
              offset += 3;
              loadMoreBtn.style.display = "inline-block";
            } else {
              loadMoreBtn.style.display = "none";
              if (reset) container.innerHTML = "<p style='width:100%; text-align:center; color: #666;'>No se encontraron resultados.</p>";
            }
          });
      }

      // Carga inicial
      loadProducts();

      // Evento Buscador
      searchInput.addEventListener("input", function (e) {
        currentSearch = e.target.value;
        loadProducts(true); // Resetear y buscar
      });

      // Evento Ver Más
      loadMoreBtn.addEventListener("click", function () {
        loadProducts();
      });
    });
  </script>


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
        <div class="social">
          <a href="#">Facebook</a>
          <a href="#">Instagram</a>
          <a href="#">YouTube</a>
          <a href="#">TikTok</a>
        </div>
      </div>

    </div>

    <div class="footer-bottom">
      <p>© 2025 Farmacia del amor — Inspirado en Starbucks</p>
    </div>
  </footer>
  <script src="/Proyecto-Final-Billy-G3/script.js"></script>
  <script src="/Proyecto-Final-Billy-G3/libs/bootstrap-5.3.8-dist/bootstrap.min.js"></script>
</body>

</html>