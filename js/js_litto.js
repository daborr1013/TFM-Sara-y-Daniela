/**
 * ====================================
 * JAVASCRIPT - CHAT BOT LITTO
 * ====================================
 * 
 * Funcionalidad:
 * - Gestiona la interacción del chat del asistente virtual "Litto"
 * - Maneja el envío de mensajes desde el formulario
 * - Auto-scroll del chat hacia nuevos mensajes
 * 
 * Accesibilidad:
 * - El formulario es completamente navegable por teclado
 * - Los mensajes se añaden al DOM preservando semántica
 * - Para lectores de pantalla, los mensajes se anuncian dinámicamente
 * 
 * Mejoras futuras:
 * - Integrar con API de IA (ChatGPT, etc.)
 * - Agregar ARIA live region para anuncios automáticos
 * - Agregar indicador de "escribiendo..." visual
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
        // role="article" para better accessibility
        newMessage.innerHTML = `<p>${escapeHtml(messageText)}</p>`;
        
        // 2. Añadir el mensaje a la caja de chat
        chatBox.appendChild(newMessage);

        // 3. Limpiar el input y devolver focus
        messageInput.value = '';
        messageInput.focus();

        // 4. Scroll automático hacia abajo
        chatBox.scrollTop = chatBox.scrollHeight;

        // Opcional: Simular respuesta del bot
        setTimeout(() => {
            const botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            botMessage.setAttribute('role', 'article');
            botMessage.innerHTML = `<p>Recibí: ${escapeHtml(messageText)}</p>`;
            chatBox.appendChild(botMessage);
            chatBox.scrollTop = chatBox.scrollHeight;
        }, 1000);
    });

    /**
     * Función de seguridad: escapar HTML para prevenir inyecciones
     * Previene que código malicioso en mensajes se ejecute
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
