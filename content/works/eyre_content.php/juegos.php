<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Litterally-Eyre-Juegos</title>
    <link rel="stylesheet" href="../../../css/css_eyre.css">
    <link rel="icon" href="../../../media/images/iconoPestanaClara.png">
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
            <h1>Juegos</h1>
        </section>

        <div class="layout">
            <div class="sidebar">
                <nav class="navbar-sidebar">
                    <ul class="menu-sidebar">
                        <li><a class="active" href="inicio_eyre.php">Inicio</a></li>

                        <li><a href="intro_obra.php">Introducción a la obra</a></li>

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
                                <li><a href="mapa.php">Mapa</a></li>
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
                    </ul>
                </nav>
            </div>

            <section class="game-hub">
                <style>
                    .hub-container {
                        display: flex;
                        gap: 30px;
                        justify-content: center;
                        margin-top: 40px;
                        flex-wrap: wrap;
                    }
                    .game-card {
                        background: #f8f3eb;
                        border-radius: 12px;
                        padding: 40px 30px;
                        text-align: center;
                        width: 320px;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                        cursor: pointer;
                        text-decoration: none;
                        color: inherit;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        border: 2px solid transparent;
                    }
                    .game-card:hover, .game-card:focus {
                        transform: translateY(-5px);
                        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                        border-color: #6A4C93;
                        outline: none;
                    }
                    .game-card img {
                        width: 100px;
                        height: 100px;
                        margin-bottom: 20px;
                        object-fit: cover;
                        border-radius: 50%;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                    }
                    .game-card h2 {
                        color: #6A4C93;
                        font-size: 1.5em;
                        margin-bottom: 15px;
                    }
                    .game-card p {
                        color: #555;
                        font-size: 1.1em;
                        line-height: 1.5;
                    }
                </style>
                <div class="hub-container">
                    <a href="juegos_interactivos.php" class="game-card">
                        <img src="../../../media/images/actividades.png" alt="Icono de Juegos Interactivos">
                        <h2>Juegos Interactivos</h2>
                        <p>Actividades y ejercicios variados sobre la obra.</p>
                    </a>
                    <a href="juego_memoria.php" class="game-card">
                        <img src="../../../media/images/jane.png" alt="Icono de Memoria">
                        <h2>Juego de Memoria</h2>
                        <p>Encuentra las parejas de los personajes principales.</p>
                    </a>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>TFM - Letras Digitales - UCM</p>
    </footer>

</body>

</html>