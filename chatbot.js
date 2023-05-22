// Obtiene los elementos del DOM
const chatHistory = document.getElementById("chat-history");
const userInput = document.getElementById("user-input");
const sendBtn = document.getElementById("send-btn");

// Añade un event listener para detectar la tecla Enter en el input del usuario
userInput.addEventListener("keypress", async (e) => {
  const keycode = e.keyCode ? e.keyCode : e.which;
  if (keycode === 13) {
    e.preventDefault(); // Evita el comportamiento predeterminado del Enter
    await sendMessage();
  }
});

// Añade un event listener para el botón de enviar
sendBtn.addEventListener("click", async () => {
  await sendMessage();
});

// Función asíncrona para enviar el mensaje del usuario y obtener la respuesta del chatbot
async function sendMessage() {
  const userMessage = userInput.value.trim();
  if (userMessage) {
    addMessageToChat("user", userMessage);
    userInput.value = "";

    // Realiza una solicitud POST al servidor con el mensaje del usuario
    const response = await fetch("chatbot.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: userMessage }),
    });

    // Si la respuesta es exitosa, muestra el mensaje del chatbot en el historial de chat
    if (response.ok) {
      const data = await response.json();
      handleResponse(data.response);
    } else {
      console.error("Error en la comunicación con el chatbot");
    }
  }
}

// Función para manejar la respuesta del chatbot y convertir enlaces en texto clickeable
function handleResponse(response) {
  const formattedResponse = formatResponse(response);
  addMessageToChat("bot", formattedResponse);
}

// Función para agregar mensajes al historial de chat
function addMessageToChat(role, message) {
    const messageContainer = document.createElement("div");
    messageContainer.classList.add("message-container", role);
  
    const profileImage = document.createElement("div");
    profileImage.classList.add("profile-image", role);
  
    const messageElement = document.createElement("div");
    messageElement.classList.add("message", role);
  
    messageElement.innerHTML = message; // Se mantiene el mensaje sin formatear
  
    messageContainer.appendChild(profileImage);
    messageContainer.appendChild(messageElement);
    chatHistory.appendChild(messageContainer);
    chatHistory.scrollTop = chatHistory.scrollHeight;
  
    // Añade la clase 'visible' después de un breve retraso para permitir animaciones CSS
    setTimeout(() => {
      messageElement.classList.add("visible");
    }, 100);
  }
  

// Función para detectar enlaces en el texto y convertirlos en enlaces clickeables
function formatLinks(text) {
    const linkRegex = /((?:https?:\/\/)?(?:www\.)?[\w.-]+\.[a-zA-Z]{2,}(?:\/[^\s]*)?)/g;
  
    const formattedText = text.replace(linkRegex, (match) => {
      const friendlyLinkText = getFriendlyLinkText(match);
      const linkElement = document.createElement("a");
      linkElement.href = match;
      linkElement.target = "_blank";
      linkElement.textContent = friendlyLinkText;
      return linkElement.outerHTML;
    });
  
    return formattedText;
  }
  
  function getFriendlyLinkText(link) {
    const url = new URL(link);
    let friendlyText = url.hostname;
  
    // Personaliza el texto amigable para enlaces conocidos
    if (friendlyText.includes('rokys.com')) {
      friendlyText = 'Roky\'s';
    }
  
    // Escapa el texto HTML para evitar problemas al hacer clic en el enlace
    friendlyText = escapeHtml(friendlyText);
  
    return friendlyText;
  }
  
  // Función para escapar caracteres especiales de HTML
  function escapeHtml(text) {
    const element = document.createElement('div');
    element.textContent = text;
    return element.innerHTML;
  }
  
  

// Función para convertir saltos de línea en etiquetas <br> y formatear enlaces
function formatResponse(response) {
  // Convierte los saltos de línea en etiquetas <br>
  const formattedResponse = response.replace(/\n/g, "<br>");

  // Luego, formatea los enlaces
  return formatLinks(formattedResponse);
}
