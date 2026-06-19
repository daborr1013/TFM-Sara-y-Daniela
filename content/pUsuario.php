<?php
session_start();

// Si el usuario no está logueado, redirigir al login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Conectar a la base de datos
require '../database.php';
require '../progress-helper.php';

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, nombre, email, fecha_registro FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Si el usuario no existe en la BD, destruir la sesión
if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// Extraer fecha de registro
$fecha_registro = date('Y', strtotime($user['fecha_registro']));

// Obtener estadísticas de progreso
$stats = obtenerEstadisticas($conn, $user_id);
$porcentaje = calcularPorcentajeProgreso($stats);

// Obtener historial de actividades
$progreso = obtenerProgresoUsuario($conn, $user_id);
$progreso_por_tipo = obtenerProgresoPorTipo($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de usuario - Litterally</title>
    <link rel="stylesheet" href="../css/css_pUsuario.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>

<body>

    <a href="#main" class="skip-link">Saltar a contenido principal</a>

    <header>
        <a href="../index.php"><img class="logo" src="../media/images/litGrande.png" alt="Litterally - Inicio"></a>
    </header>

    <nav class="navbar">
        <ul class="menu">
            <li><a href="../index.php">Inicio</a></li>

            <li class="dropdown">
                <a href="obras.php">Obras</a>
                <ul class="dropdown-menu">
                    <li><a href="works/eyre.php">Jane Eyre</a></li>
                </ul>
            </li>

            <li><a href="about_us.php">Sobre nosotras</a></li>
            <li><a href="litto.php">Litto</a></li>
            <li><a href="pUsuario.php">Perfil de usuario</a></li>
            <li><a href="../logout.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <main id="main">
        <div class="card-container">
            <div class="user-card">
                <div class="card-header">
                    <h1>LITTERALLY ID CARD</h1>
                    <div class="library-badge">Litterally.com</div>
                </div>

                <div class="card-content">
                    <div class="card-left">
                        <div class="profile-picture">
                            <img src="../media/images/pfp.jpg"
                                alt="Foto de perfil de <?php echo htmlspecialchars($user['nombre']); ?>">
                        </div>
                        <div class="user-info">
                            <div class="info-field">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['nombre']); ?></span>
                            </div>
                            <div class="info-field">
                                <span class="info-label">ID:</span>
                                <span
                                    class="info-value"><?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT) . 'A'; ?></span>
                            </div>
                            <div class="info-field">
                                <span class="info-label">Miembro desde:</span>
                                <span class="info-value"><?php echo $fecha_registro; ?></span>
                            </div>
                            <div class="info-field">
                                <span class="info-label">Correo:</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-right">
                        <div class="barcode">
                            <img src="../media/images/barcode.avif" alt="Código de barras">
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button class="edit-profile">Editar perfil</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE PROGRESO INTEGRADA -->
        <div class="progress-wrapper">
            <div id="seccion-progreso">
                <h2 class="section-title">Tu Actividad y Progreso</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Progreso General</div>
                        <div class="progress-container">
                            <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%;"></div>
                        </div>
                        <div class="stat-summary">
                            <span class="stat-main-value" style="color: #4CAF50;"><?php echo $porcentaje; ?>%</span>
                            <span class="stat-sub-value"><?php echo ($stats['completadas'] ?? 0); ?> de <?php echo ($stats['total_actividades'] ?? 0); ?></span>
                        </div>
                    </div>

                    <div class="stat-card stat-violet">
                        <div class="stat-label">Puntuación Media</div>
                        <div class="stat-main-value">
                            <?php echo ($stats['puntuacion_promedio'] ?? 0) > 0 ? round($stats['puntuacion_promedio']) : '—'; ?>
                        </div>
                        <span class="stat-sub-value">sobre 100 puntos</span>
                    </div>

                    <div class="stat-card stat-gold">
                        <div class="stat-label">Mejor Puntuación</div>
                        <div class="stat-main-value">
                            <?php echo ($stats['puntuacion_maxima'] ?? 0) > 0 ? $stats['puntuacion_maxima'] : '—'; ?>
                        </div>
                        <span class="stat-sub-value">récord personal</span>
                    </div>
                </div>

                <?php if (!empty($progreso_por_tipo)): ?>
                    <h3 class="section-subtitle">Desglose por Categoría</h3>
                    <div class="stats-grid categories-grid">
                        <?php foreach ($progreso_por_tipo as $tipo): ?>
                            <div class="category-card">
                                <div class="category-name"><?php echo htmlspecialchars($tipo['tipo'] ?? 'Actividad'); ?></div>
                                <div class="category-info-row">
                                    <span>Éxito:</span> <strong><?php echo ($tipo['completadas'] ?? 0); ?> / <?php echo $tipo['total']; ?></strong>
                                </div>
                                <div class="category-info-row">
                                    <span>Media:</span> <strong><?php echo $tipo['promedio'] > 0 ? round($tipo['promedio']) : '—'; ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 class="section-subtitle">Historial de Actividades</h3>
                <div class="history-container">
                    <?php if (empty($progreso)): ?>
                        <div class="empty-history">
                            <span>📚</span>
                            <p>Aún no has completado ninguna actividad.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($progreso as $item): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <div class="activity-name"><?php echo htmlspecialchars($item['nombre_obra'] ?? 'Actividad #' . $item['activity_id']); ?></div>
                                    <div class="activity-meta">
                                        <span class="activity-tag"><?php echo htmlspecialchars($item['tipo'] ?? 'General'); ?></span>
                                        <span class="activity-level">Nivel: <?php echo htmlspecialchars($item['nivel'] ?? 'Normal'); ?></span>
                                    </div>
                                </div>
                                <div class="activity-result">
                                    <div class="activity-points">
                                        <div class="points-number"><?php echo $item['puntuacion']; ?></div>
                                        <div class="points-label">puntos</div>
                                    </div>
                                    <div class="activity-status">COMPLETADA</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>TFM – Letras Digitales – UCM</p>
    </footer>

</body>

</html>