// Animación de cambio de página para capítulos
document.addEventListener('DOMContentLoaded', function () {
    // Botón siguiente cap
    const nextButton = document.querySelector('.cambioSiguiente');

    if (nextButton) {
        // mini animación para lo de siguiente cap
        nextButton.addEventListener('click', function (event) {
            event.preventDefault(); // por si se vuelve loco

            const contenido = document.querySelector('.contenido');
            const nextUrl = this.getAttribute('href');

            // clase de animación
            contenido.classList.add('flipping-out');

            // primero animación y luego redirección
            setTimeout(function () {
                window.location.href = nextUrl;
            }, 800); // 800ms = duración de la animación
        });
    }

    const prevButton = document.querySelector('.cambioAnterior');

    if (prevButton) {
        // mini animación para lo de anterior cap
        prevButton.addEventListener('click', function (event) {
            event.preventDefault(); // por si se vuelve loco
            const contenido = document.querySelector('.contenido');
            const prevUrl = this.getAttribute('href');

            // clase de animación
            contenido.classList.add('flipping-out');
            // primero animación y luego redirección
            setTimeout(function () {
                window.location.href = prevUrl;
            }, 800); // 800ms = duración de la animación
        });
    }
});

// Animación de notas
document.addEventListener("click", function (e) {
    const anotacion = e.target.closest(".anotacion");

    if (anotacion) {
        document.querySelectorAll(".anotacion.activa")
            .forEach(el => {
                if (el !== anotacion) el.classList.remove("activa");
            });

        anotacion.classList.toggle("activa");
        return;
    }

    document.querySelectorAll(".anotacion.activa")
        .forEach(el => el.classList.remove("activa"));
});