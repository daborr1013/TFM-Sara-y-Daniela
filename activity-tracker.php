<?php
// activity-tracker.php
// Componente para rastrear y guardar progreso de actividades
// Uso: require 'activity-tracker.php'; en tus páginas de actividades

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    // Si no está logueado, no mostrar botones de progreso
    define('USER_LOGGED_IN', false);
} else {
    define('USER_LOGGED_IN', true);
}

/**
 * Muestra el link al CSS de actividades
 */
function mostrarEstilosProgreso() {
    $current_uri = $_SERVER['REQUEST_URI'];
    $cssPath = (strpos($current_uri, '/TFM-Sara-y-Daniela/') !== false) 
        ? '/TFM-Sara-y-Daniela/css/css_actividad.css' 
        : './css/css_actividad.css';
    echo "<link rel='stylesheet' href='$cssPath'>";
}

/**
 * Muestra un botón para guardar el progreso
 * Uso:
 * showProgressButton(2, 85, "Cuestionario de Capítulo 1");
 */
function mostrarBotonProgreso($activity_id, $puntuacion = 0, $nombre_actividad = "Actividad") {
    
    if (!USER_LOGGED_IN) {
        return;
    }
    
    echo "
    <div class='progress-button-container'>
        <button 
            class='btn-guardar-progreso' 
            onclick='guardarProgreso($activity_id, $puntuacion, \"$nombre_actividad\")'
        >
            ✓ Marcar como Completada
        </button>
    </div>
    ";
}

/**
 * Muestra un widget de progreso rápido
 */
function mostrarWidgetProgreso() {
    
    if (!USER_LOGGED_IN) {
    echo "
        <div class='widget-no-login'>
            <p class='widget-no-login-msg'>Inicia sesión para rastrear tu progreso en esta actividad</p>
            <a href='/TFM-Sara-y-Daniela/login.php' class='btn-login-progreso'>Iniciar sesión</a>
        </div>
        ";
        return;
    }
    
    echo "
    <div class='widget-progreso'>
        <p class='widget-progreso-msg'>🎮 Tu progreso se guarda automáticamente</p>
        <a href='/TFM-Sara-y-Daniela/content/pUsuario.php' class='link-perfil-progreso'>Ver mi perfil</a>
    </div>
    ";
}

/**
 * Muestra el JavaScript necesario para guardar progreso
 */
function mostrarScriptProgreso() {
    mostrarEstilosProgreso();
    echo "
    <script>
    function guardarProgreso(activityId, puntuacion = 0, nombreActividad = 'Actividad') {
        if (!activityId) {
            alert('Error: ID de actividad no especificado');
            return;
        }
        
        // Determinar la ruta correcta de save-progress.php
        // Funciona desde cualquier profundidad de directorios
        const basePath = window.location.pathname.includes('/TFM-Sara-y-Daniela/')
            ? '/TFM-Sara-y-Daniela/save-progress.php'
            : '../save-progress.php';
        
        // Enviar datos al servidor
        fetch(basePath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'activity_id=' + activityId + '&puntuacion=' + puntuacion + '&completado=1'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('✓ ¡Actividad completada con éxito!\\n' + nombreActividad + ' - ' + puntuacion + ' puntos');
                
                // Actualizar el botón
                const btn = event.target;
                if (btn) {
                    btn.disabled = true;
                    btn.style.backgroundColor = '#ccc';
                    btn.textContent = '✓ Completada';
                }
            } else {
                alert('Error: ' + (data.error || 'No se pudo guardar el progreso'));
            }
        })
        .catch(error => {
            console.error('Error en guardarProgreso:', error);
            console.error('URL intentada:', basePath);
            alert('Error al guardar: ' + error.message + '\\nPor favor, verifica tu conexión e intenta de nuevo.');
        });
    }
    </script>
    ";
}

?>
