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

    setupReflectionBoxes();
});

const REFLECTIONS_STORAGE_KEY = 'litterally_reflections_v1';

function readReflections() {
    try {
        const saved = localStorage.getItem(REFLECTIONS_STORAGE_KEY);
        return saved ? JSON.parse(saved) : {};
    } catch (error) {
        console.warn('No se han podido recuperar las reflexiones guardadas.', error);
        return {};
    }
}

function writeReflections(reflections) {
    try {
        localStorage.setItem(REFLECTIONS_STORAGE_KEY, JSON.stringify(reflections));
        return true;
    } catch (error) {
        console.warn('No se ha podido guardar la reflexión.', error);
        return false;
    }
}

function setupReflectionBoxes() {
    const boxes = document.querySelectorAll('.caja-reflexion');
    if (!boxes.length) return;

    const reflections = readReflections();

    boxes.forEach((box) => {
        const textarea = box.querySelector('textarea');
        const title = box.querySelector('h3');
        const question = box.querySelector('.pregunta-reflexion');
        const reflectionId = title?.id || textarea?.name;
        if (!textarea || !reflectionId) return;

        const saved = reflections[reflectionId];
        if (saved?.answer) textarea.value = saved.answer;

        const actions = document.createElement('div');
        actions.className = 'reflexion-acciones';
        actions.innerHTML = '<button class="boton-guardar-reflexion" type="button">Guardar respuesta</button><p class="estado-reflexion" aria-live="polite"></p>';
        box.appendChild(actions);

        const button = actions.querySelector('.boton-guardar-reflexion');
        const status = actions.querySelector('.estado-reflexion');

        button.addEventListener('click', () => {
            const answer = textarea.value.trim();
            if (!answer) {
                status.textContent = 'Escribe una respuesta antes de guardarla.';
                return;
            }

            reflections[reflectionId] = {
                title: title?.textContent.trim() || 'Reflexión',
                question: question?.textContent.trim() || '',
                answer,
                chapterUrl: window.location.href,
                updatedAt: new Date().toISOString(),
            };

            if (writeReflections(reflections)) {
                status.textContent = 'Respuesta guardada en este navegador.';
            } else {
                status.textContent = 'No se ha podido guardar la respuesta en este navegador.';
            }
        });
    });
}

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
