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
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        header {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        header img {
            max-width: 120px;
            margin-left: 40px;
        }
        
        nav.navbar {
            background: white;
            padding: 0;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            padding: 0 40px;
            gap: 30px;
        }
        
        nav a {
            color: #333;
            text-decoration: none;
            padding: 15px 0;
            display: block;
        }
        
        nav a:hover {
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
        }
        
        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 32px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            color: #4CAF50;
            font-weight: bold;
        }
        
        .progress-bar-container {
            width: 100%;
            background: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }
        
        .section-title {
            color: #333;
            margin: 40px 0 20px 0;
            font-size: 24px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        .activity-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .activity-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-info {
            flex: 1;
        }
        
        .activity-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .activity-type {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .activity-level {
            display: inline-block;
            background: #fff3e0;
            color: #f57c00;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .activity-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .score {
            text-align: right;
        }
        
        .score-value {
            font-size: 20px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .score-label {
            font-size: 12px;
            color: #666;
        }
        
        .badge-completado {
            background: #4CAF50;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-pendiente {
            background: #ff9800;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #45a049;
        }
        
        .tipo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .tipo-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .tipo-card h4 {
            color: #333;
            margin-bottom: 10px;
            text-transform: capitalize;
        }
        
        .tipo-stat {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin: 5px 0;
        }
        
        footer {
            background: white;
            text-align: center;
            padding: 20px;
            margin-top: 60px;
            border-top: 1px solid #eee;
            color: #666;
        }
    </style>
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
