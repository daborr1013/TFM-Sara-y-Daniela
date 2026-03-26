/**
 * ====================================
 * JAVASCRIPT - CHATBOT LITTO
 * ====================================
 * 
 * Funcionalidad:
 * - Gestiona la interacción del chat del asistente virtual "Litto"
 * - Maneja el envío de mensajes desde el formulario
 * - Auto-scroll del chat hacia nuevos mensajes
 * - Conecta con la API chatbot-api.php para obtener respuestas de la base de datos
 * 
 * Accesibilidad:
 * - El formulario es completamente navegable por teclado
 * - Los mensajes se añaden al DOM preservando semántica
 * - Para lectores de pantalla, los mensajes se anuncian dinámicamente
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatBox = document.getElementById('chatBox');

    // Validar que elementos existan antes de usar
    if (!chatForm || !messageInput || !chatBox) {
        console.warn('⚠️ Elementos del chat no encontrados');
        return;
    }

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita que la página se recargue

        const messageText = messageInput.value.trim();
        
        // Validar que el mensaje no esté vacío
        if (messageText === "") {
            messageInput.focus();
            return;
        }

        // 1. Crear el nuevo mensaje del usuario
        const newMessage = document.createElement('div');
        newMessage.className = 'message user';
        newMessage.setAttribute('role', 'article');
        const p = document.createElement('p');
            p.textContent = messageText;
            newMessage.appendChild(p);
        
        // 2. Añadir el mensaje a la caja de chat
        chatBox.appendChild(newMessage);

        // 3. Limpiar el input y devolver focus
        messageInput.value = '';
        messageInput.focus();

        // 4. Scroll automático hacia abajo
        chatBox.scrollTop = chatBox.scrollHeight;

        // Mostrar indicador de "Escribiendo..."
        const typing = document.createElement('div');
        typing.className = 'message bot';
        typing.id = 'typing';
        typing.innerHTML = `<p>Escribiendo...</p>`;
        chatBox.appendChild(typing);
        chatBox.scrollTop = chatBox.scrollHeight;

        // Enviar mensaje a la API y obtener respuesta de la base de datos
        fetch('../chatbot-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: messageText })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Remover indicador de escritura
            document.getElementById('typing')?.remove();

            // Verificar que la respuesta sea válida
            if (!data || !data.response) {
                throw new Error('Respuesta inválida del servidor');
            }

            // Crear mensaje del bot con respuesta de la base de datos
            const botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            botMessage.setAttribute('role', 'article');

            const p = document.createElement('p');
            p.textContent = data.response;

            botMessage.appendChild(p);

            chatBox.appendChild(botMessage);
            chatBox.scrollTop = chatBox.scrollHeight;
        })
        .catch(error => {
            console.error('Error en el chatbot:', error);
            document.getElementById('typing')?.remove();

            // Mostrar mensaje de error si la API falla
            const botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            const p = document.createElement('p');
            p.textContent = 'Disculpa, hubo un error. Intenta de nuevo.';
            botMessage.appendChild(p);
            chatBox.appendChild(botMessage);
            chatBox.scrollTop = chatBox.scrollHeight;
        });
    });

    // Toda la lógica del chat es manejada por chatbot-api.php
    // El archivo API consulta la base de datos y devuelve respuestas
});
