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

    const renderMessageText = (container, text) => {
        const lines = String(text).split(/\r?\n/);
        let list = null;
        let paragraphLines = [];

        const flushParagraph = () => {
            if (paragraphLines.length === 0) {
                return;
            }

            const paragraph = document.createElement('p');
            paragraph.textContent = paragraphLines.join('\n');
            container.appendChild(paragraph);
            paragraphLines = [];
        };

        const flushList = () => {
            list = null;
        };

        lines.forEach((line) => {
            const trimmed = line.trim();

            if (trimmed === '') {
                flushParagraph();
                flushList();
                return;
            }

            if (trimmed.startsWith('- ')) {
                flushParagraph();
                if (!list) {
                    list = document.createElement('ul');
                    container.appendChild(list);
                }

                const item = document.createElement('li');
                item.textContent = trimmed.slice(2);
                list.appendChild(item);
                return;
            }

            flushList();
            paragraphLines.push(trimmed);
        });

        flushParagraph();
    };

    const appendMessage = (text, sender = 'bot', extraId = '') => {
        const wrapper = document.createElement('div');
        wrapper.className = `message ${sender}`;
        wrapper.setAttribute('role', 'article');

        if (extraId) {
            wrapper.id = extraId;
        }

        renderMessageText(wrapper, text);

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
            const data = await window.LitterallyApi.post('/api/chatbot', { message: messageText });
            document.getElementById('typing')?.remove();

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
