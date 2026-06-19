# Guía: Integrar Progreso en Jane Eyre - Paso a Paso

## 📌 Resumen Rápido

El progreso ahora está **completamente integrado** en el perfil del usuario (`pUsuario.php`) con:
- ✅ Tab "Resumen" - Estadísticas generales
- ✅ Tab "Jane Eyre" - Enlaces a todas las actividades
- ✅ Tab "Historial" - Todas las actividades completadas

---

## 🎯 Cómo Funciona Ahora

### **1. Página de Perfil (pUsuario.php)**

El usuario logueado ve 3 tabs:

```
📊 RESUMEN          📝 JANE EYRE          📚 HISTORIAL
  └─ Estadísticas      └─ Tests            └─ Todas las
  └─ Progreso          └─ Rellenar            actividades
  └─ Puntuaciones      └─ Desarrollar
                       └─ Flashcards
                       └─ Juegos
```

### **2. Acceso a Actividades**

El usuario puede:
1. Ir a su perfil → Tab "Jane Eyre" → Hacer clic en actividad
2. O navegar directamente desde el menú de Jane Eyre

### **3. Guardar Progreso**

Cuando complete una actividad:
```php
// En la página de la actividad
guardarProgreso(activity_id, puntuacion, "Nombre de Actividad");
```

---

## 🔧 Pasos para Integrar en Tus Actividades de Jane Eyre

### **Paso 1: Incluir el tracker al inicio**

En la parte superior de `test.php`, `rellenar.php`, etc.:

```php
<?php
session_start();
require '../../../../activity-tracker.php';
?>
```

### **Paso 2: Mostrar el widget de progreso**

En el lugar donde quieras mostrar el mensaje de progreso:

```php
<?php mostrarWidgetProgreso(); ?>
```

Esto mostrará:
- Si el usuario está logueado: "Tu progreso se guarda automáticamente"
- Si NO está logueado: "Inicia sesión para rastrear tu progreso"

### **Paso 3: Agregar botón para guardar**

Después de que el usuario complete la actividad:

```php
<?php mostrarBotonProgreso(1, 85, "Test - Capítulo 1"); ?>
```

Parámetros:
- `1` = activity_id (cambiar según la actividad)
- `85` = puntuación (0-100)
- `"Test - Capítulo 1"` = nombre de la actividad

### **Paso 4: Incluir el script JavaScript**

Al final del `<body>`:

```php
<?php mostrarScriptProgreso(); ?>
```

---

## 📝 Ejemplo Completo

### Para un Test (test.php):

```php
<?php
session_start();
require '../../../../activity-tracker.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test - Jane Eyre</title>
</head>
<body>
    <h1>Test de Comprensión</h1>
    
    <!-- Mostrar widget -->
    <?php mostrarWidgetProgreso(); ?>
    
    <!-- Tu contenido del test aquí -->
    <div class="test-content">
        <p>Pregunta 1: ...</p>
        <input type="radio" name="q1"> Opción A
        <input type="radio" name="q1"> Opción B
    </div>
    
    <!-- Botón para guardar cuando termine -->
    <?php mostrarBotonProgreso(2, 90, "Test - Jane Eyre"); ?>
    
    <!-- Script al final -->
    <?php mostrarScriptProgreso(); ?>
</body>
</html>
```

---

## 🔑 IDs de Actividades para Jane Eyre

Asigna IDs únicos a cada actividad:

```
Tests        → activity_id = 1
Rellenar     → activity_id = 2
Desarrollar  → activity_id = 3
Flashcards   → activity_id = 4
Juegos       → activity_id = 5
```

O puedes usar IDs más específicos:
```
Test Cap 1-4  → activity_id = 10
Test Cap 5-10 → activity_id = 11
Test Cap 11+  → activity_id = 12
```

---

## 💻 JavaScript Manual (Si no usas el helper)

Si quieres escribir el JavaScript directamente:

```html
<button onclick="guardarProgreso(5, 95)">
    Guardar Progreso
</button>

<script>
function guardarProgreso(activityId, puntuacion) {
    // Determinar la ruta correcta (funciona desde cualquier profundidad)
    const basePath = window.location.pathname.includes('/TFM-Sara-y-Daniela/')
        ? '/TFM-Sara-y-Daniela/save-progress.php'
        : '../save-progress.php';
    
    fetch(basePath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'activity_id=' + activityId + 
              '&puntuacion=' + puntuacion + 
              '&completado=1'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✓ Progreso guardado: ' + puntuacion + ' puntos');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar. Por favor, intenta de nuevo.');
    });
}
</script>
```

**⚠️ Nota Importante**: Usa siempre la resolución de ruta dinámica arriba mostrada en lugar de `../../save-progress.php` hardcodeada, ya que los archivos pueden estar a diferentes profundidades de directorios.

---

## 📂 Estructura de Rutas

```
TFM-Sara-y-Daniela/
├── content/
│   ├── pUsuario.php          ← Perfil con progreso integrado
│   ├── progreso.php          ← Dashboard completo (aún disponible)
│   └── works/
│       └── eyre.php
│       └── eyre_content.php/
│           ├── test.php          ← Agregar activity-tracker aquí
│           ├── rellenar.php      ← Agregar aquí
│           ├── desarrollar.php   ← Agregar aquí
│           ├── flashcard.php     ← Agregar aquí
│           ├── juegos.php        ← Agregar aquí
│           └── test-ejemplo.php  ← EJEMPLO COMPLETO
├── activity-tracker.php      ← Helper functions
├── save-progress.php         ← Backend para guardar
├── progress-helper.php       ← Funciones de BD
└── database.php
```

---

## 🎨 Personalización

### Cambiar el color del botón:

```php
// En activity-tracker.php, modifica:
background-color: #4CAF50;  // Cambiar a otro color
```

### Cambiar el mensaje de éxito:

```php
// En activity-tracker.php, función mostrarScriptProgreso():
alert('✓ ¡Actividad completada con éxito!...');
// Cambiar el texto aquí
```

---

## 🧪 Probar Localmente

### 1. Crea una prueba rápida

```
http://localhost/TFM-Sara-y-Daniela/content/works/eyre_content.php/test-ejemplo.php
```

### 2. Responde el test

### 3. Haz clic en "Verificar Respuestas"

### 4. Haz clic en "Guardar Progreso"

### 5. Deberías ver el alert: "✓ ¡Actividad completada!"

### 6. Ve a tu perfil (`pUsuario.php`)

### 7. Abre el tab "Historial" y deberías verlo ahí

---

## 📊 Funciones Disponibles en activity-tracker.php

### `mostrarBotonProgreso($id, $puntos, $nombre)`
Muestra un botón para guardar progreso

```php
<?php mostrarBotonProgreso(2, 85, "Test - Cap 1"); ?>
```

### `mostrarWidgetProgreso()`
Muestra un widget que indica que el progreso se guarda

```php
<?php mostrarWidgetProgreso(); ?>
```

### `mostrarScriptProgreso()`
Incluye el JavaScript necesario

```php
<?php mostrarScriptProgreso(); ?>
```

---

## ❓ Preguntas Comunes

**P: ¿Dónde se guardan los datos?**
R: En la tabla `user_progress` de la BD

**P: ¿El usuario ve su progreso automáticamente?**
R: Sí, en su perfil → Tab "Historial"

**P: ¿Puedo guardar progreso múltiples veces?**
R: Sí, la puntuación se actualiza con la última

**P: ¿Qué pasa si no estoy logueado?**
R: El widget muestra "Inicia sesión para rastrear"

---

## 🚀 Siguientes Pasos

1. Abre cada archivo de actividad en Jane Eyre:
   - `test.php`
   - `rellenar.php`
   - `desarrollar.php`
   - `flashcard.php`
   - `juegos.php`

2. Agrega al inicio:
   ```php
   <?php
   session_start();
   require '../../../../activity-tracker.php';
   ?>
   ```

3. Agrega el widget:
   ```php
   <?php mostrarWidgetProgreso(); ?>
   ```

4. Agrega el botón cuando se complete:
   ```php
   <?php mostrarBotonProgreso(ACTIVITY_ID, puntuacion, "Nombre"); ?>
   ```

5. Agrega el script al final:
   ```php
   <?php mostrarScriptProgreso(); ?>
   ```

---

## 📞 Soporte Rápido

Si algo no funciona:

1. Abre la **consola del navegador** (F12)
2. Busca errores en rojo
3. Verifica que `save-progress.php` exista
4. Verifica que el usuario esté logueado
5. Verifica que `activity-tracker.php` esté en la raíz

¡Listo para integrar! 🎉
