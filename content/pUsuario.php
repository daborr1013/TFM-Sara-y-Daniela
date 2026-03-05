<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Litterally</title>
    <link rel="stylesheet" href="../css/css_pUsuario.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>
<body>

<header>
    <img class="logo" src="../media/images/litGrande.png">
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
        <li><a href="#">Asistente virtual</a></li>
        <li><a href="tfm.php">Sobre este proyecto</a></li>
        <li><a href="pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <div class="user-card">
        <div class="profile-picture">
            <img src="../media/images/pfp.jpg" alt="Foto de perfil">
        </div>
        <div class="user-info">
            <h2>Nombre de usuario: Nombre Apellidos.</h2>
            <p>Id usuario: 12345A.</p>
            <p>Miembro desde: 2026.</p>
            <p>Edad: 25 años.</p>
            <p>Correo electrónico: correo@ucm.es.</p>
            <p>Obras favoritas: Jane Eyre, Orgullo y prejuicio, Cumbres borrascosas.</p>
            <p>Géneros literarios favoritos: terror, romance.</p>
        </div>
        <div class="edit-profile">
            <button>Editar perfil</button>
        </div>
        <div class="barcode">
            <img src="../media/images/barcode.avif" alt="Código de barras">
        </div>
    </div>

        <a id="progreso" href="#" target="_blank">
            <img class="progreso-img" src="../media/images/libro.avif" alt="Icono de libro">
        </a>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

</body>
</html>




