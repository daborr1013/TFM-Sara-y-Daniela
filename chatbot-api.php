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

// Iniciar sesión para autenticación
session_start();

// Limpiar cualquier buffer de salida previo e iniciar uno nuevo
ob_end_clean();
ob_start();

// Establecer encabezado para devolver formato JSON ANTES de cualquier otra salida
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Función para retornar respuesta de error
function sendError($message, $code = 400) {
    ob_end_clean();
    http_response_code($code);
    echo json_encode([
        'response' => $message,
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para aplicar rate limiting
function applyRateLimit() {
    $maxRequests = 30;
    $timeWindow = 60; // segundos
    
    if (!isset($_SESSION['chatbot_requests'])) {
        $_SESSION['chatbot_requests'] = [];
    }
    
    $now = time();
    // Limpiar requests antiguos
    $_SESSION['chatbot_requests'] = array_filter(
        $_SESSION['chatbot_requests'],
        fn($t) => ($now - $t) < $timeWindow
    );
    
    if (count($_SESSION['chatbot_requests']) >= $maxRequests) {
        sendError('Demasiadas solicitudes. Por favor, espera antes de intentar de nuevo.', 429);
    }
    
    $_SESSION['chatbot_requests'][] = $now;
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    sendError('Debes estar autenticado para usar el chatbot.', 401);
}

// Aplicar rate limiting
applyRateLimit();

// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Método no permitido.', 405);
}

// Función para sanitizar entrada del usuario
function sanitizeInput($input) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return substr($input, 0, 500); // Limitar a 500 caracteres
}

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
        'señora temple' => 'Señora Temple',
        'brocklehurst' => 'Señor Brocklehurst',
        'temple' => 'Señora Temple',
        'fairfax' => 'Señora Fairfax',
    ];
    
    // Encontrar qué personaje busca el usuario
    $characterName = null;
    foreach ($characterMap as $keyword => $name) {
        if (strpos($msg, $keyword) !== false) {
            $characterName = $name;
            break;
        }
    }
    
    // Si se menciona "personaje" sin especificar, obtener lista de la BD
    if (!$characterName && preg_match('/personaje|protagonista/', $msg)) {
        $query = "SELECT nombre FROM characters WHERE work_id = 1 ORDER BY nombre LIMIT 15";
        $result = $conn->query($query);
        $characters = [];
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $characters[] = $row['nombre'];
            }
        }
        if (!empty($characters)) {
            return "👤 Tengo información sobre estos personajes de Jane Eyre:\n\n**" . 
                   implode("**, **", $characters) . "**\n\n¿Sobre cuál quieres saber más?";
        }
        return "👤 Puedo contarte sobre los personajes de Jane Eyre. ¿Cuál te interesa?";
    }
    
    // Si encontró un personaje, buscar en la BD
    if ($characterName) {
        $query = "SELECT nombre, rol, descripcion FROM characters WHERE nombre LIKE ? AND work_id = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $searchName = "%$characterName%";
            $stmt->bind_param('s', $searchName);
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
                // Limitar a 600 caracteres para no saturar el chat
                if (strlen($descripcion) > 600) {
                    $descripcion = substr($descripcion, 0, 600) . "...";
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

// Función para obtener información sobre temas de la tabla themes
function getThemeInfo($message, $conn) {
    $msg = strtolower($message);
    
    // Palabras clave para identificar temas específicos
    $themeKeywords = [
        '#amorMoralidad' => ['amor', 'moralidad', 'ética', 'principios', 'rochester', 'bertha', 'moral'],
        '#csocialesIndependenciaDesigualdad' => ['clase', 'social', 'independencia', 'igualdad', 'desigualdad', 'institutriz', 'pobreza', 'riqueza'],
        '#religionEspiritualidad' => ['religión', 'espiritualidad', 'fe', 'cristian', 'brocklehurst', 'helen', 'john rivers', 'st. john'],
        '#justicia' => ['justicia', 'castigo', 'recompensa', 'consequences', 'consecuencias'],
    ];
    
    $foundTheme = null;
    foreach ($themeKeywords as $themeId => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($msg, $keyword) !== false) {
                $foundTheme = $themeId;
                break 2;
            }
        }
    }
    
    // Si encontró un tema específico, buscar en la BD
    if ($foundTheme) {
        $query = "SELECT tema_id, contenido FROM themes WHERE work_id = 1 AND tema_id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('s', $foundTheme);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $contenido = strip_tags($row['contenido']);
                $contenido = preg_replace('/\s+/', ' ', trim($contenido));
                $contenido = substr($contenido, 0, 800);
                
                $temaLabel = str_replace('#', '', $foundTheme);
                return "📚 **Tema: " . $temaLabel . "**\n\n" . $contenido . "\n\n¿Te gustaría explorar otro tema?";
            }
            $stmt->close();
        }
    }
    
    // Si no encontró tema específico, mostrar lista general
    $query = "SELECT tema_id FROM themes WHERE work_id = 1 LIMIT 10";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $response = "📚 **Temas Principales en Jane Eyre:**\n\n";
        while($row = $result->fetch_assoc()) {
            $temaLabel = str_replace('#', '', $row['tema_id']);
            $response .= "🔹 " . $temaLabel . "\n";
        }
        $response .= "\n¿Cuál te interesa explorar? Pregunta por ejemplo: 'Habla de amor y moralidad' o 'justicia en Jane Eyre'";
        return $response;
    }
    
    return "📚 Jane Eyre explora temas profundos. ¿Quieres conocer sobre amor y moralidad, clases sociales, religión, o justicia?";
}

// NUEVA FUNCIÓN: Extrae símbolos específicos de la tabla symbols
function getSymbols($message, $conn) {
    $msg = strtolower($message);
    
    // Palabras clave para identificar símbolos específicos
    $symbolKeywords = [
        '#fuegoHielo' => ['fuego', 'hielo', 'frío', 'calor', 'fuego y hielo', 'fire', 'ice', 'cold'],
        '#casa' => ['casa', 'thornfield', 'gateshead', 'lowood', 'moor house', 'ferndean', 'hogar'],
        '#luzOscuridadBertha' => ['luz', 'oscuridad', 'sombra', 'bertha', 'ciego', 'blind', 'light', 'darkness'],
        '#cuartoRojo' => ['cuarto rojo', 'red room', 'castigo', 'punishment', 'encierro'],
        '#naturaleza' => ['naturaleza', 'nature', 'árbol', 'castaño', 'rayo', 'weather', 'tiempo'],
        '#pajaroEyre' => ['pájaro', 'bird', 'libertad', 'freedom', 'aire', 'air', 'jaula'],
        '#madres' => ['madres', 'women', 'mujeres', 'reed', 'temple', 'diana', 'mary', 'bessie', 'fairfax'],
    ];
    
    $foundSymbol = null;
    foreach ($symbolKeywords as $symbolId => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($msg, $keyword) !== false) {
                $foundSymbol = $symbolId;
                break 2;
            }
        }
    }
    
    // Si encontró un símbolo específico, buscar en la BD
    if ($foundSymbol) {
        $query = "SELECT simbolo_id, contenido FROM symbols WHERE work_id = 1 AND simbolo_id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param('s', $foundSymbol);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $contenido = strip_tags($row['contenido']);
                $contenido = preg_replace('/\s+/', ' ', trim($contenido));
                $contenido = substr($contenido, 0, 900);
                
                $simboloLabel = str_replace('#', '', $row['simbolo_id']);
                return "🎭 **Símbolo: " . $simboloLabel . "**\n\n" . $contenido . "\n\n¿Te gustaría explorar otro símbolo?";
            }
            $stmt->close();
        }
    }
    
    // Si no encontró símbolo específico, mostrar lista general
    $query = "SELECT simbolo_id FROM symbols WHERE work_id = 1 LIMIT 10";
    $result = @$conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $response = "🎭 **Símbolos en Jane Eyre:**\n\n";
        while($row = $result->fetch_assoc()) {
            $simboloLabel = str_replace('#', '', $row['simbolo_id']);
            $response .= "✨ " . $simboloLabel . "\n";
        }
        $response .= "\n¿Cuál te interesa explorar? Por ejemplo: 'fuego y hielo', 'la casa Thornfield', 'el cuarto rojo', 'la naturaleza', 'el pájaro Eyre', 'las madres de Jane'";
        return $response;
    }
    
    return "🎭 Jane Eyre está llena de símbolos poderosos. ¿Te gustaría conocer sobre el fuego, la luz, las casas, o la naturaleza?";
}

// Función para procesar preguntas y búsquedas más complejas
function searchDatabase($keyword, $conn) {
    // Buscar en concepto_clave de blocks
    $query = "SELECT DISTINCT concepto_clave, nota_chatbot 
              FROM blocks 
              WHERE work_id = 1 
              AND (concepto_clave LIKE ? OR nota_chatbot LIKE ?)
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $search = "%$keyword%";
        $stmt->bind_param('ss', $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $response = "📖 Encontré información relacionada:\n\n";
            while($row = $result->fetch_assoc()) {
                if (!empty($row['nota_chatbot'])) {
                    $nota = strip_tags($row['nota_chatbot']);
                    $nota = preg_replace('/\s+/', ' ', trim($nota));
                    $nota = substr($nota, 0, 200);
                    $response .= "🔹 " . $row['concepto_clave'] . "\n" . $nota . "\n\n";
                }
            }
            return $response;
        }
        $stmt->close();
    }
    
    return null;
}

// Función para buscar y obtener respuesta de la base de datos
function getBotResponse($message, $conn) {
    $msg = strtolower($message); // Convertir a minúsculas para comparación
    
    // RESPUESTAS DE SALUDO
    if (preg_match('/hola|hi|hey|buenos|buenas|qué tal/', $msg)) {
        return "¡Hola! 👋 Soy Litto, tu asistente especialista en Jane Eyre. Puedo contarte sobre personajes, resúmenes de capítulos, temas principales y mucho más. ¿En qué puedo ayudarte?";
    }
    
    // OBTENER INFORMACIÓN DE LA OBRA DE LA BASE DE DATOS
    if (preg_match('/autor|escrit|quién.*escribió|quién.*escribio|charlotte.*brontë/', $msg)) {
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
        
        $chapter = isset($matches[2]) ? intval($matches[2]) : null;
        
        // Si no especificó capítulo, mostrar opciones
        if ($chapter === null) {
            return "📖 ¿De cuál capítulo necesitas el resumen? Puedo darte información de los capítulos del 1 al 38. Pregunta por ejemplo: 'Resumen capítulo 3' o 'Argumento del capítulo 10'.";
        }
        
        // Seguridad: asegurar que chapter sea un número válido
        if ($chapter < 1) $chapter = 1;
        if ($chapter > 38) $chapter = 38;
        
        $query = "SELECT contenido FROM summaries WHERE chapter = " . intval($chapter) . " LIMIT 1";
        $result = @$conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (isset($row['contenido']) && !empty($row['contenido'])) {
                // Remover etiquetas HTML para mostrar texto limpio
                $content = strip_tags($row['contenido']);
                // Limpiar espacios en blanco extra
                $content = preg_replace('/\s+/', ' ', trim($content));
                // Limitar a 800 caracteres
                if (strlen($content) > 800) {
                    $content = substr($content, 0, 800) . "...";
                }
                return "📖 **Capítulo " . intval($chapter) . ":**\n\n" . $content;
            }
        }
        return "📖 Lo siento, no encontré el resumen de ese capítulo. ¿Puedes especificar otro capítulo?";
    }
    
    // PREGUNTAS SOBRE PERSONAJES
    if (preg_match('/personaje|protagonista|hero|heroin|quién.*jane|jane.*es|cómo.*jane|rochester|bertha|st\.\s*john|diana|helen|adele|bessie|señora reed|brocklehurst|temple|fairfax/i', $msg)) {
        return getCharacterInfo($msg, $conn);
    }
    
    // PREGUNTAS SOBRE TEMAS Y CONCEPTOS
    if (preg_match('/tema|concepto|moral|ética|justicia|igualdad|mujer|indepen|valores/', $msg)) {
        // Primero intentar búsqueda específica
        preg_match('/tema.*?(\w+)|concepto.*?(\w+)|ética.*?(\w+)|justicia.*?(\w+)/i', $msg, $matches);
        if (!empty($matches) && !empty($matches[1])) {
            $result = searchDatabase($matches[1], $conn);
            if ($result) return $result;
        }
        return getThemeInfo($msg, $conn);
    }
    
    // PREGUNTAS SOBRE SÍMBOLOS
    if (preg_match('/símbolo|simbolog|significado|representa|metáfora|metafor|fuego|luz|oscuridad|sombra|aislamiento|cadena|casa|fantasma|miedo/', $msg)) {
        return getSymbols($msg, $conn);
    }
    
    // PREGUNTAS SOBRE CONTEXTO/PERÍODO
    if (preg_match('/contexto|época|período|periodo|cuándo|cuando|siglo|victorian|histór|inglaterra|inglaterra|escena|lugar|setting/', $msg)) {
        return "🏛️ Jane Eyre está ambientada en la Inglaterra del siglo XIX, específicamente en la época victoriana (1837-1901). La novela combina ficción con realismo social, tocando temas profundos como:\n\n" .
               "• La clase social y la movilidad social\n" .
               "• La moral y la responsabilidad personal\n" .
               "• La independencia y derechos de la mujer\n" .
               "• El amor y la igualdad en las relaciones\n\n" .
               "¿Quieres saber más sobre alguno de estos temas?";
    }
    
    // PREGUNTAS SOBRE ROMANCE/ROCHESTER
    if (preg_match('/rochester|amor|romantic|relación|pareja|jane.*rochester|matrimonio/', $msg)) {
        return "💔 La relación entre Jane y el Sr. Rochester es el eje central de la novela. Es una historia de amor compleja que:\n\n" .
               "• Cuestiona los roles sociales de la época\n" .
               "• Explora la igualdad en las relaciones humanas\n" .
               "• Desafía las convenciones victorianas sobre el matrimonio\n" .
               "• Muestra la importancia de la honestidad y la confianza\n\n" .
               "¿Quieres conocer más detalles sobre cómo evoluciona su relación?";
    }
    
    // BÚSQUEDA GENERAL EN EL CONTENIDO
    $searchResult = searchDatabase($msg, $conn);
    if ($searchResult) {
        return $searchResult;
    }
    
    // RESPUESTA POR DEFECTO SI NO COINCIDEN PALABRAS CLAVE
    return "No estoy completamente seguro sobre eso. Puedo ayudarte con:\n\n" .
           "📖 **Resúmenes**: Cuéntame el número del capítulo\n" .
           "👤 **Personajes**: Pregunta por Jane, Rochester, Helen, etc.\n" .
           "📚 **Temas**: Justicia, independencia, amor, clase social\n" .
           "� **Símbolos**: Fuego, luz, aislamiento, casas, fantasmas\n" .
           "�🏛️ **Contexto**: Época victoriana, Inglaterra, historia\n" .
           "✍️ **Autora**: Charlotte Brontë\n\n" .
           "¿Sobre qué te gustaría saber más?";
}

// Inicializar variables
$conn = null;
$response = "Ocurrió un error inesperado.";

try {
    // Importar conexión a la base de datos
    require_once 'database.php';
    
    // Verificar conexión
    if (!$conn || $conn->connect_error) {
        sendError('No se puede conectar a la base de datos. Por favor verifique que XAMPP/MySQL está ejecutándose.', 503);
    }

    // Obtener el mensaje desde JavaScript
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input || !isset($input['message'])) {
        sendError('Solicitud inválida. Falta el campo "message".', 400);
    }
    
    $userMessage = sanitizeInput($input['message']);

    // Validar que el mensaje no esté vacío después de sanitizar
    if (empty($userMessage)) {
        sendError('Por favor, escribe un mensaje.', 400);
    }
    
    // Llamar a la función para obtener respuesta
    $response = getBotResponse($userMessage, $conn);
    
} catch (Exception $e) {
    // Capturar errores de conexión o consulta
    $errorMsg = $e->getMessage();
    error_log("Error en chatbot [" . date('Y-m-d H:i:s') . "]: " . $errorMsg);
    sendError('Disculpa, hubo un error al procesar tu mensaje. Por favor intenta de nuevo.', 500);
    
} finally {
    // Cerrar conexión a la base de datos
    if ($conn !== null && $conn instanceof mysqli && !$conn->connect_error) {
        $conn->close();
    }
}

// Asegurar que la respuesta sea una cadena válida
if (!is_string($response)) {
    $response = "Hubo un error al procesar tu mensaje.";
}

// Asegurar codificación UTF-8
$response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');

// Devolver respuesta como JSON
ob_end_clean();
echo json_encode([
    'response' => $response,
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
