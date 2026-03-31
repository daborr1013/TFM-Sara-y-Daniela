<?php
// progress-helper.php
// Funciones para rastrear el progreso de los usuarios

/**
 * Registra o actualiza el progreso de un usuario en una actividad
 * 
 * @param mysqli $conn - Conexión a la base de datos
 * @param int $user_id - ID del usuario
 * @param int $activity_id - ID de la actividad
 * @param int $puntuacion - Puntuación obtenida (0-100)
 * @param bool $completado - Si la actividad está completada
 * @return bool - true si fue exitoso, false si falló
 */
function guardarProgreso($conn, $user_id, $activity_id, $puntuacion = 0, $completado = true) {
    
    // Verificar si ya existe progreso para esta actividad
    $sql_check = "SELECT id FROM user_progress WHERE user_id = ? AND activity_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    
    if (!$stmt_check) {
        error_log("Error en preparación: " . $conn->error);
        return false;
    }
    
    $stmt_check->bind_param("ii", $user_id, $activity_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Actualizar progreso existente
        $sql_update = "UPDATE user_progress 
                       SET puntuacion = ?, completado = ?, fecha = NOW() 
                       WHERE user_id = ? AND activity_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if (!$stmt_update) {
            error_log("Error en UPDATE: " . $conn->error);
            return false;
        }
        
        $stmt_update->bind_param("iiii", $puntuacion, $completado, $user_id, $activity_id);
        $success = $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Insertar nuevo progreso
        $sql_insert = "INSERT INTO user_progress (user_id, activity_id, puntuacion, completado) 
                       VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        
        if (!$stmt_insert) {
            error_log("Error en INSERT: " . $conn->error);
            return false;
        }
        
        $stmt_insert->bind_param("iiii", $user_id, $activity_id, $puntuacion, $completado);
        $success = $stmt_insert->execute();
        $stmt_insert->close();
    }
    
    $stmt_check->close();
    return $success;
}

/**
 * Obtiene el progreso de un usuario en todas las actividades
 * 
 * @param mysqli $conn - Conexión a la base de datos
 * @param int $user_id - ID del usuario
 * @return array - Array con toda la información del progreso
 */
function obtenerProgresoUsuario($conn, $user_id) {
    
    $sql = "SELECT 
                up.id,
                up.user_id,
                up.activity_id,
                up.puntuacion,
                up.completado,
                up.fecha,
                a.tipo,
                a.nivel,
                a.descripcion,
                w.titulo as nombre_obra
            FROM user_progress up
            LEFT JOIN activities a ON up.activity_id = a.id
            LEFT JOIN works w ON a.work_id = w.id
            WHERE up.user_id = ?
            ORDER BY up.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en SELECT progreso: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $progreso = [];
    while ($row = $result->fetch_assoc()) {
        $progreso[] = $row;
    }
    
    $stmt->close();
    return $progreso;
}

/**
 * Obtiene estadísticas del progreso del usuario
 * 
 * @param mysqli $conn - Conexión a la base de datos
 * @param int $user_id - ID del usuario
 * @return array - Array con estadísticas
 */
function obtenerEstadisticas($conn, $user_id) {
    
    $sql = "SELECT 
                COUNT(*) as total_actividades,
                SUM(CASE WHEN completado = 1 THEN 1 ELSE 0 END) as completadas,
                AVG(puntuacion) as puntuacion_promedio,
                MAX(puntuacion) as puntuacion_maxima,
                MIN(puntuacion) as puntuacion_minima
            FROM user_progress
            WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en estadísticas: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
    
    return $stats;
}

/**
 * Obtiene el progreso por tipo de actividad
 * 
 * @param mysqli $conn - Conexión a la base de datos
 * @param int $user_id - ID del usuario
 * @return array - Array con progreso por tipo
 */
function obtenerProgresoPorTipo($conn, $user_id) {
    
    $sql = "SELECT 
                a.tipo,
                COUNT(*) as total,
                SUM(CASE WHEN up.completado = 1 THEN 1 ELSE 0 END) as completadas,
                AVG(up.puntuacion) as promedio
            FROM user_progress up
            LEFT JOIN activities a ON up.activity_id = a.id
            WHERE up.user_id = ?
            GROUP BY a.tipo";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en progreso por tipo: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $por_tipo = [];
    while ($row = $result->fetch_assoc()) {
        $por_tipo[] = $row;
    }
    
    $stmt->close();
    return $por_tipo;
}

/**
 * Calcula el porcentaje de progreso
 * 
 * @param array $stats - Array de estadísticas del usuario
 * @return int - Porcentaje (0-100)
 */
function calcularPorcentajeProgreso($stats) {
    
    if (!$stats || $stats['total_actividades'] == 0) {
        return 0;
    }
    
    $completadas = $stats['completadas'] ?? 0;
    $total = $stats['total_actividades'];
    
    return round(($completadas / $total) * 100);
}
?>
