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
                <li><a href="content/works/eyre.php"><i>Jane Eyre</i></a></li>
            </ul>
        </li>

        <li><a href="content/about_us.php">Sobre nosotras</a></li>
        <li><a href="content/litto.php">Asistente virtual</a></li>
        <li><a href="content/pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <section class="hero">
        <h2>Inicio</h2>
    </section>
    <section class="pjustificado">
        <p>Bienvenido a <i>Litterally</i>, una plataforma en la que puedes explorar y analizar obras literarias de forma interactiva.</p>

        <p>Esta página nace para tranformar las lecturas pasivas en una experiencia inmersiva que te ayude a comprender las obras 
        propuestas profundamente.</p>

        <p>Frente a lecturas superficiales ayudadas con IA o resúmenes genéricos que te cuenten solo lo que pasa, pero no "<i>lo que sucede</i>", 
        aquí el lector es el protagonista de su propio proceso interpretativo</p>

        <p>Aquí podrás encontrar contenidos de análisis literario, como explicaciones de personajes o simbología, actividades de comprensión
        lectora, flashcards, un panel de lector...</p>
        
        <p>Actualmente, esta plataforma cuenta con la novela Jane Eyre, pero esperamos poder ir actualizando 
        la página con más obras para que aprendas con ellas</p>

        <p>Para saber más sobre nosotras, ve a "<a href="content/about_us.php">Sobre nosotras</a>". ¡Disfruta, querido lector!</p>
    </section>

    <button class="boton">
        <a href="content/works/eyre.php">Accede a Jane Eyre</a>
    </button>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

<audio autoplay loop src="media/audio/musicota.mp3"></audio>

</body>
</html>

<?php



