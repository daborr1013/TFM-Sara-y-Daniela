document.addEventListener('DOMContentLoaded', () => {
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatBox = document.getElementById('chatBox');

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita que la página se recargue

        const messageText = messageInput.value;
        if (messageText.trim() === "") return;

        // 1. Crear el nuevo mensaje del usuario
        const newMessage = document.createElement('div');
        newMessage.className = 'message user';
        newMessage.innerHTML = `<p>${messageText}</p>`;
        
        // 2. Añadir el mensaje a la caja de chat
        chatBox.appendChild(newMessage);

        // 3. Limpiar el input
        messageInput.value = '';

        // 4. Scroll automático hacia abajo
        chatBox.scrollTop = chatBox.scrollHeight;

        // Opcional: Simular respuesta del bot
        setTimeout(() => {
            const botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            botMessage.innerHTML = `<p>Recibí: ${messageText}</p>`;
            chatBox.appendChild(botMessage);
            chatBox.scrollTop = chatBox.scrollHeight;
        }, 1000);
    });
});
