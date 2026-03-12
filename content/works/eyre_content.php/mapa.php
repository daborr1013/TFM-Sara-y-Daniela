<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litterally-Eyre-Mapa</title>
    <link rel="stylesheet" href="../../../css/css_eyre.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" type="text/css" href="../../../css/css_mapa.css">
    <link rel="icon" href="../../../media/images/iconoPestanaClara.png">
</head>
<body>

<header>
    <a href="../../../index.php"><img class="logo" src="../../../media/images/litGrande.png"></a>
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
        <li><a href="../../litto.php">Asistente virtual</a></li>
        <li><a href="../../pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <section class="hero">
        <h2>Mapa</h2>
        <p>Obra esencial de la literatura inglesa del siglo XIX.</p>
    </section>

    <div class="layout">
        <div class="sidebar">
                <nav class="navbar-sidebar">    
                    <ul class="menu-sidebar">
                        <li><a class="active" href="inicio_eyre.php">Inicio</a></li>

                        <li><a href="intro_obra.php">Introducción a la obra</a></li>

                        <li class="dropdown-sidebar">
                            <a href="contenido_eyre.php">Contenido</a>
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
                                <li><a href="explicaciones.php">Explicaciones</a></li>
                                <li><a href="simbolos.php">Símbolos</a></li>
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
                                <li><a href="flascard.php">Flashcards</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>

        <section class="pjustificado">
            <button id="resetBtn" class="reset-button" title="Reset map to original view">↻</button>
            <div id="map"></div>
        </section>
    </div>
</main>

<footer>
    <p>TFM - Letras Digitales - UCM</p>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="../../../js/mapa.js"></script>

</body>
</html>









