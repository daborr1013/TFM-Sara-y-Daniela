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
    <!-- Meta viewport para accesibilidad en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de usuario - Litterally</title>
    <link rel="stylesheet" href="../css/css_pUsuario.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
    <style>
        /* Estilos adicionales para el progreso */
        .tabs {
            display: flex;
            gap: 10px;
            margin: 30px 0 20px 0;
            border-bottom: 2px solid #ddd;
        }

        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-button.active {
            color: #4CAF50;
            border-bottom-color: #4CAF50;
        }

        .tab-button:hover {
            color: #4CAF50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #e0e0e0 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }

        .stat-label {
            color: #666;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
        }

        .progress-bar-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .progress-bar-container {
            width: 100%;
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            transition: width 0.3s ease;
        }

        .activity-item {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #2196F3;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .activity-info {
            flex: 1;
        }

        .activity-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .activity-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            margin-right: 8px;
        }

        .activity-score {
            text-align: right;
        }

        .score-number {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
        }

        .score-label {
            font-size: 12px;
            color: #666;
        }

        .badge-complete {
            background: #4CAF50;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-pending {
            background: #ff9800;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .jane-eyre-activities {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .activity-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .activity-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .activity-card a {
            color: white;
            text-decoration: none;
            display: inline-block;
            background: #4CAF50;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .activity-card a:hover {
            background: #45a049;
        }

        .activity-card-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .activity-card h4 {
            color: #333;
            margin: 10px 0;
            font-size: 16px;
        }
    </style>
</head>

<body>

    <a href="#main" class="skip-link">Saltar a contenido principal</a>

    <header>
        <!-- Logo con texto alternativo para lectores de pantalla -->
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

            <!-- SECCIÓN DE PROGRESO INTEGRADA -->
            <div
                style="margin-top: 30px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">

                <!-- TABS -->
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <div class="tabs">
                        <button class="tab-button active" onclick="cambiarTab('resumen')">📊 Resumen</button>
                        <button class="tab-button" onclick="cambiarTab('actividades')">📝 Jane Eyre</button>
                        <button class="tab-button" onclick="cambiarTab('historial')">📚 Historial</button>
                    </div>
                </div>

                <!-- TAB 1: RESUMEN -->
                <div id="resumen" class="tab-content active" style="padding: 20px;">
                    <h2 style="margin-bottom: 20px; color: #333;">Tu Progreso General</h2>

                    <!-- Barra de progreso principal -->
                    <div class="progress-bar-section">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: bold; color: #333;">Progreso General</span>
                            <span
                                style="font-size: 18px; font-weight: bold; color: #4CAF50;"><?php echo $porcentaje; ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?php echo $porcentaje; ?>%;">
                                <?php if ($porcentaje > 15)
                                    echo $porcentaje . '%'; ?>
                            </div>
                        </div>
                        <p style="color: #666; font-size: 13px; margin-top: 10px;">
                            <?php echo ($stats['completadas'] ?? 0) . " de " . ($stats['total_actividades'] ?? 0) . " actividades completadas"; ?>
                        </p>
                    </div>

                    <!-- Estadísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Actividades Completadas</div>
                            <div class="stat-value"><?php echo ($stats['completadas'] ?? 0); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Total de Intentos</div>
                            <div class="stat-value"><?php echo ($stats['total_actividades'] ?? 0); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Puntuación Promedio</div>
                            <div class="stat-value">
                                <?php echo ($stats['puntuacion_promedio'] ?? 0) > 0 ? round($stats['puntuacion_promedio']) : '—'; ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Mejor Puntuación</div>
                            <div class="stat-value">
                                <?php echo ($stats['puntuacion_maxima'] ?? 0) > 0 ? $stats['puntuacion_maxima'] : '—'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Progreso por tipo -->
                    <?php if (!empty($progreso_por_tipo)): ?>
                        <h3 style="margin-top: 30px; margin-bottom: 15px; color: #333;">Progreso por Tipo de Actividad</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                            <?php foreach ($progreso_por_tipo as $tipo): ?>
                                <div
                                    style="background: #f9f9f9; padding: 12px; border-radius: 6px; border-left: 3px solid #4CAF50;">
                                    <div style="font-weight: 600; color: #333; margin-bottom: 8px; text-transform: capitalize;">
                                        <?php echo htmlspecialchars($tipo['tipo'] ?? 'Sin tipo'); ?>
                                    </div>
                                    <div style="font-size: 12px; color: #666; margin: 4px 0;">
                                        <strong><?php echo $tipo['total']; ?></strong> total
                                    </div>
                                    <div style="font-size: 12px; color: #666; margin: 4px 0;">
                                        <strong><?php echo ($tipo['completadas'] ?? 0); ?></strong> completadas
                                    </div>
                                    <div style="font-size: 12px; color: #666;">
                                        Promedio:
                                        <strong><?php echo $tipo['promedio'] > 0 ? round($tipo['promedio']) : '—'; ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: JANE EYRE ACTIVIDADES -->
                <div id="actividades" class="tab-content" style="padding: 20px;">
                    <h2 style="margin-bottom: 20px; color: #333;">Actividades de Jane Eyre</h2>
                    <p style="color: #666; margin-bottom: 20px;">Explora y completa estas actividades para aprender más
                        sobre Jane Eyre:</p>

                    <div class="jane-eyre-activities">
                        <div class="activity-card">
                            <div class="activity-card-icon">📋</div>
                            <h4>Tests</h4>
                            <p style="font-size: 13px; color: #666;">Prueba tu comprensión</p>
                            <a href="works/eyre_content.php/test.php">Ir a Tests</a>
                        </div>

                        <div class="activity-card">
                            <div class="activity-card-icon">✏️</div>
                            <h4>Rellenar</h4>
                            <p style="font-size: 13px; color: #666;">Completa los espacios</p>
                            <a href="works/eyre_content.php/rellenar.php">Ir a Rellenar</a>
                        </div>

                        <div class="activity-card">
                            <div class="activity-card-icon">📝</div>
                            <h4>Desarrollar</h4>
                            <p style="font-size: 13px; color: #666;">Escribe tus respuestas</p>
                            <a href="works/eyre_content.php/desarrollar.php">Ir a Desarrollar</a>
                        </div>

                        <div class="activity-card">
                            <div class="activity-card-icon">🎴</div>
                            <h4>Flashcards</h4>
                            <p style="font-size: 13px; color: #666;">Aprende con tarjetas</p>
                            <a href="works/eyre_content.php/flashcard.php">Ir a Flashcards</a>
                        </div>

                        <div class="activity-card">
                            <div class="activity-card-icon">🎮</div>
                            <h4>Juegos</h4>
                            <p style="font-size: 13px; color: #666;">Aprende jugando</p>
                            <a href="works/eyre_content.php/juegos.php">Ir a Juegos</a>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: HISTORIAL -->
                <div id="historial" class="tab-content" style="padding: 20px;">
                    <h2 style="margin-bottom: 20px; color: #333;">Tu Historial de Actividades</h2>

                    <?php if (empty($progreso)): ?>
                        <div class="empty-state">
                            <div style="font-size: 48px; margin-bottom: 15px;">📚</div>
                            <p style="font-size: 16px; margin-bottom: 20px;">Aún no has completado actividades</p>
                            <p style="color: #999; font-size: 14px;">Completa actividades de Jane Eyre para ver tu historial
                                aquí</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-y: auto; max-height: 500px;">
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
                                            <span class="activity-tag">
                                                <?php echo htmlspecialchars($item['tipo'] ?? 'Sin tipo'); ?>
                                            </span>
                                            <span class="activity-tag" style="background: #fff3e0; color: #e65100;">
                                                <?php echo htmlspecialchars($item['nivel'] ?? 'Normal'); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div class="activity-score">
                                            <div class="score-number"><?php echo $item['puntuacion']; ?></div>
                                            <div class="score-label">puntos</div>
                                        </div>
                                        <span class="badge-<?php echo ($item['completado'] == 1) ? 'complete' : 'pending'; ?>">
                                            <?php echo ($item['completado'] == 1) ? '✓ Completada' : 'Pendiente'; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <a id="progreso" href="#" target="_blank" style="display: none;"></a>
        </div>
    </main>

    <footer>
        <p>TFM – Letras Digitales – UCM</p>
    </footer>

    <script>
        // Función para cambiar entre tabs
        function cambiarTab(tabName) {
            // Ocultar todos los tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Desactivar todos los botones
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => {
                btn.classList.remove('active');
            });

            // Mostrar el tab seleccionado
            document.getElementById(tabName).classList.add('active');

            // Activar el botón seleccionado
            event.target.classList.add('active');
        }
    </script>

</body>

</html>