<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Meta viewport para accesibilidad en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litterally</title>
    <link rel="stylesheet" href="../css/css_litto.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>
<body>

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
        <li><a href="litto.php">Asistente virtual</a></li>
        <li><a href="pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main>
    <div class="chat-container">
    <div class="chat-header">
        <h2>Litto</h2>
    </div>
    <div class="chat-box" id="chatBox">
        <!-- Los mensajes aparecerán aquí -->
        <div class="message bot">
            <p>¡Hola, soy Litto! Escribe un mensaje.</p>
        </div>
    </div>
    <form class="chat-input-area" id="chatForm">
        <input type="text" id="messageInput" placeholder="Escribe tu mensaje..." required>
        <button type="submit">Enviar</button>
    </form>
</div>

<script src="litto.js"></script>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

</body>
</html>

