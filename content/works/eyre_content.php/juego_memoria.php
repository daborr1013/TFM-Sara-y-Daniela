<?php
session_start();
require '../../../activity-tracker.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Litterally-Eyre-Juego de Memoria</title>
    <link rel="stylesheet" href="../../../css/css_eyre.css">
    <link rel="stylesheet" href="../../../css/css_memory.css">
    <link rel="icon" href="../../../media/images/iconoPestanaClara.png">
    <script src="../../../js/memory_game.js" defer></script>
</head>

<body>

    <a href="#main" class="skip-link">Saltar a contenido principal</a>

    <header>
        <a href="../../../index.php"><img class="logo" src="../../../media/images/litGrande.png"
                alt="Litterally - Inicio"></a>
    </header>

    <nav class="navbar">
        <ul class="menu">
            <li><a href="../../../index.php">Inicio</a></li>

            <li class="dropdown">
                <a href="../../obras.php">Obras</a>
                <ul class="dropdown-menu">
                    <li><a href="../eyre.php">Jane Eyre</a></li>
                </ul>
            </li>

            <li><a href="../../about_us.php">Sobre nosotras</a></li>
            <li><a href="../../litto.php">Litto</a></li>
            <li><a href="../../pUsuario.php">Perfil de usuario</a></li>
        </ul>
    </nav>

    <main id="main">
        <section class="hero">
            <h1>Juego de Memoria</h1>
        </section>

        <div class="layout">
            <div class="sidebar">
                <nav class="navbar-sidebar">
                    <ul class="menu-sidebar">
                        <li><a class="active" href="inicio_eyre.php">Inicio</a></li>

                        <li class="dropdown-sidebar">
                            <a href="contenido_eyre.php">Obra</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="resumenes/resumenes.php">Resúmenes</a></li>
                                <li><a href="capitulos.php">Capítulos</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="contexto_eyre.php">Contexto</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="charlotte.php">Charlotte Brontë</a></li>
                                <li><a href="contexto_historico.php">Contexto histórico</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="recursos_eyre.php">Recursos</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="simbolosTemas.php">Temas y Símbolos</a></li>
                                <li><a href="personajes.php">Personajes</a></li>
                                <li><a href="glosario.php">Glosario</a></li>
                                <li><a href="citas.php">Citas</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="actividades_eyre.php">Actividades</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="test.php">Tests</a></li>
                                <li><a href="rellenar.php">Rellenar</a></li>
                                <li><a href="desarrollar.php">Desarrollar</a></li>
                                <li><a href="flashcard.php">Flashcards</a></li>
                                <li><a href="juegos.php">Juegos</a></li>
                            </ul>
                        </li>

                        <li><a href="externos.php">Enlaces externos</a></li>
                    </ul>
                </nav>
            </div>

            <section class="pjustificado" style="display: flex; flex-direction: column; align-items: center;">
                <?php mostrarWidgetProgreso(); ?>
                <div class="game-info" style="text-align: center; margin-bottom: 20px;">
                    <p style="font-weight: bold; font-size: 1.1em; margin-bottom: 5px;">
                        Parejas encontradas: <span id="pairs-count">0</span>/<span id="pairs-total">8</span>
                    </p>
                    <p id="game-status" role="status" aria-live="polite" aria-atomic="true"
                        style="color: #6A4C93; font-style: italic; min-height: 24px; margin: 5px 0;"></p>
                    <p class="instructions" style="margin-top: 5px;">Usa las flechas del teclado para navegar y Enter o
                        Espacio para seleccionar cartas.</p>
                </div>
                <div class="memory-game" role="region" aria-label="Tablero de juego de memoria">

                </div>
                <div style="margin-top: 20px;">
                    <button id="restart-btn" class="restart-button" aria-label="Reiniciar el juego">Reiniciar
                        Juego</button>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>TFM - Letras Digitales - UCM</p>
    </footer>

    <?php mostrarScriptProgreso(); ?>
</body>

</html>