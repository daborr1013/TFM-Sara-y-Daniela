<?php

// Configuración de la base de datos
$host = "localhost";
$user = "root";
$password = "";
$database = "litterally";

// Crear conexión con manejo de errores
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    // Registrar error del lado del servidor (no exponer detalles al usuario)
    error_log("Fallo de conexión a la base de datos: " . $conn->connect_error);
    // Lanzar excepción en lugar de morir abruptamente
    throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Establecer charset a UTF-8
$conn->set_charset("utf8mb4");