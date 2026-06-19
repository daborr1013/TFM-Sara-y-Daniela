/**
 * Chat de Litto.
 *
 * Gestiona el envio de mensajes y muestra las respuestas del chatbot
 * conservando los saltos de linea devueltos por la API.
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatBox = document.getElementById('chatBox');

    if (!chatForm || !messageInput || !chatBox) {
        console.warn('Elementos del chat no encontrados.');
        return;
    }

    const appendMessage = (text, sender = 'bot', extraId = '') => {
        const wrapper = document.createElement('div');
        wrapper.className = `message ${sender}`;
        wrapper.setAttribute('role', 'article');

        if (extraId) {
            wrapper.id = extraId;
        }

        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        wrapper.appendChild(paragraph);

        chatBox.appendChild(wrapper);
        chatBox.scrollTop = chatBox.scrollHeight;

        return wrapper;
    };

    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const messageText = messageInput.value.trim();
        if (messageText === '') {
            messageInput.focus();
            return;
        }

        appendMessage(messageText, 'user');
        messageInput.value = '';
        messageInput.focus();

        appendMessage('Escribiendo...', 'bot', 'typing');

        try {
            const response = await fetch('../chatbot-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: messageText }),
            });

            const data = await response.json().catch(() => null);
            document.getElementById('typing')?.remove();

            if (!response.ok) {
                const error = new Error(data?.response || `Error HTTP ${response.status}`);
                error.status = response.status;
                throw error;
            }

            if (!data || typeof data.response !== 'string') {
                throw new Error('La respuesta del servidor no tiene el formato esperado.');
            }

            appendMessage(data.response, 'bot');
        } catch (error) {
            console.error('Error en el chatbot:', error);
            document.getElementById('typing')?.remove();

            let errorMessage = 'Disculpa, ha ocurrido un error. Inténtalo de nuevo.';

            if (error?.status === 401) {
                errorMessage = 'Tu sesión ha caducado o no has iniciado sesión. Vuelve a entrar para seguir usando Litto.';
            } else if (typeof error?.message === 'string' && error.message.trim() !== '') {
                errorMessage = error.message;
            }

            appendMessage(errorMessage, 'bot');
        }
    });
});
