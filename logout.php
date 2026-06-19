<?php
// Destruye la sesión del usuario
session_start();
session_destroy();

// Redirige al inicio
header("Location: index.php");
exit;
