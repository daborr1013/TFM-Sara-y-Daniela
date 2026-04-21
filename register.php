<?php
// Inicia la sesión
session_start();

// Si el usuario ya está logueado, redirigir a página de perfil
if (isset($_SESSION['user_id'])) {
    header("Location: content/pUsuario.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'database.php';
    
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $contraseña = $_POST['contraseña'];
    $confirmar_contraseña = $_POST['confirmar_contraseña'];
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($contraseña) || empty($confirmar_contraseña)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres.";
    } elseif (strlen($contraseña) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($contraseña !== $confirmar_contraseña) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el correo ya existe
        $sql_check = "SELECT id FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        
        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $error = "Este correo ya está registrado. <a href='login.php'>Inicia sesión aquí</a>";
            } else {
                // Insertar el nuevo usuario
                $sql_insert = "INSERT INTO users (nombre, email, contraseña) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sss", $nombre, $email, $contraseña);
                    
                    if ($stmt_insert->execute()) {
                        $success = "¡Cuenta creada exitosamente! <a href='login.php'>Inicia sesión aquí</a>";
                    } else {
                        $error = "Error al crear la cuenta. Intenta de nuevo.";
                    }
                    
                    $stmt_insert->close();
                } else {
                    $error = "Error en la base de datos.";
                }
            }
            
            $stmt_check->close();
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
    <title>Registrarse - Litterally</title>
    <link rel="icon" href="media/images/iconoPestanaClara.png">
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <a href="index.php">
                <img src="media/images/litGrande.png" alt="Litterally">
            </a>
            <h1>Crear Cuenta</h1>
            <p>Únete a Litterally</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($success)): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    placeholder="Juan Pérez" 
                    required
                    minlength="3"
                >
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="tu@correo.com" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="contraseña">Contraseña</label>
                <input 
                    type="password" 
                    id="contraseña" 
                    name="contraseña" 
                    placeholder="Mínimo 6 caracteres" 
                    required
                    minlength="6"
                >
            </div>
            
            <div class="form-group">
                <label for="confirmar_contraseña">Confirmar Contraseña</label>
                <input 
                    type="password" 
                    id="confirmar_contraseña" 
                    name="confirmar_contraseña" 
                    placeholder="Repite tu contraseña" 
                    required
                    minlength="6"
                >
            </div>
            
            <button type="submit" class="register-button">Crear Cuenta</button>
        </form>
        <?php endif; ?>
        
        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
    <script src="js/registro.js"></script>
</body>
</html>
