<?php
/**
 * API del chatbot Litto.
 *
 * El objetivo de este archivo es enrutar preguntas frecuentes sobre
 * Jane Eyre hacia la fuente de datos mas adecuada dentro de la base de datos.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

function sendJson(array $payload, int $code = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sendError(string $message, int $code = 400): void
{
    sendJson([
        'response' => $message,
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
    ], $code);
}

function applyRateLimit(): void
{
    $maxRequests = 30;
    $timeWindow = 60;

    if (!isset($_SESSION['chatbot_requests'])) {
        $_SESSION['chatbot_requests'] = [];
    }

    $now = time();
    $_SESSION['chatbot_requests'] = array_values(array_filter(
        $_SESSION['chatbot_requests'],
        static fn($timestamp) => ($now - $timestamp) < $timeWindow
    ));

    if (count($_SESSION['chatbot_requests']) >= $maxRequests) {
        sendError('Has enviado demasiadas consultas en muy poco tiempo. Espera un momento y vuelve a intentarlo.', 429);
    }

    $_SESSION['chatbot_requests'][] = $now;
}

function sanitizeInput($input): string
{
    $input = is_string($input) ? $input : '';
    $input = trim(strip_tags($input));
    $input = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $input);
    $input = preg_replace('/\s+/u', ' ', $input);

    return mb_substr($input, 0, 500, 'UTF-8');
}

function normalizeText(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = mb_strtolower($text, 'UTF-8');

    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($transliterated !== false) {
        $text = $transliterated;
    }

    $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function truncateAtSentenceBoundary(string $text, int $limit): string
{
    if ($limit <= 0 || mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    $slice = mb_substr($text, 0, $limit, 'UTF-8');
    $bestLength = 0;

    if (preg_match_all('/[.!?;:](?=\s|$)/u', $slice, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $position = mb_strlen(mb_substr($slice, 0, $match[1] + 1, 'UTF-8'), 'UTF-8');
            if ($position >= (int) floor($limit * 0.6)) {
                $bestLength = max($bestLength, $position);
            }
        }
    }

    if ($bestLength === 0 && preg_match_all('/,\s/u', $slice, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $position = mb_strlen(mb_substr($slice, 0, $match[1], 'UTF-8'), 'UTF-8');
            if ($position >= (int) floor($limit * 0.75)) {
                $bestLength = max($bestLength, $position);
            }
        }
    }

    if ($bestLength === 0) {
        $lastSpace = mb_strrpos($slice, ' ', 0, 'UTF-8');
        if ($lastSpace !== false && $lastSpace >= (int) floor($limit * 0.7)) {
            $bestLength = $lastSpace;
        }
    }

    if ($bestLength === 0) {
        $bestLength = $limit;
    }

    return rtrim(mb_substr($text, 0, $bestLength, 'UTF-8')) . '...';
}

function cleanDatabaseText(?string $text, ?int $limit = null): string
{
    if ($text === null) {
        return '';
    }

    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\[value-\d+\]/i', '', $text);
    $text = preg_replace('/\s+/u', ' ', trim($text));

    if ($limit !== null && mb_strlen($text, 'UTF-8') > $limit) {
        $text = truncateAtSentenceBoundary($text, $limit);
    }

    return $text;
}

function appendFollowUpSuggestions(string $response, array $suggestions = []): string
{
    $suggestions = array_values(array_unique(array_filter(array_map('trim', $suggestions))));

    if (empty($suggestions)) {
        return $response;
    }

    $suggestions = array_slice($suggestions, 0, 3);

    return rtrim($response) . "\n\nPuedes seguir con:\n- " . implode("\n- ", $suggestions);
}

function humanizeHashId(string $identifier): string
{
    $identifier = ltrim($identifier, '#');
    $identifier = preg_replace('/([a-z])([A-Z])/', '$1 $2', $identifier);

    return ucfirst(trim($identifier));
}

function getReadableLabel(string $identifier): string
{
    $labels = [
        '#amorMoralidad' => 'Amor y moralidad',
        '#csocialesIndependenciaDesigualdad' => 'Clases sociales, independencia y desigualdad',
        '#religionEspiritualidad' => 'Religión y espiritualidad',
        '#justicia' => 'Justicia',
        '#cuartoRojo' => 'Cuarto rojo',
        '#fuegoHielo' => 'Fuego e hielo',
        '#casa' => 'Casas',
        '#luzOscuridadBertha' => 'Luz y oscuridad',
        '#naturaleza' => 'Naturaleza',
        '#pajaroEyre' => 'Pájaro Eyre',
        '#madres' => 'Figuras maternas',
    ];

    return $labels[$identifier] ?? humanizeHashId($identifier);
}

function normalizeCharacterDisplayName(string $name): string
{
    $displayMap = [
        'Señora Fairfaix' => 'Señora Fairfax',
    ];

    return $displayMap[$name] ?? $name;
}

function hasPhrase(string $normalizedText, string $phrase): bool
{
    $pattern = '/(?:^|\s)' . str_replace('\ ', '\s+', preg_quote($phrase, '/')) . '(?:$|\s)/';
    return preg_match($pattern, $normalizedText) === 1;
}

function hasAnyPhrase(string $normalizedText, array $phrases): bool
{
    foreach ($phrases as $phrase) {
        if (hasPhrase($normalizedText, $phrase)) {
            return true;
        }
    }

    return false;
}

function bindDynamicParams(mysqli_stmt $stmt, string $types, array $params): void
{
    $references = [$types];

    foreach ($params as $index => $value) {
        $references[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $references);
}

function fetchSingleRow(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && !empty($params)) {
        bindDynamicParams($stmt, $types, $params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function isGreetingMessage(string $normalizedMessage): bool
{
    $greetings = [
        'hola',
        'hola litto',
        'hi',
        'hey',
        'buenas',
        'buenos dias',
        'buenas tardes',
        'buenas noches',
        'que tal',
    ];

    return in_array($normalizedMessage, $greetings, true);
}

function isAuthorQuery(string $normalizedMessage): bool
{
    return hasAnyPhrase($normalizedMessage, [
        'autor',
        'autora',
        'escritor',
        'escritora',
        'quien escribio',
        'charlotte bronte',
    ]);
}

function getOutOfScopeResponse(): string
{
    return 'Solo puedo ayudarte con preguntas sobre Jane Eyre: personajes, capitulos, temas, simbolos, glosario y contexto historico. Si quieres, reformula tu pregunta relacionandola con la novela.';
}

function hasJaneEyreScopeSignal(string $normalizedMessage): bool
{
    if (resolveCharacterPatterns($normalizedMessage) !== []) {
        return true;
    }

    return hasAnyPhrase($normalizedMessage, [
        'jane eyre',
        'jane',
        'rochester',
        'bronte',
        'charlotte',
        'thornfield',
        'lowood',
        'gateshead',
        'moor house',
        'ferndean',
        'bertha',
        'adele',
        'helen',
        'bessie',
        'reed',
        'rivers',
        'personaje',
        'personajes',
        'protagonista',
        'capitulo',
        'chapter',
        'resumen',
        'resumeme',
        'sinopsis',
        'argumento',
        'trama',
        'tema',
        'temas',
        'simbolo',
        'simbolismo',
        'fuego',
        'hielo',
        'naturaleza',
        'cuarto rojo',
        'casa',
        'luz',
        'oscuridad',
        'libertad',
        'moralidad',
        'clase social',
        'independencia',
        'desigualdad',
        'religion',
        'justicia',
        'resurgam',
        'institutriz',
        'glosario',
        'termino',
        'concepto',
        'contexto',
        'historico',
        'victoriana',
        'gotico',
        'romanticismo',
        'autor',
        'autora',
        'obra',
        'novela',
        'libro',
    ]);
}

function isOutOfScopeQuestion(string $normalizedMessage): bool
{
    if (hasJaneEyreScopeSignal($normalizedMessage)) {
        return false;
    }

    return hasAnyPhrase($normalizedMessage, [
        'capital',
        'receta',
        'cocina',
        'programacion',
        'codigo',
        'matematicas',
        'fisica',
        'quimica',
        'biologia',
        'futbol',
        'deporte',
        'noticias',
        'politica',
        'musica',
        'pelicula',
        'serie',
        'clima',
        'tiempo',
        'viaje',
        'hotel',
        'restaurante',
        'traduce',
        'traducir',
        'escribe un poema',
        'hazme',
        'cuentame un chiste',
    ]);
}

function romanToInt(string $roman): ?int
{
    $roman = strtoupper(trim($roman));
    if ($roman === '' || preg_match('/^[IVXLCDM]+$/', $roman) !== 1) {
        return null;
    }

    $values = [
        'I' => 1,
        'V' => 5,
        'X' => 10,
        'L' => 50,
        'C' => 100,
        'D' => 500,
        'M' => 1000,
    ];

    $total = 0;
    $length = strlen($roman);

    for ($index = 0; $index < $length; $index++) {
        $current = $values[$roman[$index]] ?? 0;
        $next = ($index + 1 < $length) ? ($values[$roman[$index + 1]] ?? 0) : 0;
        $total += ($current < $next) ? -$current : $current;
    }

    return $total > 0 ? $total : null;
}

function getSpanishChapterWordMap(): array
{
    return [
        'uno' => 1,
        'primero' => 1,
        'primer' => 1,
        'dos' => 2,
        'segundo' => 2,
        'tres' => 3,
        'tercero' => 3,
        'cuatro' => 4,
        'cuarto' => 4,
        'cinco' => 5,
        'quinto' => 5,
        'seis' => 6,
        'sexto' => 6,
        'siete' => 7,
        'septimo' => 7,
        'octavo' => 8,
        'ocho' => 8,
        'nueve' => 9,
        'noveno' => 9,
        'diez' => 10,
        'decimo' => 10,
        'once' => 11,
        'doce' => 12,
        'trece' => 13,
        'catorce' => 14,
        'quince' => 15,
        'dieciseis' => 16,
        'diecisiete' => 17,
        'dieciocho' => 18,
        'diecinueve' => 19,
        'veinte' => 20,
        'veintiuno' => 21,
        'veintiun' => 21,
        'veintidos' => 22,
        'veintitres' => 23,
        'veinticuatro' => 24,
        'veinticinco' => 25,
        'veintiseis' => 26,
        'veintisiete' => 27,
        'veintiocho' => 28,
        'veintinueve' => 29,
        'treinta' => 30,
        'treinta y uno' => 31,
        'treinta y dos' => 32,
        'treinta y tres' => 33,
        'treinta y cuatro' => 34,
        'treinta y cinco' => 35,
        'treinta y seis' => 36,
        'treinta y siete' => 37,
        'treinta y ocho' => 38,
    ];
}

function parseChapterNumberCandidate(string $candidate): ?int
{
    $candidate = trim(preg_replace('/\s+/', ' ', $candidate));
    if ($candidate === '') {
        return null;
    }

    if (preg_match('/^\d{1,2}$/', $candidate) === 1) {
        return (int) $candidate;
    }

    if (preg_match('/^[ivxlcdm]+$/i', $candidate) === 1) {
        return romanToInt($candidate);
    }

    $map = getSpanishChapterWordMap();
    return $map[$candidate] ?? null;
}

function extractNumberNearChapterMarker(array $tokens): ?int
{
    $chapterMarkers = ['capitulo', 'cap', 'chapter'];
    $skipWords = ['numero', 'num', 'n', 'no'];
    $tokenCount = count($tokens);

    for ($index = 0; $index < $tokenCount; $index++) {
        if (!in_array($tokens[$index], $chapterMarkers, true)) {
            continue;
        }

        $start = $index + 1;
        while ($start < $tokenCount && in_array($tokens[$start], $skipWords, true)) {
            $start++;
        }

        for ($length = 3; $length >= 1; $length--) {
            if ($start + $length <= $tokenCount) {
                $candidate = implode(' ', array_slice($tokens, $start, $length));
                $value = parseChapterNumberCandidate($candidate);
                if ($value !== null) {
                    return $value;
                }
            }
        }

        for ($length = 3; $length >= 1; $length--) {
            $begin = $index - $length;
            if ($begin >= 0) {
                $candidate = implode(' ', array_slice($tokens, $begin, $length));
                $value = parseChapterNumberCandidate($candidate);
                if ($value !== null) {
                    return $value;
                }
            }
        }
    }

    return null;
}

function extractStandaloneChapterNumber(string $normalizedMessage): ?int
{
    $tokens = array_values(array_filter(preg_split('/\s+/', trim($normalizedMessage))));
    if (empty($tokens)) {
        return null;
    }

    $matches = [];
    $tokenCount = count($tokens);

    for ($length = 3; $length >= 1; $length--) {
        for ($index = 0; $index <= $tokenCount - $length; $index++) {
            $candidate = implode(' ', array_slice($tokens, $index, $length));
            $value = parseChapterNumberCandidate($candidate);

            if ($value !== null && $value >= 1 && $value <= 38) {
                $matches[] = $value;
            }
        }
    }

    $matches = array_values(array_unique($matches));
    return count($matches) === 1 ? $matches[0] : null;
}

function extractChapterNumber(string $normalizedMessage): ?int
{
    $tokens = array_values(array_filter(preg_split('/\s+/', trim($normalizedMessage))));
    $chapter = extractNumberNearChapterMarker($tokens);
    if ($chapter !== null) {
        return $chapter;
    }

    if (hasAnyPhrase($normalizedMessage, [
        'resumen',
        'resumeme',
        'resumir',
        'sinopsis',
        'argumento',
        'trama',
        'plot',
    ])) {
        return extractStandaloneChapterNumber($normalizedMessage);
    }

    return null;
}

function isChapterQuery(string $normalizedMessage): bool
{
    return extractChapterNumber($normalizedMessage) !== null
        || hasAnyPhrase($normalizedMessage, [
            'resumen',
            'sinopsis',
            'argumento',
            'trama',
            'plot',
            'capitulo',
            'chapter',
        ]);
}

function getCharacterAliasMap(): array
{
    return [
        ['aliases' => ['edward rochester', 'sr rochester', 'senor rochester', 'mr rochester', 'rochester'], 'patterns' => ['Edward Rochester']],
        ['aliases' => ['bertha mason', 'bertha'], 'patterns' => ['Bertha Mason']],
        ['aliases' => ['grace poole'], 'patterns' => ['Grace Poole']],
        ['aliases' => ['blanche ingram', 'ingram'], 'patterns' => ['Blanche Ingram']],
        ['aliases' => ['helen burns', 'helen'], 'patterns' => ['Helen Burns']],
        ['aliases' => ['senora temple', 'miss temple', 'temple'], 'patterns' => ['Señora Temple']],
        ['aliases' => ['senora reed', 'mrs reed'], 'patterns' => ['Señora Reed']],
        ['aliases' => ['senora fairfax', 'senora fairfaix', 'mrs fairfax', 'fairfax', 'fairfaix'], 'patterns' => ['Señora Fairfaix', 'Señora Fairfax']],
        ['aliases' => ['adele varens', 'adele'], 'patterns' => ['Adèle Varens', 'Adele Varens']],
        ['aliases' => ['bessie'], 'patterns' => ['Bessie']],
        ['aliases' => ['diana rivers', 'diana'], 'patterns' => ['Diana Rivers']],
        ['aliases' => ['mary rivers', 'mary'], 'patterns' => ['Mary Rivers']],
        ['aliases' => ['st john rivers', 'st john', 'john rivers'], 'patterns' => ['John Rivers']],
        ['aliases' => ['john reed', 'primo john'], 'patterns' => ['John Reed']],
        ['aliases' => ['lloyd', 'senor lloyd', 'sr lloyd'], 'patterns' => ['Lloyd']],
        ['aliases' => ['brocklehurst', 'senor brocklehurst', 'sr brocklehurst'], 'patterns' => ['Señor Brocklehurst']],
        ['aliases' => ['georgiana reed', 'georgiana', 'georgina reed', 'georgina'], 'patterns' => ['Georgina Reed']],
        ['aliases' => ['eliza reed', 'eliza'], 'patterns' => ['Eliza Reed']],
        ['aliases' => ['jane eyre', 'jane'], 'patterns' => ['Jane Eyre']],
    ];
}

function resolveCharacterPatterns(string $normalizedMessage): array
{
    foreach (getCharacterAliasMap() as $entry) {
        foreach ($entry['aliases'] as $alias) {
            if (hasPhrase($normalizedMessage, $alias)) {
                return $entry['patterns'];
            }
        }
    }

    return [];
}

function isCharacterQuery(string $normalizedMessage): bool
{
    return !empty(resolveCharacterPatterns($normalizedMessage))
        || hasAnyPhrase($normalizedMessage, ['personaje', 'personajes', 'protagonista', 'protagonistas']);
}

function isJaneRochesterQuery(string $normalizedMessage): bool
{
    return hasPhrase($normalizedMessage, 'jane') && hasPhrase($normalizedMessage, 'rochester');
}

function getThemeMap(): array
{
    return [
        '#amorMoralidad' => ['amor y moralidad', 'moralidad', 'etica', 'principios', 'amor'],
        '#csocialesIndependenciaDesigualdad' => ['clase social', 'clases sociales', 'independencia', 'desigualdad', 'igualdad', 'institutriz', 'pobreza', 'riqueza'],
        '#religionEspiritualidad' => ['religion', 'espiritualidad', 'fe', 'cristianismo'],
        '#justicia' => ['justicia', 'castigo', 'consecuencias'],
    ];
}

function resolveThemeId(string $normalizedMessage): ?string
{
    foreach (getThemeMap() as $themeId => $aliases) {
        if (hasAnyPhrase($normalizedMessage, $aliases)) {
            return $themeId;
        }
    }

    return null;
}

function isThemeQuery(string $normalizedMessage): bool
{
    return resolveThemeId($normalizedMessage) !== null
        || hasAnyPhrase($normalizedMessage, ['tema', 'temas']);
}

function getSymbolMap(): array
{
    return [
        '#cuartoRojo' => ['cuarto rojo', 'red room'],
        '#fuegoHielo' => ['fuego y hielo', 'fuego', 'hielo', 'frio', 'calor'],
        '#casa' => ['casa', 'casas', 'thornfield', 'gateshead', 'lowood', 'moor house', 'ferndean', 'hogar'],
        '#luzOscuridadBertha' => ['luz y oscuridad', 'luz', 'oscuridad', 'sombra'],
        '#naturaleza' => ['naturaleza', 'arbol', 'castano', 'rayo', 'weather'],
        '#pajaroEyre' => ['pajaro', 'bird', 'libertad', 'jaula'],
        '#madres' => ['madres', 'maternidad', 'figuras maternas'],
    ];
}

function resolveSymbolId(string $normalizedMessage): ?string
{
    foreach (getSymbolMap() as $symbolId => $aliases) {
        if (hasAnyPhrase($normalizedMessage, $aliases)) {
            return $symbolId;
        }
    }

    return null;
}

function isSymbolQuery(string $normalizedMessage): bool
{
    return resolveSymbolId($normalizedMessage) !== null
        || hasAnyPhrase($normalizedMessage, ['simbolo', 'simboliza', 'simbolismo', 'metafora', 'representa', 'significa']);
}

function getContextMap(): array
{
    return [
        'Época Victoriana' => ['epoca victoriana', 'victoriana', 'victoriano', 'siglo xix'],
        'Romanticismo' => ['romanticismo'],
        'Romanticismo vs. Ilustración' => ['ilustracion', 'romanticismo vs ilustracion', 'razon y corazon'],
        'Gótico' => ['gotico', 'gotica', 'gothic'],
        'Las Hermanas Brontë: Realidad tras la Ficción' => ['hermanas bronte', 'charlotte bronte', 'emily bronte', 'anne bronte', 'biografia'],
        'El Legado: ¿Por qué seguimos leyendo Jane Eyre?' => ['legado', 'por que seguimos leyendo', 'actualidad'],
    ];
}

function resolveContextSection(string $normalizedMessage): ?string
{
    foreach (getContextMap() as $section => $aliases) {
        if (hasAnyPhrase($normalizedMessage, $aliases)) {
            return $section;
        }
    }

    return null;
}

function isContextQuery(string $normalizedMessage): bool
{
    return resolveContextSection($normalizedMessage) !== null
        || hasAnyPhrase($normalizedMessage, [
            'contexto',
            'historico',
            'epoca',
            'periodo',
            'inglaterra',
            'victoriana',
            'victoriano',
            'siglo xix',
            'gotico',
            'romanticismo',
            'ilustracion',
        ]);
}



function getCharacterFollowUps(string $characterName): array
{
    return [
        'pregunta sobre otro personaje',
        'busca otro tema',
    ];
}

function getThemeFollowUps(): array
{
    return [
        'pregunta sobre otro tema',
        'busca información de otro tipo',
    ];
}

function getSymbolFollowUps(): array
{
    return [
        'pregunta sobre otro síbolo',
        'busca otro tema',
    ];
}

function getContextFollowUps(): array
{
    return [
        'pregunta sobre otro aspecto histórico',
        'busca otra información',
    ];
}

function getChapterFollowUps(int $chapter): array
{
    return [
        'pregunta sobre otro capítulo',
        'busca información sobre personajes',
    ];
}

function getCharacterList(mysqli $conn): string
{
    $result = $conn->query("SELECT nombre FROM characters WHERE work_id = 1 ORDER BY nombre");
    $characters = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $characters[] = normalizeCharacterDisplayName($row['nombre']);
        }
    }

    if (empty($characters)) {
        return '👤 No tengo información de personajes disponible en este momento.';
    }

    return "👤 Tengo información sobre estos personajes:\n\n- " . implode("\n- ", $characters) . "\n\nPregunta por el que te interese.";
}

function getCharacterResponse(string $normalizedMessage, mysqli $conn): string
{
    if (isJaneRochesterQuery($normalizedMessage)) {
        return appendFollowUpSuggestions(
            "Jane y Rochester forman la relacion central de la novela.\n\nAl principio, Rochester reconoce en Jane una igual intelectual: no la trata solo como institutriz, sino como alguien capaz de responderle con independencia y criterio. Ese vinculo se vuelve amoroso, pero tambien conflictivo, porque el secreto de Bertha rompe la confianza y obliga a Jane a elegir su dignidad antes que el deseo.\n\nEl final recompone la relacion desde otra posicion: Jane vuelve cuando ya es independiente y Rochester ha perdido parte de su antiguo poder. Por eso su union final funciona como una relacion mas igualitaria, no como un rescate romantico simple.",
            [
                'pregunta por Rochester',
                'pregunta por Jane',
                'busca el tema amor y moralidad',
            ]
        );
    }

    $patterns = resolveCharacterPatterns($normalizedMessage);

    if (empty($patterns)) {
        return getCharacterList($conn);
    }

    foreach ($patterns as $pattern) {
        $like = '%' . $pattern . '%';
        $row = fetchSingleRow(
            $conn,
            "SELECT nombre, rol, descripcion, relaciones FROM characters WHERE work_id = 1 AND nombre LIKE ? LIMIT 1",
            's',
            [$like]
        );

        if ($row) {
            $description = cleanDatabaseText($row['descripcion'] ?? '', 1400);
            $description = preg_replace('/^' . preg_quote($row['nombre'], '/') . '\s+/iu', '', $description);
            $relationships = cleanDatabaseText($row['relaciones'] ?? '', 450);

            $displayName = normalizeCharacterDisplayName($row['nombre']);
            $response = "👤 " . $displayName;
            if (!empty($row['rol'])) {
                $response .= ' (' . $row['rol'] . ')';
            }

            if ($relationships !== '') {
                $description .= "\n\nRelaciones: " . $relationships;
            }

            return appendFollowUpSuggestions(
                $response . "\n\n" . $description,
                getCharacterFollowUps($displayName)
            );
        }
    }

    return '👤 No he podido localizar ese personaje en la base de datos. Intenta con otro nombre.';
}

function getThemeList(mysqli $conn): string
{
    $result = $conn->query("SELECT tema_id FROM themes WHERE work_id = 1 ORDER BY id");
    $themes = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $themes[] = getReadableLabel($row['tema_id']);
        }
    }

    if (empty($themes)) {
        return '📚 No tengo información de temas disponible en este momento.';
    }

    return "📚 Tengo información sobre estos temas:\n\n- " . implode("\n- ", $themes) . "\n\nPregunta por el que te interese.";
}

function getThemeResponse(string $normalizedMessage, mysqli $conn): string
{
    $themeId = resolveThemeId($normalizedMessage);

    if ($themeId === null) {
        return getThemeList($conn);
    }

    $row = fetchSingleRow(
        $conn,
        "SELECT tema_id, contenido FROM themes WHERE work_id = 1 AND tema_id = ? LIMIT 1",
        's',
        [$themeId]
    );

    if (!$row) {
        return '📚 No he encontrado ese tema en la base de datos.';
    }

    return appendFollowUpSuggestions(
        "📚 Tema: " . getReadableLabel($row['tema_id']) . "\n\n" . cleanDatabaseText($row['contenido'], 1800),
        getThemeFollowUps()
    );
}

function getSymbolList(mysqli $conn): string
{
    $result = $conn->query("SELECT simbolo_id FROM symbols WHERE work_id = 1 ORDER BY id");
    $symbols = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $symbols[] = getReadableLabel($row['simbolo_id']);
        }
    }

    if (empty($symbols)) {
        return '🎭 No tengo información de símbolos disponible en este momento.';
    }

    return "🎭 Tengo información sobre estos símbolos:\n\n- " . implode("\n- ", $symbols) . "\n\nPregunta por el que te interese.";
}

function getSymbolResponse(string $normalizedMessage, mysqli $conn): string
{
    $symbolId = resolveSymbolId($normalizedMessage);

    if ($symbolId === null) {
        return getSymbolList($conn);
    }

    $row = fetchSingleRow(
        $conn,
        "SELECT simbolo_id, contenido FROM symbols WHERE work_id = 1 AND simbolo_id = ? LIMIT 1",
        's',
        [$symbolId]
    );

    if (!$row) {
        return '🎭 No he encontrado ese símbolo en la base de datos.';
    }

    return appendFollowUpSuggestions(
        "🎭 Símbolo: " . getReadableLabel($row['simbolo_id']) . "\n\n" . cleanDatabaseText($row['contenido'], 1800),
        getSymbolFollowUps()
    );
}

function getContextList(mysqli $conn): string
{
    $result = $conn->query("SELECT section FROM work_historical_context WHERE work_id = 1 ORDER BY id");
    $sections = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row['section'];
        }
    }

    if (empty($sections)) {
        return '🏛️ No tengo información de contexto histórico disponible en este momento.';
    }

    return "🏛️ Tengo información sobre estas secciones de contexto:\n\n- " . implode("\n- ", $sections) . "\n\nPregunta por la que te interese.";
}

function getContextResponse(string $normalizedMessage, mysqli $conn): string
{
    $section = resolveContextSection($normalizedMessage);

    if ($section === null) {
        return getContextList($conn);
    }

    $row = fetchSingleRow(
        $conn,
        "SELECT section, content FROM work_historical_context WHERE work_id = 1 AND section = ? LIMIT 1",
        's',
        [$section]
    );

    if (!$row) {
        return '🏛️ No he encontrado esa sección de contexto histórico.';
    }

    return appendFollowUpSuggestions(
        "🏛️ " . $row['section'] . "\n\n" . cleanDatabaseText($row['content'], 1800),
        getContextFollowUps()
    );
}

function getAuthorResponse(mysqli $conn): string
{
    $row = fetchSingleRow($conn, "SELECT autor FROM works WHERE id = 1 LIMIT 1");
    $author = trim((string) ($row['autor'] ?? 'Charlotte Brontë'), "[] \t\n\r\0\x0B");

    return '✍️ Jane Eyre fue escrita por ' . $author . ' (1816-1855), una de las autoras fundamentales de la literatura inglesa.';
}

function getChapterResponse(string $normalizedMessage, mysqli $conn): string
{
    $chapter = extractChapterNumber($normalizedMessage);

    if ($chapter === null) {
        return '📖 Cuéntame el número o nombre del capítulo del que quieres el resumen.';
    }

    if ($chapter < 1 || $chapter > 38) {
        return '📖 Jane Eyre tiene 38 capítulos. Solicita un número entre el 1 y el 38.';
    }

    $row = fetchSingleRow(
        $conn,
        "SELECT chapter, contenido FROM summaries WHERE work_id = 1 AND chapter = ? LIMIT 1",
        'i',
        [$chapter]
    );

    if (!$row) {
        return '📖 No he encontrado el resumen de ese capítulo en la base de datos.';
    }

    $chapterNumber = (int) $row['chapter'];
    $chapterBody = cleanDatabaseText($row['contenido'], 1600);
    $chapterTitle = 'Capítulo ' . $chapterNumber;
    $blockLike = '%' . $chapterTitle . '%';
    $insight = fetchSingleRow(
        $conn,
        "SELECT concepto_clave, nota_chatbot
         FROM blocks
         WHERE work_id = 1 AND titulo LIKE ?
         LIMIT 1",
        's',
        [$blockLike]
    );

    $extraSections = [];
    if (!empty($insight['concepto_clave'])) {
        $extraSections[] = 'Idea clave: ' . cleanDatabaseText($insight['concepto_clave'], 220);
    }

    if (!empty($insight['nota_chatbot'])) {
        $extraSections[] = 'Lectura guiada: ' . cleanDatabaseText($insight['nota_chatbot'], 260);
    }

    $response = '📖 ' . $chapterTitle . "\n\n" . $chapterBody;
    if (!empty($extraSections)) {
        $response .= "\n\n" . implode("\n", $extraSections);
    }

    return appendFollowUpSuggestions(
        $response,
        getChapterFollowUps($chapterNumber)
    );
}



function buildSearchTerms(string $normalizedMessage): array
{
    $terms = [];
    $priorityPhrases = [
        'resurgam',
        'cuarto rojo',
        'amor y moralidad',
        'clase social',
        'epoca victoriana',
        'thornfield',
        'lowood',
        'moor house',
        'ferndean',
        'bertha mason',
        'helen burns',
    ];

    foreach ($priorityPhrases as $phrase) {
        if (hasPhrase($normalizedMessage, $phrase)) {
            $terms[] = $phrase;
        }
    }

    $stopWords = [
        'que', 'por', 'para', 'con', 'sin', 'una', 'uno', 'unos', 'unas', 'del', 'las', 'los',
        'sobre', 'como', 'donde', 'cuando', 'porque', 'quien', 'cual', 'cuales', 'explica',
        'habla', 'significa', 'simboliza', 'representa', 'jane', 'rochester',
    ];

    foreach (explode(' ', $normalizedMessage) as $token) {
        if ($token === '' || mb_strlen($token, 'UTF-8') < 3) {
            continue;
        }

        if (in_array($token, $stopWords, true)) {
            continue;
        }

        $terms[] = $token;
    }

    $terms = array_values(array_unique($terms));

    usort($terms, static fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

    return $terms;
}

function searchGlossaryByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT concept, definition FROM glossary WHERE work_id = 1 AND (concept LIKE ? OR definition LIKE ?) LIMIT 1",
        'ss',
        [$like, $like]
    );

    if (!$row) {
        return null;
    }

    return "📘 " . cleanDatabaseText($row['concept']) . "\n\n" . cleanDatabaseText($row['definition'], 1200);
}

function searchBlocksByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT titulo, concepto_clave, nota_chatbot, texto_curado
         FROM blocks
         WHERE work_id = 1
         AND (titulo LIKE ? OR concepto_clave LIKE ? OR nota_chatbot LIKE ? OR texto_curado LIKE ?)
         LIMIT 1",
        'ssss',
        [$like, $like, $like, $like]
    );

    if (!$row) {
        return null;
    }

    $parts = [];

    if (!empty($row['concepto_clave'])) {
        $parts[] = cleanDatabaseText($row['concepto_clave'], 220);
    }

    if (!empty($row['nota_chatbot'])) {
        $parts[] = cleanDatabaseText($row['nota_chatbot'], 420);
    }

    if (empty($parts) && !empty($row['texto_curado'])) {
        $parts[] = cleanDatabaseText($row['texto_curado'], 1200);
    }

    return "📖 " . cleanDatabaseText($row['titulo'] ?? 'Pasaje relacionado') . "\n\n" . implode("\n\n", $parts);
}

function searchSummaryByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT chapter, contenido FROM summaries WHERE work_id = 1 AND contenido LIKE ? ORDER BY chapter LIMIT 1",
        's',
        [$like]
    );

    if (!$row) {
        return null;
    }

    return '📖 Capítulo ' . (int) $row['chapter'] . "\n\n" . cleanDatabaseText($row['contenido'], 1400);
}

function searchWorkContextByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT content
         FROM work_context
         WHERE work_id = 1 AND content LIKE ?
         LIMIT 1",
        's',
        [$like]
    );

    if (!$row) {
        return null;
    }

    return '🏛️ Contexto general de la obra' . "\n\n" . cleanDatabaseText($row['content'], 1400);
}

function searchHistoricalContextByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT section, content
         FROM work_historical_context
         WHERE work_id = 1 AND (section LIKE ? OR content LIKE ?)
         LIMIT 1",
        'ss',
        [$like, $like]
    );

    if (!$row) {
        return null;
    }

    return '🏛️ ' . cleanDatabaseText($row['section']) . "\n\n" . cleanDatabaseText($row['content'], 1400);
}

function searchSymbolsByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT simbolo_id, contenido
         FROM symbols
         WHERE work_id = 1 AND (simbolo_id LIKE ? OR contenido LIKE ?)
         LIMIT 1",
        'ss',
        [$like, $like]
    );

    if (!$row) {
        return null;
    }

    return '🎭 Símbolo: ' . getReadableLabel($row['simbolo_id']) . "\n\n" . cleanDatabaseText($row['contenido'], 1400);
}

function searchThemesByTerm(string $term, mysqli $conn): ?string
{
    $like = '%' . $term . '%';
    $row = fetchSingleRow(
        $conn,
        "SELECT tema_id, contenido
         FROM themes
         WHERE work_id = 1 AND (tema_id LIKE ? OR contenido LIKE ?)
         LIMIT 1",
        'ss',
        [$like, $like]
    );

    if (!$row) {
        return null;
    }

    return '📚 Tema: ' . getReadableLabel($row['tema_id']) . "\n\n" . cleanDatabaseText($row['contenido'], 1400);
}

function searchKnowledgeBase(string $normalizedMessage, mysqli $conn): ?string
{
    $terms = buildSearchTerms($normalizedMessage);
    $matches = [];
    $seen = [];

    foreach ($terms as $term) {
        $searchers = [
            'searchGlossaryByTerm',
            'searchBlocksByTerm',
            'searchSummaryByTerm',
            'searchWorkContextByTerm',
            'searchHistoricalContextByTerm',
            'searchSymbolsByTerm',
            'searchThemesByTerm',
        ];

        foreach ($searchers as $searcher) {
            $result = $searcher($term, $conn);
            if ($result === null) {
                continue;
            }

            if (isset($seen[$result])) {
                continue;
            }

            $seen[$result] = true;
            $matches[] = $result;

            if (count($matches) >= 2) {
                break 2;
            }
        }
    }

    if (empty($matches)) {
        return null;
    }

    if (count($matches) === 1) {
        return appendFollowUpSuggestions(
            $matches[0],
            [
                'pregunta por algo relacionado',
                'busca otro tema',
            ]
        );
    }

    return appendFollowUpSuggestions(
        "He encontrado dos resultados relacionados:\n\nResultado 1:\n" . $matches[0] . "\n\nResultado 2:\n" . $matches[1],
        [
            'pregunta algo más específ ico',
            'intenta otra búsqueda',
        ]
    );
}

function getFallbackResponse(): string
{
    return "No he encontrado nada al respecto en mi base de datos. Intenta una pregunta más específica sobre Jane Eyre.";
}

function getBotResponse(string $message, mysqli $conn): string
{
    $normalizedMessage = normalizeText($message);

    if ($normalizedMessage === '') {
        return 'Escribe tu pregunta sobre Jane Eyre.';
    }

    if (isGreetingMessage($normalizedMessage)) {
        return '¡Hola! Soy Litto. Busco información sobre Jane Eyre en mi base de datos. ¿Qué quieres saber?';
    }

    if (isOutOfScopeQuestion($normalizedMessage)) {
        return getOutOfScopeResponse();
    }

    if (isAuthorQuery($normalizedMessage)) {
        return getAuthorResponse($conn);
    }

    if (isCharacterQuery($normalizedMessage)) {
        return getCharacterResponse($normalizedMessage, $conn);
    }

    if (isChapterQuery($normalizedMessage)) {
        return getChapterResponse($normalizedMessage, $conn);
    }

    if (isSymbolQuery($normalizedMessage)) {
        return getSymbolResponse($normalizedMessage, $conn);
    }

    if (isContextQuery($normalizedMessage)) {
        return getContextResponse($normalizedMessage, $conn);
    }

    if (isThemeQuery($normalizedMessage)) {
        return getThemeResponse($normalizedMessage, $conn);
    }

    if (!hasJaneEyreScopeSignal($normalizedMessage)) {
        return getOutOfScopeResponse();
    }

    $searchResult = searchKnowledgeBase($normalizedMessage, $conn);
    if ($searchResult !== null) {
        return $searchResult;
    }

    return getFallbackResponse();
}

if (!isset($_SESSION['user_id'])) {
    sendError('Debes iniciar sesión para usar el chatbot.', 401);
}

applyRateLimit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Método no permitido.', 405);
}

$conn = null;

try {
    require_once 'database.php';

    if (!$conn || $conn->connect_error) {
        sendError('No se ha podido conectar con la base de datos. Comprueba que XAMPP y MySQL estén activos.', 503);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['message'])) {
        sendError('Solicitud inválida. Falta el campo "message".', 400);
    }

    $userMessage = sanitizeInput($input['message']);
    if ($userMessage === '') {
        sendError('Escribe una pregunta antes de enviar el mensaje.', 400);
    }

    $response = getBotResponse($userMessage, $conn);

    sendJson([
        'response' => $response,
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $exception) {
    error_log('Error en chatbot [' . date('Y-m-d H:i:s') . ']: ' . $exception->getMessage());
    sendError('Ha ocurrido un error al procesar tu mensaje. Inténtalo de nuevo en unos segundos.', 500);
} finally {
    if ($conn instanceof mysqli && !$conn->connect_error) {
        $conn->close();
    }
}
