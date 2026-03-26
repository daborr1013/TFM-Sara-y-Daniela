<?php
/**
 * API DEL CHATBOT - Integración Simple con Base de Datos
 * Este archivo maneja solicitudes de chat y obtiene respuestas de la base de datos
 * 
 * Cómo funciona:
 * 1. Recibe el mensaje del usuario desde JavaScript
 * 2. Busca en la base de datos contenido relevante
 * 3. Devuelve una respuesta basada en datos de la base de datos
 */

// Suprimir errores y mostrar como JSON en su lugar
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limpiar cualquier buffer de salida previo e iniciar uno nuevo
ob_end_clean();
ob_start();

// Establecer encabezado para devolver formato JSON ANTES de cualquier otra salida
header('Content-Type: application/json; charset=utf-8');

// Función para obtener información de personajes de la base de datos
function getCharacterInfo($message, $conn) {
    $msg = strtolower($message);
    
    // Mapeo de palabras clave a nombres de personajes en la BD
    $characterMap = [
        'jane' => 'Jane Eyre',
        'rochester' => 'Edward Rochester',
        'bertha' => 'Bertha Mason',
        'john' => 'John Rivers',
        'st. john' => 'John Rivers',
        'diana' => 'Diana Rivers',
        'helen' => 'Helen Burns',
        'helen burns' => 'Helen Burns',
        'adele' => 'Adèle Varens',
        'bessie' => 'Bessie',
        'señora reed' => 'Señora Reed',
        'brocklehurst' => 'Señor Brocklehurst',
        'temple' => 'Señora Temple',
        'fairfax' => 'Señora Fairfaix',
    ];
    
    // Encontrar qué personaje busca el usuario
    $characterName = null;
    foreach ($characterMap as $keyword => $name) {
        if (strpos($msg, $keyword) !== false) {
            $characterName = $name;
            break;
        }
    }
    
    // Si se menciona "personaje" sin especificar, devolver lista de disponibles
    if (!$characterName && preg_match('/personaje|protagonista/', $msg)) {
        return "👤 Tengo información sobre varios personajes de Jane Eyre. Puedo hablarte sobre:\n\n" .
               "**Principales**: Jane Eyre, Edward Rochester, Bertha Mason, John Rivers, Diana Rivers, Helen Burns\n\n" .
               "**Otros**: Adèle Varens, Bessie, Señora Reed, Señor Brocklehurst, Señora Temple y más.\n\n" .
               "¿Sobre cuál quieres saber más?";
    }
    
    // Si encontró un personaje, buscar en la BD
    if ($characterName) {
        $query = "SELECT nombre, rol, descripcion FROM characters WHERE nombre = ? AND work_id = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param('s', $characterName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Strip HTML tags from descripcion while preserving readability
                $descripcion = strip_tags($row['descripcion']);
                // Remove placeholder patterns like [value-5]
                $descripcion = preg_replace('/\[value-\d+\]/i', '', $descripcion);
                // Remove character name from beginning of description if it appears there
                $descripcion = preg_replace('/^' . preg_quote($row['nombre'], '/') . '\s+/i', '', $descripcion);
                // Clean up extra whitespace
                $descripcion = trim(preg_replace('/\s+/', ' ', $descripcion));
                // Limitar a 500 caracteres para no saturar el chat
                $descripcion = substr($descripcion, 0, 500);
                if (strlen($row['descripcion']) > 500) {
                    $descripcion .= "...";
                }
                
                // Build response with role only if it exists
                $response = "👤 " . $row['nombre'];
                if (!empty($row['rol']) && $row['rol'] !== null) {
                    $response .= " (" . $row['rol'] . ")";
                }
                $response .= "\n\n" . $descripcion;
                
                return $response;
            }
            $stmt->close();
        }
    }
    
    // Respuesta por defecto
    return "👤 Jane Eyre es la protagonista. Es una mujer independiente, inteligente y moralmente firme. Lucha por su dignidad en la Inglaterra victoriana. ¿Quieres saber más sobre ella o sobre otros personajes?";
}

// Función para buscar y obtener respuesta de la base de datos
function getBotResponse($message, $conn) {
    $msg = strtolower($message); // Convertir a minúsculas para comparación
    
    // RESPUESTAS DE SALUDO
    if (preg_match('/hola|hi|hey|buenos|buenas|qué tal/', $msg)) {
        return "¡Hola! 👋 Soy Litto, tu asistente especialista en Jane Eyre. ¿En qué puedo ayudarte?";
    }
    
    // OBTENER INFORMACIÓN DE LA OBRA DE LA BASE DE DATOS
    if (preg_match('/autor|escrit|quién.*escribió|quién.*escribio/', $msg)) {
        $query = "SELECT autor FROM works WHERE id = 1";
        $result = @$conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $autor = isset($row['autor']) ? $row['autor'] : 'Charlotte Brontë';
            return "✍️ La autora es " . $autor . " (1816-1855), una de las grandes escritoras de la literatura inglesa.";
        }
        return "✍️ La autora es Charlotte Brontë (1816-1855), una de las grandes escritoras de la literatura inglesa.";
    }
    
    // OBTENER INFORMACIÓN DE CAPÍTULOS/RESÚMENES DE LA BASE DE DATOS
    if (preg_match('/resumen|sinopsis|trama|plot|qué.*trata|de qué.*habla|argumento|capítulo/', $msg)) {
        // Extraer número de capítulo si el usuario lo mencionó
        preg_match('/\b(capítulo|cap|chapter)\s*(\d+)/i', $msg, $matches);
        
        $chapter = isset($matches[2]) ? intval($matches[2]) : 1;
        
        // Seguridad: asegurar que chapter sea un número válido
        if ($chapter < 1) $chapter = 1;
        if ($chapter > 38) $chapter = 38;
        
        $query = "SELECT contenido FROM summaries WHERE chapter = " . intval($chapter) . " LIMIT 1";
        $result = @$conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (isset($row['contenido'])) {
                // Remover etiquetas HTML para mostrar texto limpio
                $content = strip_tags($row['contenido']);
                // Limpiar espacios en blanco extra
                $content = preg_replace('/\s+/', ' ', trim($content));
                // Asegurar que no esté vacío
                if (strlen($content) > 0) {
                    return "📖 " . $content;
                }
            }
        }
        return "📖 Aquí va el resumen del capítulo. La base de datos contiene información detallada sobre Jane Eyre.";
    }
    
    // PREGUNTAS SOBRE PERSONAJES
    if (preg_match('/personaje|protagonista|heroin|quién.*jane|jane.*es|cómo.*jane|rochester|bertha|st\.\s*john|diana|helen|adele|bessie|señora reed|brocklehurst/i', $msg)) {
        return getCharacterInfo($msg, $conn);
    }
    
    // PREGUNTAS SOBRE CONTEXTO/PERÍODO
    if (preg_match('/contexto|época|período|periodo|cuándo|cuando|siglo|victorian|histór/', $msg)) {
        return "🏛️ Jane Eyre está ambientada en la Inglaterra del siglo XIX, en la época victoriana. Une ficción y realismo social, tocando temas como la clase social, la moral y la independencia femenina.";
    }
    
    // PREGUNTAS SOBRE ROMANCE/ROCHESTER
    if (preg_match('/rochester|amor|romantic|relación|pareja|jane.*rochester/', $msg)) {
        return "💔 La relación entre Jane y el Sr. Rochester es central en la novela. Es una historia de amor compleja que cuestiona los roles sociales y la igualdad en la época victoriana.";
    }
    
    // RESPUESTA POR DEFECTO SI NO COINCIDEN PALABRAS CLAVE
    return "No estoy seguro sobre eso. Prueba preguntándome sobre: resumen, autor, personajes, contexto, o la relación de Jane y Rochester.";
}

// Inicializar variables
$conn = null;
$response = "Ocurrió un error inesperado.";

try {
    // Importar conexión a la base de datos
    require_once 'database.php';

    // Obtener el mensaje desde JavaScript
    $input = json_decode(file_get_contents("php://input"), true);
    $userMessage = isset($input['message']) ? trim($input['message']) : '';

    // Validar que el mensaje no esté vacío
    if (empty($userMessage)) {
        $response = 'Por favor, escribe un mensaje.';
    } else {
        // Llamar a la función para obtener respuesta
        $response = getBotResponse($userMessage, $conn);
    }
} catch (Exception $e) {
    // Capturar errores de conexión o consulta
    $errorMsg = $e->getMessage();
    error_log("Error en chatbot - " . date('Y-m-d H:i:s') . ": " . $errorMsg);
    
    // Para debugging: mostramos si es en desarrollo
    if (strpos($errorMsg, 'connect') !== false) {
        $response = 'No se puede conectar a la base de datos. Por favor verifique que XAMPP/MySQL está ejecutándose.';
    } else {
        $response = 'Disculpa, hubo un error al procesar tu mensaje. Por favor intenta de nuevo.';
    }
} finally {
    // Cerrar conexión a la base de datos
    if ($conn !== null && $conn instanceof mysqli) {
        $conn->close();
    }
}

// Asegurar que la respuesta sea una cadena válida
if (!is_string($response)) {
    $response = "Hubo un error al procesar tu mensaje.";
}

$response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');

// Devolver respuesta como JSON
ob_end_clean();
echo json_encode([
    'response' => $response,
    'status' => 'success'
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
