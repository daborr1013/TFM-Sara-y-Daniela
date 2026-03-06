<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Litterally</title>
    <link rel="stylesheet" href="css/css_index.css">
    <link rel="icon" href="media/images/iconoPestanaClara.png">
</head>
<body>

<header>
    <a href="index.php"><img class="logo" src="media/images/litGrande.png"></a>
</header>

<nav class="navbar">
    <ul class="menu">
        <li><a href="index.php">Inicio</a></li>

        <li class="dropdown">
            <a href="content/obras.php">Obras</a>
            <ul class="dropdown-menu">
                <li><a href="content/works/eyre.php">Jane Eyre</a></li>
            </ul>
        </li>

        <li><a href="content/about_us.php">Sobre nosotras</a></li>
        <li><a href="#">Asistente virtual</a></li>
        <li><a href="content/tfm.php">Sobre este proyecto</a></li>
        <li><a href="content/pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <section class="hero">
        <h2>Inicio</h2>
    </section>
    <section>
        <p>Bienvenido a <i>Litterally</i>, una plataforma en la que puedes explorar y analizar obras literarias de forma interactiva.</p>

        <p>Actualmente, esta plataforma cuenta con la novela Jane Eyre, pero esperamos poder ir actualizando 
            la página con más obras para que aprendas con ellas</p>

        <button class="boton">
          <a href="content/works/eyre.php">Accede a Jane Eyre</a>
        </button>
    </section>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

</body>
</html>

<?php



