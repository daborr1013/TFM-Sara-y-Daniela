// Animación de cambio de página para capítulos
document.addEventListener('DOMContentLoaded', function() {
    // Botón siguiente cap
    const nextButton = document.querySelector('.cambioSiguiente');
    
    if (nextButton) {
        // mini animación para lo de siguiente cap
        nextButton.addEventListener('click', function(event) {
            event.preventDefault(); // por si se vuelve loco
            
            const contenido = document.querySelector('.contenido');
            const nextUrl = this.getAttribute('href');
            
            // clase de animación
            contenido.classList.add('flipping-out');
            
            // primero animación y luego redirección
            setTimeout(function() {
                window.location.href = nextUrl;
            }, 800); // 800ms = duración de la animación
        });
    }
});