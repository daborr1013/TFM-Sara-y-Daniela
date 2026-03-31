<?php
// save-progress.php
// Script para guardar o actualizar el progreso de una actividad
// Uso: Se llama desde AJAX o mediante POST

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require 'database.php';
require 'progress-helper.php';

// Obtener datos del POST
$user_id = $_SESSION['user_id'];
$activity_id = $_POST['activity_id'] ?? null;
$puntuacion = $_POST['puntuacion'] ?? 0;
$completado = $_POST['completado'] ?? 1;

// Validar datos
if ($activity_id === null) {
    http_response_code(400);
    echo json_encode(['error' => 'activity_id es requerido']);
    exit;
}

// Validar puntuación
$puntuacion = intval($puntuacion);
if ($puntuacion < 0 || $puntuacion > 100) {
    $puntuacion = 0;
}

// Validar completado
$completado = intval($completado);

// Guardar el progreso
$resultado = guardarProgreso($conn, $user_id, intval($activity_id), $puntuacion, $completado);

if ($resultado) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'mensaje' => 'Progreso guardado correctamente'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el progreso']);
}
?>
