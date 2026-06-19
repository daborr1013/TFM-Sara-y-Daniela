<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <!-- Meta viewport para accesibilidad en dispositivos móviles -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Memory Jane Eyre - Juego de Memoria</title>
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="../media/images/iconoPestanaClara.png">
    </head>
    <body>
        <!-- Skip link para accesibilidad - permite saltar navegación -->
        <a href="#main" class="skip-link">Saltar a contenido principal</a>

        <header>
            <!-- Logo con texto alternativo para lectores de pantalla -->
            <a href="../index.php"><img class="logo" src="../media/images/litGrande.png" alt="Litterally - Inicio" loading="lazy"></a>
        </header>

        <nav class="navbar" aria-label="Navegación principal">
            <ul class="menu">
                <li><a href="../index.php">Inicio</a></li>
                <li class="dropdown">
                    <a href="../content/obras.php">Obras</a>
                    <ul class="dropdown-menu">
                        <li><a href="../content/works/eyre.php"><i>Jane Eyre</i></a></li>
                    </ul>
                </li>
                <li><a href="../content/about_us.php">Sobre nosotras</a></li>
                <li><a href="../content/litto.php">Asistente virtual</a></li>
                <li><a href="../content/pUsuario.php">Perfil de usuario</a></li>
            </ul>
        </nav>

        <main id="main" role="main" aria-label="Juego de memoria Jane Eyre">
            <h1>Juego de Memoria - Jane Eyre</h1>
            <div class="game-info">
                <p id="game-status" role="status" aria-live="polite" aria-atomic="true">
                    Parejas encontradas: <span id="pairs-count">0</span>/<span id="pairs-total">8</span>
                </p>
                <p class="instructions">Usa las flechas del teclado para navegar y Enter o Espacio para seleccionar cartas.</p>
            </div>
            <div class="memory-game" role="region" aria-label="Tablero de juego de memoria">
                
            </div>
            <button id="restart-btn" class="restart-button" aria-label="Reiniciar el juego">Reiniciar Juego</button>
        </main>

        <footer>
            <p>TFM – Letras Digitales – UCM</p>
        </footer>

        <script src="script.js"></script>
    </body>

</html>