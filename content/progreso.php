<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require '../database.php';
require '../progress-helper.php';

$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];

// Obtener datos de progreso
$progreso = obtenerProgresoUsuario($conn, $user_id);
$stats = obtenerEstadisticas($conn, $user_id);
$progreso_por_tipo = obtenerProgresoPorTipo($conn, $user_id);
$porcentaje = calcularPorcentajeProgreso($stats);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - Litterally</title>
    <link rel="stylesheet" href="../css/css_progreso.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>

<body>
    <header>
        <a href="../index.php">
            <img src="../media/images/litGrande.png" alt="Litterally">
        </a>
    </header>

    <nav class="navbar">
        <ul>
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="obras.php">Obras</a></li>
            <li><a href="about_us.php">Sobre nosotras</a></li>
            <li><a href="pUsuario.php">Perfil</a></li>
            <li><a href="progreso.php" style="color: #4CAF50;">Mi Progreso</a></li>
            <li><a href="../logout.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <main>
        <h1>Mi Progreso - <?php echo htmlspecialchars($user_nombre); ?></h1>

        <!-- Estadísticas Generales -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Progreso General</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $porcentaje; ?>%;">
                        <?php echo $porcentaje; ?>%
                    </div>
                </div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">
                    <?php echo ($stats['completadas'] ?? 0) . " de " . ($stats['total_actividades'] ?? 0) . " completadas"; ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Puntuación Promedio</h3>
                <div class="stat-value">
                    <?php echo ($stats['puntuacion_promedio'] ?? 0) > 0 ? round($stats['puntuacion_promedio']) : '—'; ?>
                </div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">de 100 puntos</p>
            </div>

            <div class="stat-card">
                <h3>Mejor Puntuación</h3>
                <div class="stat-value">
                    <?php echo ($stats['puntuacion_maxima'] ?? 0) > 0 ? $stats['puntuacion_maxima'] : '—'; ?>
                </div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">puntos máximos</p>
            </div>

            <div class="stat-card">
                <h3>Total de Actividades</h3>
                <div class="stat-value">
                    <?php echo ($stats['total_actividades'] ?? 0); ?>
                </div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">actividades intentadas</p>
            </div>
        </div>

        <!-- Progreso por Tipo -->
        <?php if (!empty($progreso_por_tipo)): ?>
            <h2 class="section-title">Progreso por Tipo de Actividad</h2>
            <div class="tipo-stats">
                <?php foreach ($progreso_por_tipo as $tipo): ?>
                    <div class="tipo-card">
                        <h4><?php echo htmlspecialchars($tipo['tipo'] ?? 'Sin tipo'); ?></h4>
                        <div class="tipo-stat">
                            <span>Total:</span>
                            <strong><?php echo $tipo['total']; ?></strong>
                        </div>
                        <div class="tipo-stat">
                            <span>Completadas:</span>
                            <strong><?php echo ($tipo['completadas'] ?? 0); ?></strong>
                        </div>
                        <div class="tipo-stat">
                            <span>Promedio:</span>
                            <strong><?php echo $tipo['promedio'] > 0 ? round($tipo['promedio']) : '—'; ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Historial de Actividades -->
        <h2 class="section-title">Historial de Actividades</h2>

        <?php if (empty($progreso)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <p>Aún no has completado ninguna actividad</p>
                <a href="obras.php" class="btn">Explorar Obras</a>
            </div>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($progreso as $item): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title">
                                Actividad #<?php echo $item['activity_id']; ?>
                                <?php if (!empty($item['nombre_obra'])): ?>
                                    - <em><?php echo htmlspecialchars($item['nombre_obra']); ?></em>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="activity-type">
                                    <?php echo htmlspecialchars($item['tipo'] ?? 'Sin tipo'); ?>
                                </span>
                                <span class="activity-level">
                                    Nivel: <?php echo htmlspecialchars($item['nivel'] ?? 'Normal'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="activity-status">
                            <div class="score">
                                <div class="score-value"><?php echo $item['puntuacion']; ?></div>
                                <div class="score-label">puntos</div>
                            </div>
                            <span class="badge-<?php echo ($item['completado'] == 1) ? 'completado' : 'pendiente'; ?>">
                                <?php echo ($item['completado'] == 1) ? '✓ Completada' : 'Pendiente'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>TFM – Letras Digitales – UCM</p>
    </footer>
</body>

</html>