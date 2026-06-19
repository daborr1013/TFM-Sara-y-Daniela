<?php
/**
 * TEST DE CONEXIÓN A LA BASE DE DATOS
 * Abre en el navegador: http://localhost/TFM-Sara-y-Daniela/test-db.php
 */

echo "<h1>Test de Conexión a Base de Datos</h1>";
echo "<hr>";

// Test 1: Verificar que el archivo database.php existe
echo "<h2>1. Verificar archivo database.php</h2>";
if (file_exists('database.php')) {
    echo "✅ Archivo database.php encontrado<br>";
} else {
    echo "❌ Archivo database.php NO encontrado<br>";
}

// Test 2: Intentar conectar a la base de datos
echo "<h2>2. Intentar conectar a la base de datos</h2>";

$host = "localhost";
$user = "root";
$password = "";
$database = "litterally";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
    echo "<p><strong>Solución:</strong></p>";
    echo "<ol>";
    echo "<li>Abre XAMPP Control Panel</li>";
    echo "<li>Haz clic en 'Start' para Apache y MySQL</li>";
    echo "<li>Recarga esta página</li>";
    echo "</ol>";
} else {
    echo "✅ Conexión exitosa a: " . $database . "<br>";
    
    // Test 3: Verificar tablas
    echo "<h2>3. Verificar tablas</h2>";
    
    $tables = ['works', 'summaries', 'users', 'activities'];
    
    foreach ($tables as $table) {
        $query = "SELECT 1 FROM $table LIMIT 1";
        $result = $conn->query($query);
        
        if ($result === false) {
            echo "❌ Tabla '$table' NO existe<br>";
        } else {
            echo "✅ Tabla '$table' existe<br>";
        }
    }
    
    // Test 4: Verificar datos en works
    echo "<h2>4. Verificar datos en tabla 'works'</h2>";
    
    $query = "SELECT * FROM works";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Datos encontrados en 'works':<br>";
        while ($row = $result->fetch_assoc()) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "⚠️ No hay datos en la tabla 'works'<br>";
    }
    
    // Test 5: Verificar datos en summaries
    echo "<h2>5. Verificar datos en tabla 'summaries'</h2>";
    
    $query = "SELECT id, chapter, tipo FROM summaries LIMIT 3";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ Datos encontrados en 'summaries':<br>";
        while ($row = $result->fetch_assoc()) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    } else {
        echo "⚠️ No hay datos en la tabla 'summaries'<br>";
    }
    
    $conn->close();
}
?>
