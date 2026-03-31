# Sistema de Login - Guía de Uso

## 📋 Descripción General

He creado un sistema de login básico y principiante amigable para tu plataforma Litterally. El sistema permite que los usuarios se registren, inicien sesión y accedan a su perfil.

## 📁 Archivos Creados

### 1. **login.php**
- Página de inicio de sesión
- Formulario simple con email y contraseña
- Valida las credenciales contra la base de datos
- Crea una sesión PHP si el login es exitoso
- Redirige al perfil del usuario tras iniciar sesión

### 2. **register.php**
- Página de registro de nuevos usuarios
- Formulario con nombre, email y contraseña (confirmación)
- Validaciones básicas:
  - Nombre mínimo 3 caracteres
  - Contraseña mínima 6 caracteres
  - Las contraseñas deben coincidir
  - Verifica que el email no esté registrado
- Inserta el nuevo usuario en la base de datos

### 3. **logout.php**
- Script simple que destruye la sesión del usuario
- Redirige al inicio después de cerrar sesión

## 🔄 Archivos Modificados

### **index.php**
- Agregado: `session_start()` al inicio
- Modificada la navegación para mostrar:
  - "Iniciar sesión" y "Registrarse" si el usuario NO está logueado
  - "Perfil de usuario" y "Cerrar sesión" si el usuario SÍ está logueado

### **content/pUsuario.php**
- Agregado: Verificación de sesión activa
- Si el usuario no está logueado, redirige a login.php
- Muestra datos reales del usuario desde la base de datos:
  - Nombre completo
  - ID único generado
  - Fecha de registro
  - Email

## 🔐 Cómo Funciona el Sistema

### Flujo de Registro
```
usuario visita register.php 
  ↓
completa el formulario 
  ↓
validaciones de seguridad 
  ↓
se inserta en la tabla `users` 
  ↓
redirige a login.php
```

### Flujo de Login
```
usuario visita login.php 
  ↓
ingresa email y contraseña 
  ↓
se verifica en la tabla `users` 
  ↓
si es correcto → crea sesión 
  ↓
redirige a pUsuario.php
```

### Acceso Protegido
```
usuario visita pUsuario.php 
  ↓
sistema verifica session_start() 
  ↓
si NO está logueado → redirige a login.php 
  ↓
si está logueado → muestra perfil
```

## 🚀 Cómo Usar

### Para crear una cuenta:
1. Haz clic en "Registrarse" en la página de inicio
2. Completa el formulario con:
   - Tu nombre completo
   - Tu email
   - Una contraseña (mínimo 6 caracteres)
3. Haz clic en "Crear Cuenta"
4. Se redirige automáticamente a login.php

### Para iniciar sesión:
1. Haz clic en "Iniciar sesión" en la página de inicio
2. Ingresa tu email y contraseña
3. Haz clic en "Iniciar Sesión"
4. Se abrirá tu perfil automáticamente

### Para cerrar sesión:
1. Haz clic en "Cerrar sesión" en la barra de navegación
2. Se elimina la sesión y regresas al inicio

## 💾 Datos en la Base de Datos

El sistema usa la tabla `users` existente:
```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
);
```

**Nota:** Las contraseñas se guardan en texto plano. Para un proyecto en producción, es **MUY IMPORTANTE** usar `password_hash()` y `password_verify()`.

## ⚠️ Mejoras Futuras (Beginner Friendly Tips)

Para mejorar la seguridad en el futuro, considera:

### 1. **Hashear Contraseñas**
```php
// Al registrar:
$contraseña_hash = password_hash($contraseña, PASSWORD_DEFAULT);

// Al verificar:
if (password_verify($contraseña, $user['contraseña'])) {
    // Login exitoso
}
```

### 2. **Agregar Validación de Email**
- Enviar email de confirmación antes de activar la cuenta

### 3. **Recuperación de Contraseña**
- Crear un formulario para restablecer la contraseña

### 4. **Protección CSRF (Cross-Site Request Forgery)**
```php
// Generar token:
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Incluir en formulario:
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Verificar:
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Token inválido');
}
```

### 5. **Rate Limiting**
- Limitar el número de intentos de login fallidos

## 📧 Ejemplo de Datos de Prueba

Para probar el sistema, puedes insertar un usuario directamente en la base de datos:

```sql
INSERT INTO users (nombre, email, contraseña) 
VALUES ('Juan Pérez', 'juan@example.com', 'password123');
```

Luego puedes iniciar sesión con:
- Email: `juan@example.com`
- Contraseña: `password123`

## ❓ Preguntas Frecuentes

**P: ¿Dónde se guardan los datos de la sesión?**
R: Se guardan en variables `$_SESSION`, que están almacenadas en el servidor (en archivos de sesión).

**P: ¿Durante cuánto tiempo dura la sesión?**
R: Por defecto, 24 minutos de inactividad. Puedes cambiar esto en `php.ini`.

**P: ¿Qué pasa si el usuario cierra el navegador?**
R: La sesión se mantiene hasta que expire o el usuario haga clic en "Cerrar sesión".

**P: ¿Puedo agregar "Recuérdame"?**
R: Sí, usando cookies seguras. Es una mejora futura recomendada.

---

¡Listo para usar! Si tienes preguntas, revisa el código comentado en cada archivo. 🎉
