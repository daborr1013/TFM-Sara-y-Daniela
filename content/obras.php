<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Litterally</title>
    <link rel="stylesheet" href="../css/css_index.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>
<body>

<header>
    <a href="../index.php"><img class="logo" src="../media/images/litGrande.png"></a>
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
        <li><a href="litto.php">Asistente virtual</a></li>
        <li><a href="pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <section class="hero">
        <h2>Catálogo de obras disponibles</h2>
    </section>

    <hr>

    <section class="pjustificado">
        <p>Este espacio ha sido diseñado para que tecnología y literatura clásica puedan fusionarse y así
        proporcionar a los lectores una mayor comprensión sobre las obras que leen y darles una nueva visión 
        crítica sobre ellas.</p>
        
        <p>Haciendo click en la portada del libro, no solo encontrarás los textos, también verás una pequeña introducción de la historia, 
        nuestra sección de resúmenes, el contexto tanto histórico como el personal del autor/a, explicaciones relevantes, análisis de personajes y 
        de símbolos, glorarios, citas célebres y mapas interactivos, además de actividades que ayuden a mejorar tu comprensión lectora.</p>
        
        <p>Aquí puedes ver las obras que tenemos disponibles en el momento. Estamos trabajando para poder proporcionar más obras y materiales educativos 
        de ellas, ¡mantente atento a las novedades!</p> 
    </section>

    <table>
        <tr><img class="portada2" src="../media/images/prox.png" alt="Próximamente en Litterally"></tr>
        <tr>
            <a id="j_eyre" href="works/eyre.php" target="_blank">
                <img class="portada" src="../media/images/portadaJane.png" alt="Portada de Jane Eyre">
            </a>
        </tr>
        <tr><img class="portada2" src="../media/images/prox.png" alt="Próximamente en Litterally"></tr>
    </table>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

</body>
</html>




