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
    <div class="card-container">
        <div class="user-card">
            <div class="card-header">
                <h1>LITTERALLY ID CARD</h1>
                <div class="library-badge">Litterally.com</div>
            </div>

            <div class="card-content">
                <div class="card-left">
                    <div class="profile-picture">
                        <img src="../media/images/pfp.jpg" alt="Foto de perfil">
                    </div>
                    <div class="user-info">
                        <div class="info-field">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value">Nombre Apellidos</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">ID:</span>
                            <span class="info-value">12345A</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Miembro desde:</span>
                            <span class="info-value">2026</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Edad:</span>
                            <span class="info-value">25 años</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Correo:</span>
                            <span class="info-value">correo@ucm.es</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Favoritas:</span>
                            <span class="info-value">Jane Eyre, Orgullo y prejuicio, Cumbres borrascosas</span>
                        </div>
                        <div class="info-field">
                            <span class="info-label">Géneros:</span>
                            <span class="info-value">Terror, Romance</span>
                        </div>
                    </div>
                </div>

                <div class="card-right">
                    <div class="barcode">
                        <img src="../media/images/barcode.avif" alt="Código de barras">
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button class="edit-profile">Editar perfil</button>
            </div>
        </div>

        <a id="progreso" href="#" target="_blank">
            <img class="progreso-img" src="../media/images/libro.avif" alt="Icono de libro">
        </a>
    </div>
</main>

<footer>
    <p>TFM – Letras Digitales – UCM</p>
</footer>

</body>
</html>




