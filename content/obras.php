<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <!-- Meta viewport para accesibilidad en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litterally</title>
    <link rel="stylesheet" href="../css/css_litterally.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
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
        </ul>
    </nav>

    <main id="main">
        <section class="hero">
            <!-- Encabezado principal h1 para mejor jerarquía semántica -->
            <h1>Catálogo de obras disponibles</h1>
        </section>

        <section class="pjustificado">
            <p>Este espacio ha sido diseñado para que tecnología y literatura clásica puedan fusionarse y así
                proporcionar a los lectores una mayor comprensión sobre las obras que leen y darles una nueva visión
                crítica sobre ellas.</p>

            <p>Haciendo click en la portada del libro, no solo encontrarás los textos, también verás una pequeña
                introducción de la historia,
                nuestra sección de resúmenes, el contexto tanto histórico como el personal del autor/a, explicaciones
                relevantes, análisis de personajes y
                de símbolos, glorarios, citas célebres y mapas interactivos, además de actividades que ayuden a mejorar
                tu comprensión lectora.</p>

            <p>Aquí puedes ver las obras que tenemos disponibles en el momento. Estamos trabajando para poder
                proporcionar más obras y materiales educativos
                de ellas, ¡mantente atento a las novedades!</p>
        </section>

        <div class="book-gallery">
            <div class="book-item upcoming">
                <img class="portada2" src="../media/images/prox.png" alt="Próximamente en Litterally">
            </div>
            <div class="book-item">
                <a id="j_eyre" href="works/eyre.php" target="_blank">
                    <img class="portada" src="../media/images/portadaJane.png" alt="Portada de Jane Eyre">
                </a>
            </div>
            <div class="book-item upcoming">
                <img class="portada2" src="../media/images/prox.png" alt="Próximamente en Litterally">
            </div>
        </div>
    </main>

    <footer>
        <p>TFM – Letras Digitales – UCM</p>
    </footer>

</body>

</html>