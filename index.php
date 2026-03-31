<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Meta viewport para accesibilidad en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litterally</title>
    <link rel="stylesheet" href="css/css_litterally.css">
    <link rel="icon" href="media/images/iconoPestanaClara.png">
</head>
<body>
    <a href="#main" class="skip-link">Saltar a contenido principal</a>
    <header>
        <!-- Logo con texto alternativo para lectores de pantalla -->
        <!-- Lazy-loading agregado para mejorar rendimiento de carga inicial -->
        <a href="index.php"><img class="logo" src="media/images/litGrande.png" alt="Litterally - Inicio" loading="lazy"></a>
    </header>

    <nav class="navbar">
        <ul class="menu">
            <li><a href="index.php">Inicio</a></li>

            <li class="dropdown">
                <a href="content/obras.php">Obras</a>
                <ul class="dropdown-menu">
                    <li><a href="content/works/eyre.php"><i>Jane Eyre</i></a></li>
                </ul>
            </li>

            <li><a href="content/about_us.php">Sobre nosotras</a></li>
            <li><a href="content/litto.php">Asistente virtual</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="content/pUsuario.php">Perfil de usuario</a></li>
                <li><a href="content/progreso.php">Mi Progreso</a></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar sesión</a></li>
                <li><a href="register.php">Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main>
        <section class="hero">
            <!-- Encabezado principal (h1) para mejor jerarquía de contenido -->
            <h1>Inicio</h1>
        </section>
        <section class="pjustificado">
            <p>Bienvenido a <i>Litterally</i>, una plataforma en la que puedes explorar y analizar obras literarias de forma interactiva.</p>

            <p>Esta página nace para transformar las lecturas pasivas en una experiencia inmersiva que te ayude a comprender las obras 
            propuestas profundamente.</p>

            <p>Frente a lecturas superficiales ayudadas con IA o resúmenes genéricos que te cuenten solo lo que pasa, pero no "<i>lo que sucede</i>", 
            aquí el lector es el protagonista de su propio proceso interpretativo</p>

            <p>Aquí podrás encontrar contenidos de análisis literario, como explicaciones de personajes o simbología, actividades de comprensión
            lectora, flashcards, un panel de lector...</p>
            
            <p>Actualmente, esta plataforma cuenta con la novela Jane Eyre, pero esperamos poder ir actualizando 
            la página con más obras para que aprendas con ellas</p>

            <p>Para saber más sobre nosotras, ve a "<a href="content/about_us.php">Sobre nosotras</a>". ¡Disfruta, querido lector!</p>
        </section>

        <!-- Botón accesible con aria-label descriptivo -->
        <a href="content/works/eyre.php" class="boton" role="button" aria-label="Acceder a la lectura interactiva de Jane Eyre">Accede a Jane Eyre</a>
        <a href="memory/index.php" class="boton" role="button" aria-label="Acceder al juego de memoria">Prueba del memory luego lo quito cuando esté bien</a>
    </main>

    <footer>
        <p>TFM – Letras Digitales – UCM</p>
    </footer>

</body>
</html>

<?php



