document.addEventListener("DOMContentLoaded", function () {
  const botonCirculo = document.getElementById("chat-circle");
  const ventanaChat = document.getElementById("chat-box");
  const botonEnviar = document.getElementById("send-btn");
  const inputChat = document.getElementById("chat-input");
  const contenidoChat = document.getElementById("chat-content");

  // 1. Función para abrir/cerrar el chat
  if (botonCirculo && ventanaChat) {
    botonCirculo.onclick = function () {
      ventanaChat.style.display = (ventanaChat.style.display === "none" || ventanaChat.style.display === "") ? "flex" : "none";
    };
  }

  // 2. Definimos la función de enviar por separado para que el botón y el Enter la usen
  async function enviarMensaje() {
    const mensaje = inputChat.value.trim();
    if (mensaje === "") return;

    // Mensaje del usuario
    contenidoChat.innerHTML += `<div class="msg msg-user" style="text-align:right; background:#007bff; color:white; padding:8px; border-radius:10px; margin-bottom:10px;">${mensaje}</div>`;
    inputChat.value = "";
    contenidoChat.scrollTop = contenidoChat.scrollHeight;

    try {
      const respuesta = await fetch("IA/api_ia.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ mensaje: mensaje }),
      });

      const data = await respuesta.json();

      // Convertir [Producto] en botón si la IA lo manda
      let textoIA = data.respuesta.replace(/\[(.*?)\]/g, `<br><a href="buscar.php?producto=$1" style="display:inline-block; margin-top:5px; padding:5px 10px; background:#28a745; color:white; border-radius:5px; text-decoration:none; font-weight:bold;">🔎 Buscar $1</a>`);

      contenidoChat.innerHTML += `<div class="msg msg-ia" style="background:#e9ecef; padding:8px; border-radius:10px; margin-bottom:10px;">${textoIA}</div>`;
      contenidoChat.scrollTop = contenidoChat.scrollHeight;

    } catch (error) {
      console.error("Error:", error);
      contenidoChat.innerHTML += `<div style="color:red">Error de conexión. Revisa el PHP.</div>`;
    }
  }

  // 3. Asignar la función al botón de clic
  if (botonEnviar) {
    botonEnviar.onclick = enviarMensaje;
  }

  // 4. Asignar la función a la tecla Enter
  if (inputChat) {
    inputChat.addEventListener("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault(); // Evita recargar página
        enviarMensaje();
      }
    });
  }
}); // <--- Aquí cerramos correctamente el DOMContentLoaded