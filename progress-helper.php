<?php
// progress-helper.php
// Helpers de progreso compatibles con el esquema actual y con versiones antiguas.

function prepareProgressStatement($conn, $sql)
{
    try {
        $stmt = $conn->prepare($sql);
    } catch (mysqli_sql_exception $exception) {
        error_log('Error preparando consulta de progreso: ' . $exception->getMessage());
        return null;
    }

    if (!$stmt) {
        error_log('Error preparando consulta de progreso: ' . $conn->error);
        return null;
    }

    return $stmt;
}

function progressTableExists($conn, $tableName)
{
    static $cache = [];

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = prepareProgressStatement(
        $conn,
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );

    if (!$stmt) {
        $cache[$tableName] = false;
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $result = $stmt->get_result();
    $cache[$tableName] = ($result && $result->num_rows > 0);
    $stmt->close();

    return $cache[$tableName];
}

function cleanProgressLabel($value)
{
    $value = trim((string) $value);
    $value = trim($value, "[] \t\n\r\0\x0B");

    return $value;
}

function getProgressEmptyStats()
{
    return [
        'total_actividades' => 0,
        'completadas' => 0,
        'puntuacion_promedio' => 0,
        'puntuacion_maxima' => 0,
        'puntuacion_minima' => 0,
    ];
}

function getProgressWorkTitle($conn)
{
    static $loaded = false;
    static $title = null;

    if ($loaded) {
        return $title;
    }

    $loaded = true;
    $title = 'Jane Eyre';

    if (!progressTableExists($conn, 'works')) {
        return $title;
    }

    $stmt = prepareProgressStatement($conn, 'SELECT titulo FROM works ORDER BY id ASC LIMIT 1');
    if (!$stmt) {
        return $title;
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!empty($row['titulo'])) {
        $title = cleanProgressLabel($row['titulo']);
    }

    return $title;
}

function getFallbackActivityMetadata($conn, $activityId)
{
    $workTitle = getProgressWorkTitle($conn);

    $catalog = [
        1 => [
            'tipo' => 'Tests',
            'nivel' => 'General',
            'descripcion' => 'Prueba general de comprension',
        ],
        2 => [
            'tipo' => 'Rellenar',
            'nivel' => 'General',
            'descripcion' => 'Actividad para completar espacios',
        ],
        3 => [
            'tipo' => 'Desarrollar',
            'nivel' => 'General',
            'descripcion' => 'Actividad de respuesta abierta',
        ],
        4 => [
            'tipo' => 'Flashcards',
            'nivel' => 'General',
            'descripcion' => 'Repaso con tarjetas',
        ],
        5 => [
            'tipo' => 'Juegos',
            'nivel' => 'General',
            'descripcion' => 'Actividad ludica sobre la obra',
        ],
        10 => [
            'tipo' => 'Tests',
            'nivel' => 'Capitulos 1-4',
            'descripcion' => 'Test sobre los capitulos 1 a 4',
        ],
        11 => [
            'tipo' => 'Tests',
            'nivel' => 'Capitulos 5-10',
            'descripcion' => 'Test sobre los capitulos 5 a 10',
        ],
        12 => [
            'tipo' => 'Tests',
            'nivel' => 'Capitulos 11+',
            'descripcion' => 'Test sobre los capitulos posteriores al 10',
        ],
    ];

    $metadata = $catalog[$activityId] ?? [
        'tipo' => 'Actividad',
        'nivel' => 'General',
        'descripcion' => 'Actividad de Jane Eyre',
    ];

    $metadata['nombre_obra'] = $workTitle ?: 'Jane Eyre';

    return $metadata;
}

function enrichProgressRow($conn, array $row)
{
    $activityId = isset($row['activity_id']) ? (int) $row['activity_id'] : 0;
    $fallback = getFallbackActivityMetadata($conn, $activityId);

    foreach (['tipo', 'nivel', 'descripcion', 'nombre_obra'] as $field) {
        if (!isset($row[$field]) || trim((string) $row[$field]) === '') {
            $row[$field] = $fallback[$field];
        } else {
            $row[$field] = cleanProgressLabel($row[$field]);
        }
    }

    return $row;
}

/**
 * Registra o actualiza el progreso de un usuario en una actividad.
 */
function guardarProgreso($conn, $user_id, $activity_id, $puntuacion = 0, $completado = true)
{
    if (!progressTableExists($conn, 'user_progress')) {
        error_log('No existe la tabla user_progress.');
        return false;
    }

    $sql_check = 'SELECT id FROM user_progress WHERE user_id = ? AND activity_id = ?';
    $stmt_check = prepareProgressStatement($conn, $sql_check);

    if (!$stmt_check) {
        return false;
    }

    $stmt_check->bind_param('ii', $user_id, $activity_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql_update = 'UPDATE user_progress
                       SET puntuacion = ?, completado = ?, fecha = NOW()
                       WHERE user_id = ? AND activity_id = ?';
        $stmt_update = prepareProgressStatement($conn, $sql_update);

        if (!$stmt_update) {
            $stmt_check->close();
            return false;
        }

        $stmt_update->bind_param('iiii', $puntuacion, $completado, $user_id, $activity_id);
        $success = $stmt_update->execute();
        $stmt_update->close();
    } else {
        $sql_insert = 'INSERT INTO user_progress (user_id, activity_id, puntuacion, completado)
                       VALUES (?, ?, ?, ?)';
        $stmt_insert = prepareProgressStatement($conn, $sql_insert);

        if (!$stmt_insert) {
            $stmt_check->close();
            return false;
        }

        $stmt_insert->bind_param('iiii', $user_id, $activity_id, $puntuacion, $completado);
        $success = $stmt_insert->execute();
        $stmt_insert->close();
    }

    $stmt_check->close();
    return $success;
}

/**
 * Obtiene el progreso de un usuario en todas las actividades.
 */
function obtenerProgresoUsuario($conn, $user_id)
{
    if (!progressTableExists($conn, 'user_progress')) {
        return [];
    }

    $hasActivitiesTable = progressTableExists($conn, 'activities');
    $hasWorksTable = $hasActivitiesTable && progressTableExists($conn, 'works');

    $sql = 'SELECT
                up.id,
                up.user_id,
                up.activity_id,
                up.puntuacion,
                up.completado,
                up.fecha';

    if ($hasActivitiesTable) {
        $sql .= ',
                a.tipo,
                a.nivel,
                a.descripcion';
    }

    if ($hasWorksTable) {
        $sql .= ',
                w.titulo AS nombre_obra';
    }

    $sql .= '
            FROM user_progress up';

    if ($hasActivitiesTable) {
        $sql .= '
            LEFT JOIN activities a ON up.activity_id = a.id';
    }

    if ($hasWorksTable) {
        $sql .= '
            LEFT JOIN works w ON a.work_id = w.id';
    }

    $sql .= '
            WHERE up.user_id = ?
            ORDER BY up.fecha DESC';

    $stmt = prepareProgressStatement($conn, $sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $progreso = [];
    while ($row = $result->fetch_assoc()) {
        $progreso[] = enrichProgressRow($conn, $row);
    }

    $stmt->close();
    return $progreso;
}

/**
 * Obtiene estadisticas del progreso del usuario.
 */
function obtenerEstadisticas($conn, $user_id)
{
    if (!progressTableExists($conn, 'user_progress')) {
        return getProgressEmptyStats();
    }

    $sql = 'SELECT
                COUNT(*) AS total_actividades,
                SUM(CASE WHEN completado = 1 THEN 1 ELSE 0 END) AS completadas,
                AVG(puntuacion) AS puntuacion_promedio,
                MAX(puntuacion) AS puntuacion_maxima,
                MIN(puntuacion) AS puntuacion_minima
            FROM user_progress
            WHERE user_id = ?';

    $stmt = prepareProgressStatement($conn, $sql);

    if (!$stmt) {
        return getProgressEmptyStats();
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$stats) {
        return getProgressEmptyStats();
    }

    return array_merge(getProgressEmptyStats(), $stats);
}

/**
 * Obtiene el progreso agrupado por tipo de actividad.
 */
function obtenerProgresoPorTipo($conn, $user_id)
{
    $progreso = obtenerProgresoUsuario($conn, $user_id);

    if (empty($progreso)) {
        return [];
    }

    $grouped = [];

    foreach ($progreso as $item) {
        $tipo = trim((string) ($item['tipo'] ?? 'Actividad'));
        if ($tipo === '') {
            $tipo = 'Actividad';
        }

        if (!isset($grouped[$tipo])) {
            $grouped[$tipo] = [
                'tipo' => $tipo,
                'total' => 0,
                'completadas' => 0,
                'sumatorio' => 0,
            ];
        }

        $grouped[$tipo]['total']++;
        $grouped[$tipo]['completadas'] += ((int) ($item['completado'] ?? 0) === 1) ? 1 : 0;
        $grouped[$tipo]['sumatorio'] += (int) ($item['puntuacion'] ?? 0);
    }

    foreach ($grouped as &$row) {
        $row['promedio'] = $row['total'] > 0 ? ($row['sumatorio'] / $row['total']) : 0;
        unset($row['sumatorio']);
    }
    unset($row);

    usort($grouped, static function ($left, $right) {
        return $right['total'] <=> $left['total'];
    });

    return $grouped;
}

/**
 * Calcula el porcentaje de progreso.
 */
function calcularPorcentajeProgreso($stats)
{
    if (!$stats || (int) ($stats['total_actividades'] ?? 0) === 0) {
        return 0;
    }

    $completadas = (int) ($stats['completadas'] ?? 0);
    $total = (int) ($stats['total_actividades'] ?? 0);

    if ($total <= 0) {
        return 0;
    }

    return round(($completadas / $total) * 100);
}
?>
