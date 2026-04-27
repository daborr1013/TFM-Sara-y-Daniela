<?php
// EJEMPLO: actividad-ejemplo.php
// Este archivo muestra cómo integrar el sistema de progreso en una actividad

session_start();

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require '../database.php';
require '../progress-helper.php';

$user_id = $_SESSION['user_id'];

// Si se envía el formulario de la actividad
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['completar_actividad'])) {

    // Obtener datos del formulario
    $activity_id = 1; // CAMBIAR: el ID real de la actividad
    $puntuacion = intval($_POST['puntuacion'] ?? 0);
    $completado = 1;

    // Guardar el progreso
    $resultado = guardarProgreso($conn, $user_id, $activity_id, $puntuacion, $completado);

    if ($resultado) {
        // Mostrar mensaje de éxito
        $mensaje_exito = "¡Actividad completada! Tu puntuación: $puntuacion/100";
    } else {
        $mensaje_error = "Error al guardar el progreso";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplo de Actividad - Litterally</title>
    <link rel="stylesheet" href="../css/actividad.css">
</head>

<body>
    <div class="container">
        <h1>📚 Ejemplo: Mi Primera Actividad</h1>

        <!-- Mostrar mensajes -->
        <?php if (isset($mensaje_exito)): ?>
            <div class="success-message">
                ✓ <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($mensaje_error)): ?>
            <div class="error-message">
                ✗ <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <!-- Contenido de la actividad -->
        <div class="activity-content">
            <h2>Pregunta: ¿Cuál es tu opinión sobre Jane Eyre?</h2>
            <p>Lee el siguiente fragmento y responde las preguntas:</p>
            <blockquote style="margin: 15px 0; padding: 10px; border-left: 3px solid #4CAF50;">
                "Yo era una discordancia en Gateshead Hall..."
            </blockquote>
        </div>

        <!-- Formulario de la actividad -->
        <form method="POST">
            <div class="form-group">
                <label for="respuesta">Tu Respuesta:</label>
                <textarea id="respuesta" name="respuesta" rows="5" placeholder="Escribe tu respuesta aquí..."
                    required></textarea>
            </div>

            <div class="form-group">
                <label for="puntuacion">
                    ¿Qué tan seguro estás de tu respuesta?
                    <span class="range-value" id="puntuacion-value">50</span>
                </label>
                <input type="range" id="puntuacion" name="puntuacion" min="0" max="100" value="50" style="width: 100%;">
                <small style="color: #666;">0 = No seguro, 100 = Muy seguro</small>
            </div>

            <input type="hidden" name="completar_actividad" value="1">
            <button type="submit" class="submit-btn">Enviar Respuesta</button>
        </form>

        <!-- Información adicional -->
        <div class="info-box">
            <strong>ℹ️ ¿Cómo funciona?</strong>
            <p style="margin-top: 10px; font-size: 14px;">
                Cuando envíes esta respuesta, el sistema guardará tu progreso automáticamente.
                Podrás ver tu puntuación y el historial de actividades completadas en tu
                <a href="../content/pUsuario.php">perfil</a> o en
                <a href="../content/progreso.php">Mi Progreso</a>.
            </p>
        </div>

        <div class="back-link">
            <a href="../index.php">← Volver al Inicio</a>
        </div>
    </div>

    <!-- Script para actualizar el valor del rango -->
    <script>
        const rangeInput = document.getElementById('puntuacion');
        const rangeValue = document.getElementById('puntuacion-value');

        rangeInput.addEventListener('input', function () {
            rangeValue.textContent = this.value;
        });
    </script>
</body>

</html>