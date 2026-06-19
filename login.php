<?php
// Inicia la sesión
session_start();

// Si el usuario ya está logueado, redirigir a página de perfil
if (isset($_SESSION['user_id'])) {
    header("Location: content/pUsuario.php");
    exit;
}

// Variables para mensajes de error
$error = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'database.php';
    
    $email = $_POST['email'];
    $contraseña = $_POST['contraseña'];
    
    // Validación básica
    if (empty($email) || empty($contraseña)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Buscar el usuario en la base de datos
        $sql = "SELECT id, nombre, email, contraseña FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar la contraseña
                // En producción, usar password_verify() con contraseñas hasheadas
                if ($contraseña === $user['contraseña']) {
                    // Login exitoso - guardar datos en sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nombre'] = $user['nombre'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Redirigir al perfil de usuario
                    header("Location: content/pUsuario.php");
                    exit;
                } else {
                    $error = "Contraseña incorrecta.";
                }
            } else {
                $error = "El correo no está registrado.";
            }
            
            $stmt->close();
        } else {
            $error = "Error en la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Litterally</title>
    <link rel="icon" href="media/images/iconoPestanaClara.png">
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <a href="index.php">
                <img src="media/images/litGrande.png" alt="Litterally">
            </a>
            <h1>Iniciar Sesión</h1>
            <p>Bienvenido a Litterally</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="tu@correo.com" 
                    required
                    autocomplete="email"
                >
            </div>
            
            <div class="form-group">
                <label for="contraseña">Contraseña</label>
                <input 
                    type="password" 
                    id="contraseña" 
                    name="contraseña" 
                    placeholder="Tu contraseña" 
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="login-button">Iniciar Sesión</button>
        </form>
        
        <div class="signup-link">
            <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
    <script src="js/registro.js"></script>
</body>
</html>
