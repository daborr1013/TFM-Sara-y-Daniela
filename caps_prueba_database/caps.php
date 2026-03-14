<?php
require_once '../database.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection error: " . ($conn ? $conn->connect_error : "Connection not established"));
}

$query = "SELECT id, titulo FROM blocks";
$result = $conn->query($query);

if (!$result) {
    die("Query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Litterally-Eyre-Capítulos</title>
    <link rel="stylesheet" href="../css/css_eyre.css">
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
            <a href="../content/obras.php">Obras</a>
            <ul class="dropdown-menu">
                <li><a href="../content/works/eyre.php">Jane Eyre</a></li>
            </ul>
        </li>

        <li><a href="../content/about_us.php">Sobre nosotras</a></li>
        <li><a href="../content/litto.php">Asistente virtual</a></li>

        <li><a href="../content/pUsuario.php">Perfil de usuario</a></li>
    </ul>
</nav>

<main id="main">
    <section class="hero">
        <h1>Capítulos</h1>
    </section>

    <div class="layout">
        <div class="sidebar">
                <nav class="navbar-sidebar">    
                    <ul class="menu-sidebar">
                        <li><a href="../content/works/eyre_content.php/inicio_eyre.php">Inicio</a></li>

                        <li><a href="../content/works/eyre_content.php/intro_obra.php">Introducción a la obra</a></li>

                        <li class="dropdown-sidebar">
                            <a href="../content/works/eyre_content.php/contenido_eyre.php">Contenido</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="../content/works/eyre_content.php/resumenes/resumenes.php">Resúmenes</a></li>
                                <li><a class="active" href="../content/works/eyre_content.php/capitulos.php">Capítulos</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="../content/works/eyre_content.php/contexto_eyre.php">Contexto</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="../content/works/eyre_content.php/charlotte.php">Charlotte Brontë</a></li>
                                <li><a href="../content/works/eyre_content.php/contexto_historico.php">Contexto histórico</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="../content/works/eyre_content.php/recursos_eyre.php">Recursos</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="../content/works/eyre_content.php/explicaciones.php">Explicaciones</a></li>
                                <li><a href="../content/works/eyre_content.php/simbolos.php">Símbolos</a></li>
                                <li><a href="../content/works/eyre_content.php/personajes.php">Personajes</a></li>
                                <li><a href="../content/works/eyre_content.php/glosario.php">Glosario</a></li>
                                <li><a href="../content/works/eyre_content.php/mapa.php">Mapa</a></li>
                                <li><a href="../content/works/eyre_content.php/citas.php">Citas</a></li>
                            </ul>
                        </li>

                        <li class="dropdown-sidebar">
                            <a href="../content/works/eyre_content.php/actividades_eyre.php">Actividades</a>
                            <ul class="dropdown-menu-sidebar">
                                <li><a href="../content/works/eyre_content.php/test.php">Tests</a></li>
                                <li><a href="../content/works/eyre_content.php/rellenar.php">Rellenar</a></li>
                                <li><a href="../content/works/eyre_content.php/flascard.php">Flashcards</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>

        <section class="pjustificado">
            <section>
                <p>En esta sección se encuentran todos los capítulos de <i>Jane Eyre</i> desde la base de datos.</p>
            </section>
            <section class="menu">
                <h4>Capítulos disponibles</h4>
                <section class="layout">
<?php
$count = 0;
while ($row = $result->fetch_assoc()) {
    if ($count % 2 == 0 && $count > 0) {
        echo "</section><section>";
    }
    if ($count % 2 == 0) {
        echo "<section>";
    }
    echo "<a class='boton' href='cap.php?id=".$row['id']."'>".$row['titulo']."</a>";
    $count++;
}
if ($count > 0) {
    echo "</section>";
}
?>
                </section>
            </section>
        </section>
    </div>
</main>

<footer></footer>
</body>
</html>