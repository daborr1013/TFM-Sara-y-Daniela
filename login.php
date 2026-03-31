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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            width: 120px;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-size: 14px;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-button:hover {
            background-color: #45a049;
        }
        
        .login-button:active {
            transform: scale(0.98);
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .signup-link p {
            color: #666;
            font-size: 14px;
        }
        
        .signup-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-link a:hover {
            color: #4CAF50;
        }
    </style>
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
</body>
</html>
