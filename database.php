<?php

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$database = "litterally";

// Create connection with error handling
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    // Log error server-side (don't expose details to users)
    error_log("Database connection failed: " . $conn->connect_error);
    // Show generic error message to user
    die("Error de conexión a la base de datos. Por favor, intenta más tarde.");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

?>