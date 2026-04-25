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
 * Muestra un botón para guardar el progreso
 * Uso:
 * showProgressButton(2, 85, "Cuestionario de Capítulo 1");
 */
function mostrarBotonProgreso($activity_id, $puntuacion = 0, $nombre_actividad = "Actividad") {
    
    if (!USER_LOGGED_IN) {
        return;
    }
    
    echo "
    <div style='text-align: center; margin: 20px 0;'>
        <button 
            class='btn-guardar-progreso' 
            onclick='guardarProgreso($activity_id, $puntuacion, \"$nombre_actividad\")'
            style='
                background-color: #6A4C93;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            '
            onmouseover='this.style.backgroundColor=\"#593c7d\"; this.style.transform=\"translateY(-2px)\";'
            onmouseout='this.style.backgroundColor=\"#6A4C93\"; this.style.transform=\"translateY(0)\";'
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
        <div style='background: #f8f3eb; padding: 15px; border-radius: 8px; text-align: center; color: #555; border: 1px solid #e1d5c9; margin-bottom: 20px;'>
            <p style='margin:0 0 10px 0;'>Inicia sesión para rastrear tu progreso en esta actividad</p>
            <a href='/TFM-Sara-y-Daniela/login.php' style='display:inline-block; background-color:#6A4C93; color: white; padding: 8px 16px; border-radius:5px; text-decoration: none; font-weight: bold; transition: background 0.3s;'>Iniciar sesión</a>
        </div>
        ";
        return;
    }
    
    echo "
    <div style='
        background: linear-gradient(135deg, #6A4C93, #8b6bba);
        color: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 10px rgba(106, 76, 147, 0.2);
        margin-bottom: 20px;
    '>
        <p style='font-size: 15px; font-weight: bold; margin: 0;'>🎮 Tu progreso se guarda automáticamente</p>
        <a href='/TFM-Sara-y-Daniela/content/pUsuario.php' style='display: inline-block; margin-top: 10px; padding: 5px 15px; background: rgba(255,255,255,0.2); border-radius: 20px; color: white; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.3s;' onmouseover='this.style.background=\"rgba(255,255,255,0.3)\"' onmouseout='this.style.background=\"rgba(255,255,255,0.2)\"'>Ver mi perfil</a>
    </div>
    ";
}

/**
 * Muestra el JavaScript necesario para guardar progreso
 */
function mostrarScriptProgreso() {
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
