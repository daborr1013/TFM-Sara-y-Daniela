# Sistema de Seguimiento de Progreso - Guía Completa

## 📊 Descripción

He creado un sistema **simple y beginner-friendly** para rastrear el progreso de los usuarios en las actividades. El sistema guarda puntuaciones y marca cuando una actividad está completada.

---

## 📁 Archivos Creados

### 1. **progress-helper.php**
Archivo con funciones útiles para trabajar con el progreso. Contiene:

- `guardarProgreso()` - Guarda o actualiza el progreso
- `obtenerProgresoUsuario()` - Obtiene todas las actividades del usuario
- `obtenerEstadisticas()` - Calcula estadísticas
- `obtenerProgresoPorTipo()` - Agrupa progreso por tipo de actividad
- `calcularPorcentajeProgreso()` - Calcula el %

### 2. **save-progress.php**
Script que recibe datos POST y guarda el progreso. Se usa con AJAX.

### 3. **content/progreso.php**
Página de dashboard donde usuarios ven:
- Progreso general (%)
- Puntuación promedio
- Mejor puntuación
- Historial de actividades
- Estadísticas por tipo

---

## 🔄 Archivos Modificados

### **content/pUsuario.php**
Agregado:
- Estadísticas rápidas en el perfil
- Botón "Ver Mi Progreso"
- Porcentaje completado

---

## 🚀 Cómo Usar

### **Opción 1: Desde una Página HTML/PHP**

```php
<?php
session_start();
require 'database.php';
require 'progress-helper.php';

// Cuando el usuario completa una actividad
$user_id = $_SESSION['user_id'];
$activity_id = 5;
$puntuacion = 85;
$completado = 1;

// Guardar el progreso
guardarProgreso($conn, $user_id, $activity_id, $puntuacion, $completado);

// Redirigir al siguiente paso
header("Location: siguiente-pagina.php");
?>
```

### **Opción 2: Usar con AJAX (Recomendado)**

En tu página HTML/PHP:

```html
<script>
function guardarProgresoDatos(activityId, puntuacion = 0, completado = 1) {
    fetch('../save-progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'activity_id=' + activityId + 
              '&puntuacion=' + puntuacion + 
              '&completado=' + completado
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Progreso guardado');
            alert('¡Actividad completada!');
        } else {
            console.error('Error:', data.error);
        }
    });
}

// Cuando el usuario termina una actividad
document.getElementById('completar-btn').addEventListener('click', function() {
    guardarProgresoDatos(5, 90, 1);
});
</script>
```

### **Opción 3: Obtener el Progreso**

```php
<?php
require 'database.php';
require 'progress-helper.php';

$user_id = $_SESSION['user_id'];

// Obtener todo el progreso del usuario
$progreso = obtenerProgresoUsuario($conn, $user_id);

// Obtener estadísticas
$stats = obtenerEstadisticas($conn, $user_id);
$porcentaje = calcularPorcentajeProgreso($stats);

// Obtener por tipo
$por_tipo = obtenerProgresoPorTipo($conn, $user_id);

foreach ($progreso as $actividad) {
    echo "Actividad: " . $actividad['activity_id'];
    echo "Puntuación: " . $actividad['puntuacion'];
    echo "Completada: " . ($actividad['completado'] ? 'Sí' : 'No');
}
?>
```

---

## 📊 Estructura de Datos en la BD

### Tabla: `user_progress`

```sql
CREATE TABLE `user_progress` (
  `id` int(11) PRIMARY KEY,              -- ID único
  `user_id` int(11),                     -- ID del usuario
  `activity_id` int(11),                 -- ID de la actividad
  `puntuacion` int(11),                  -- 0-100
  `completado` tinyint(1),               -- 1 = completada, 0 = no
  `fecha` timestamp DEFAULT NOW()        -- Cuándo se hizo
);
```

---

## 🎯 Ejemplos Prácticos

### Ejemplo 1: Guardar progreso desde un Quiz

```php
<?php
session_start();
require 'database.php';
require 'progress-helper.php';

// El usuario termina el quiz
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $respuestas_correctas = 8;
    $total_preguntas = 10;
    $puntuacion = round(($respuestas_correctas / $total_preguntas) * 100);
    
    // Guardar progreso
    guardarProgreso($conn, $user_id, 3, $puntuacion, 1);
    
    echo "¡Quiz completado! Puntuación: $puntuacion%";
}
?>
```

### Ejemplo 2: Mostrar Progreso en una Tarjeta

```php
<?php
$stats = obtenerEstadisticas($conn, $_SESSION['user_id']);
$porcentaje = calcularPorcentajeProgreso($stats);
?>

<div class="progress-card">
    <h3>Tu Progreso</h3>
    <div class="progress-bar">
        <div style="width: <?php echo $porcentaje; ?>%;">
            <?php echo $porcentaje; ?>%
        </div>
    </div>
    <p>
        <?php echo ($stats['completadas'] ?? 0) . " de " . ($stats['total_actividades'] ?? 0); ?> 
        actividades completadas
    </p>
</div>
```

### Ejemplo 3: Registrar que una Flashcard se completó

```javascript
// JavaScript en la página de flashcards
const flashcardBtn = document.getElementById('flashcard-complete');

flashcardBtn.addEventListener('click', function() {
    // Guardar que la actividad 2 se completó con 100 puntos
    fetch('save-progress.php', {
        method: 'POST',
        body: new FormData(),
        body: 'activity_id=2&puntuacion=100&completado=1'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('¡Flashcard completada! Ve a ver tu progreso');
        }
    });
});
```

---

## 📈 Cómo Ver el Progreso del Usuario

**Opción 1: Dashboard Completo**
```
Ir a: http://localhost/TFM-Sara-y-Daniela/content/progreso.php
```

**Opción 2: Resumen en el Perfil**
```
Ir a: http://localhost/TFM-Sara-y-Daniela/content/pUsuario.php
→ Se muestra un resumen rápido
```

---

## ⚙️ Flujo Completo

```
Usuario completa una actividad
         ↓
save-progress.php recibe los datos
         ↓
guardarProgreso() inserta en usuario_progress
         ↓
Usuario puede ver su progreso en progreso.php
```

---

## 💡 Tips Beginner-Friendly

### 1. **Para cada tipo de actividad, usa IDs diferentes**
```php
// Flashcard = activity_id 1
// Quiz = activity_id 2
// Juego = activity_id 3
```

### 2. **Puntuación = número 0-100**
```php
// 0 = no intentado
// 50 = intento parcial
// 100 = completado perfecto
```

### 3. **Completado = 0 o 1**
```php
// 0 = el usuario lo intentó pero no terminó
// 1 = el usuario lo completó
```

### 4. **Siempre verifica que el usuario esté logueado**
```php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
```

---

## ❓ Preguntas Frecuentes

**P: ¿Cuándo debo guardar el progreso?**
R: Cuando el usuario TERMINA una actividad (responde un quiz, termina flashcards, etc.)

**P: ¿Puedo actualizar el progreso múltiples veces?**
R: Sí. `guardarProgreso()` actualiza si ya existe, o crea uno nuevo si no existe.

**P: ¿Cómo muestro solo actividades completadas?**
R: En la BD: `SELECT * FROM user_progress WHERE completado = 1`

**P: ¿Puedo ver el progreso de otros usuarios?**
R: No, por seguridad. Solo ves tu propio progreso.

---

## 🔐 Seguridad

El sistema ya verifica:
- ✅ El usuario debe estar logueado
- ✅ Solo guarda el progreso del usuario logueado
- ✅ Valida que puntuación sea 0-100

---

## 📱 Acceso Rápido

- **Perfil con Resumen**: `/content/pUsuario.php`
- **Dashboard Completo**: `/content/progreso.php`
- **Guardar Progreso**: `/save-progress.php` (POST)
- **Funciones Útiles**: `/progress-helper.php`

¡Listo para usar! 🚀
