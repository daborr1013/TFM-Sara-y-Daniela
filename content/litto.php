<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Litto - Litterally</title>
    <link rel="stylesheet" href="../css/css_litto.css">
    <link rel="icon" href="../media/images/iconoPestanaClara.png">
</head>

<body>

    <a href="#main" class="skip-link">Saltar a contenido principal</a>

    <header>
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
            <li><a href="progreso.php">Mi Progreso</a></li>
            <li><a href="../logout.php">Cerrar sesión</a></li>
        </ul>
    </nav>

    <main id="main">
        <div class="chat-container">
            <div class="chat-header">
                <h1>Litto</h1>
            </div>

            <div class="chat-box" id="chatBox" aria-live="polite" aria-atomic="false">
                <div class="message bot">
                    <p>¡Hola! Estoy listo para ayudarte con Jane Eyre. Pregúntame por personajes, capítulos, símbolos,
                        temas o contexto histórico.</p>
                </div>
            </div>

            <form class="chat-input-area" id="chatForm">
                <label for="messageInput" class="sr-only">Mensaje de chat</label>
                <input type="text" id="messageInput" placeholder="Escribe tu pregunta..." required
                    aria-label="Mensaje de chat">
                <button type="submit">Enviar</button>
            </form>
        </div>

        <script src="../js/js_litto.js"></script>
    </main>

    <footer>
        <p>TFM - Letras Digitales - UCM</p>
    </footer>

</body>

</html>