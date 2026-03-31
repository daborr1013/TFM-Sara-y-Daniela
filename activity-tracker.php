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
                background-color: #4CAF50;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 0.3s;
            '
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
        <div style='background: #f5f5f5; padding: 15px; border-radius: 5px; text-align: center; color: #666;'>
            <p>Inicia sesión para rastrear tu progreso</p>
            <a href='../../login.php' style='color: #4CAF50; text-decoration: none; font-weight: bold;'>Iniciar sesión</a>
        </div>
        ";
        return;
    }
    
    echo "
    <div style='
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
    '>
        <p style='font-size: 14px; opacity: 0.9; margin: 0;'>Tu progreso se guarda automáticamente</p>
        <p style='font-size: 12px; opacity: 0.8; margin-top: 5px;'>
            <a href='../../content/pUsuario.php' style='color: white; text-decoration: underline;'>Ver mi progreso</a>
        </p>
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
        
        // Enviar datos al servidor
        fetch('../../save-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'activity_id=' + activityId + '&puntuacion=' + puntuacion + '&completado=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('✓ ¡Actividad completada con éxito!\\n' + nombreActividad + ' - ' + puntuacion + ' puntos');
                
                // Actualizar el botón
                const btn = event.target;
                btn.disabled = true;
                btn.style.backgroundColor = '#ccc';
                btn.textContent = '✓ Completada';
            } else {
                alert('Error: ' + (data.error || 'No se pudo guardar el progreso'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar. Por favor, intenta de nuevo.');
        });
    }
    </script>
    ";
}

?>
