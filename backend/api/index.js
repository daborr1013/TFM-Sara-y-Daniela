import bcrypt from 'bcryptjs';
import { parse as parseCookie, serialize as serializeCookie } from 'cookie';
import jwt from 'jsonwebtoken';
import pg from 'pg';

const { Pool } = pg;

const COOKIE_NAME = 'litterally_session';
const FALLBACK_ACTIVITIES = {
  1: { type: 'Tests', level: 'General', description: 'Prueba general de comprension' },
  2: { type: 'Rellenar', level: 'General', description: 'Actividad para completar espacios' },
  3: { type: 'Desarrollar', level: 'General', description: 'Actividad de respuesta abierta' },
  4: { type: 'Flashcards', level: 'General', description: 'Repaso con tarjetas' },
  5: { type: 'Juegos', level: 'General', description: 'Actividad ludica sobre la obra' },
  6: { type: 'Juegos', level: 'General', description: 'Juego de memoria de personajes' },
  10: { type: 'Tests', level: 'Capitulos 1-4', description: 'Test sobre los capitulos 1 a 4' },
  11: { type: 'Tests', level: 'Capitulos 5-10', description: 'Test sobre los capitulos 5 a 10' },
  12: { type: 'Tests', level: 'Capitulos 11+', description: 'Test sobre los capitulos posteriores al 10' },
};

let pool;
const tableCache = new Map();

export default async function handler(request, response) {
  applyCors(request, response);

  if (request.method === 'OPTIONS') {
    response.writeHead(204).end();
    return;
  }

  try {
    const url = new URL(request.url, `http://${request.headers.host || 'localhost'}`);
    const pathname = url.pathname.replace(/\/$/, '') || '/';

    if (request.method === 'GET' && pathname === '/api/health') {
      return sendJson(response, { ok: true });
    }

    if (request.method === 'POST' && pathname === '/api/auth/register') {
      return await register(request, response);
    }

    if (request.method === 'POST' && pathname === '/api/auth/login') {
      return await login(request, response);
    }

    if (request.method === 'POST' && pathname === '/api/auth/logout') {
      return logout(request, response);
    }

    if (request.method === 'GET' && pathname === '/api/me') {
      const user = await requireUser(request);
      return sendJson(response, { user });
    }

    if (request.method === 'GET' && pathname === '/api/progress') {
      const user = await requireUser(request);
      return await getProgress(response, user.id);
    }

    if (request.method === 'POST' && pathname === '/api/progress') {
      const user = await requireUser(request);
      return await saveProgress(request, response, user.id);
    }

    if (request.method === 'POST' && pathname === '/api/chatbot') {
      return await chatbot(request, response);
    }

    return sendJson(response, { error: 'Ruta no encontrada' }, 404);
  } catch (error) {
    const status = Number(error.statusCode || 500);
    return sendJson(response, { error: status >= 500 ? 'Error interno del servidor' : error.message }, status);
  }
}

function applyCors(request, response) {
  const origin = request.headers.origin;
  const configuredOrigins = (process.env.FRONTEND_ORIGIN || '')
    .split(',')
    .map((item) => normalizeOrigin(item))
    .filter(Boolean);
  const allowedOrigins = [
    ...configuredOrigins,
    'https://*.vercel.app',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
    'https://localhost:5173',
    'https://localhost:3000',
  ];

  if (!origin) return;

  if (originAllowed(origin, allowedOrigins)) {
    response.setHeader('Access-Control-Allow-Origin', origin);
    response.setHeader('Vary', 'Origin');
    response.setHeader('Access-Control-Allow-Credentials', 'true');
    response.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
    response.setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With');
  }
}

function originAllowed(origin, allowedOrigins) {
  const normalizedOrigin = normalizeOrigin(origin);

  if (allowedOrigins.length === 0) return true;

  return allowedOrigins.some((allowedOrigin) => {
    if (allowedOrigin === normalizedOrigin) return true;
    if (!allowedOrigin.includes('*')) return false;

    const escaped = allowedOrigin.replace(/[|\\{}()[\]^$+?.]/g, '\\$&').replace(/\*/g, '.*');
    return new RegExp(`^${escaped}$`).test(normalizedOrigin);
  });
}

function normalizeOrigin(value) {
  return String(value || '').trim().replace(/\/+$/, '');
}

function sendJson(response, payload, status = 200) {
  response.statusCode = status;
  response.setHeader('Content-Type', 'application/json; charset=utf-8');
  response.end(JSON.stringify(payload));
}

function httpError(message, statusCode) {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
}

async function readJson(request) {
  if (request.body && typeof request.body === 'object') {
    return request.body;
  }

  let raw = '';
  for await (const chunk of request) {
    raw += chunk;
  }

  if (!raw.trim()) return {};

  try {
    return JSON.parse(raw);
  } catch {
    throw httpError('JSON invalido', 400);
  }
}

function getPool() {
  if (pool) return pool;

  const connectionString = process.env.DATABASE_URL || process.env.POSTGRES_URL;

  if (!connectionString) {
    throw httpError('DATABASE_URL o POSTGRES_URL no configurado', 500);
  }

  const ssl = process.env.DATABASE_SSL === 'false'
    ? false
    : { rejectUnauthorized: false };

  pool = new Pool({
    connectionString,
    max: 5,
    ssl,
    idleTimeoutMillis: 10_000,
  });

  return pool;
}

async function query(sql, params = []) {
  const { rows } = await getPool().query(sql, params);
  return rows;
}

async function tableExists(tableName) {
  if (tableCache.has(tableName)) return tableCache.get(tableName);
  const rows = await query(
    'SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = $1 LIMIT 1',
    [tableName],
  );
  const exists = rows.length > 0;
  tableCache.set(tableName, exists);
  return exists;
}

async function register(request, response) {
  const body = await readJson(request);
  const name = String(body.name || '').trim();
  const email = String(body.email || '').trim().toLowerCase();
  const password = String(body.password || '');
  const passwordConfirmation = String(body.passwordConfirmation || '');

  if (!name || !email || !password || !passwordConfirmation) {
    throw httpError('Completa todos los campos.', 400);
  }

  if (name.length < 3) {
    throw httpError('El nombre debe tener al menos 3 caracteres.', 400);
  }

  if (password.length < 8) {
    throw httpError('La contrasena debe tener al menos 8 caracteres.', 400);
  }

  if (password !== passwordConfirmation) {
    throw httpError('Las contrasenas no coinciden.', 400);
  }

  const existing = await query('SELECT id FROM users WHERE email = $1 LIMIT 1', [email]);
  if (existing.length > 0) {
    throw httpError('Este correo ya esta registrado.', 409);
  }

  const hash = await bcrypt.hash(password, 12);
  await query('INSERT INTO users (nombre, email, "contraseña") VALUES ($1, $2, $3)', [name, email, hash]);

  sendJson(response, { ok: true }, 201);
}

async function login(request, response) {
  const body = await readJson(request);
  const email = String(body.email || '').trim().toLowerCase();
  const password = String(body.password || '');

  if (!email || !password) {
    throw httpError('Completa todos los campos.', 400);
  }

  const rows = await query(
    'SELECT id, nombre, email, "contraseña", fecha_registro FROM users WHERE email = $1 LIMIT 1',
    [email],
  );

  if (rows.length === 0) {
    throw httpError('El correo no esta registrado.', 401);
  }

  const userRow = rows[0];
  const storedPassword = String(userRow['contraseña'] || '');
  const bcryptHash = storedPassword.startsWith('$2a$') || storedPassword.startsWith('$2b$') || storedPassword.startsWith('$2y$');
  const validPassword = bcryptHash
    ? await bcrypt.compare(password, storedPassword)
    : password === storedPassword;

  if (!validPassword) {
    throw httpError('Contrasena incorrecta.', 401);
  }

  if (!bcryptHash) {
    const hash = await bcrypt.hash(password, 12);
    await query('UPDATE users SET "contraseña" = $1 WHERE id = $2', [hash, userRow.id]);
  }

  const user = normalizeUser(userRow);
  const token = jwt.sign({ sub: user.id }, jwtSecret(), { expiresIn: '7d' });
  response.setHeader('Set-Cookie', sessionCookie(request, token));

  sendJson(response, { user, token });
}

async function logout(request, response) {
  response.setHeader('Set-Cookie', serializeCookie(COOKIE_NAME, '', {
    httpOnly: true,
    maxAge: 0,
    path: '/',
    sameSite: cookieSameSite(request),
    secure: cookieSecure(),
  }));
  sendJson(response, { ok: true });
}

async function requireUser(request) {
  const cookies = parseCookie(request.headers.cookie || '');
  const bearer = request.headers.authorization?.startsWith('Bearer ')
    ? request.headers.authorization.slice('Bearer '.length)
    : '';
  const token = cookies[COOKIE_NAME] || bearer;

  if (!token) {
    throw httpError('No autorizado', 401);
  }

  let decoded;
  try {
    decoded = jwt.verify(token, jwtSecret());
  } catch {
    throw httpError('Sesion caducada', 401);
  }

  const rows = await query('SELECT id, nombre, email, fecha_registro FROM users WHERE id = $1 LIMIT 1', [decoded.sub]);
  if (rows.length === 0) {
    throw httpError('Usuario no encontrado', 401);
  }

  return normalizeUser(rows[0]);
}

function normalizeUser(row) {
  const registeredAt = row.fecha_registro ? new Date(row.fecha_registro) : null;
  return {
    id: row.id,
    name: row.nombre,
    email: row.email,
    registeredAt: registeredAt ? registeredAt.toISOString() : null,
    registeredYear: registeredAt ? String(registeredAt.getFullYear()) : '',
  };
}

function jwtSecret() {
  return process.env.JWT_SECRET || 'dev-only-change-me';
}

function sessionCookie(request, token) {
  return serializeCookie(COOKIE_NAME, token, {
    httpOnly: true,
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
    sameSite: cookieSameSite(request),
    secure: cookieSecure(),
  });
}

function cookieSameSite(request) {
  if (process.env.NODE_ENV !== 'production') return 'lax';
  return isCrossSiteRequest(request) ? 'none' : 'lax';
}

function cookieSecure() {
  return process.env.NODE_ENV === 'production';
}

function isCrossSiteRequest(request) {
  const origin = request.headers.origin;
  if (!origin) return false;

  try {
    const originHost = new URL(origin).host;
    const requestHost = request.headers['x-forwarded-host'] || request.headers.host || '';
    return Boolean(requestHost) && originHost !== requestHost;
  } catch {
    return false;
  }
}

async function getProgress(response, userId) {
  const progress = await progressRows(userId);
  const stats = buildStats(progress);
  const categories = buildCategories(progress);
  sendJson(response, { progress, stats, categories });
}

async function saveProgress(request, response, userId) {
  const body = await readJson(request);
  const activityId = Number(body.activityId);
  const score = Math.max(0, Math.min(100, Number(body.score || 0)));
  const completed = body.completed === false ? 0 : 1;

  if (!Number.isInteger(activityId) || activityId <= 0) {
    throw httpError('activityId requerido', 400);
  }

  const existing = await query('SELECT id FROM user_progress WHERE user_id = $1 AND activity_id = $2 LIMIT 1', [userId, activityId]);

  if (existing.length > 0) {
    await query(
      'UPDATE user_progress SET puntuacion = $1, completado = $2, fecha = NOW() WHERE id = $3',
      [score, completed, existing[0].id],
    );
  } else {
    await query(
      'INSERT INTO user_progress (user_id, activity_id, puntuacion, completado) VALUES ($1, $2, $3, $4)',
      [userId, activityId, score, completed],
    );
  }

  sendJson(response, { ok: true });
}

async function progressRows(userId) {
  const hasActivities = await tableExists('activities');
  const hasWorks = hasActivities && await tableExists('works');

  let sql = 'SELECT up.id, up.activity_id AS "activityId", up.puntuacion AS score, up.completado AS completed, up.fecha AS date';

  if (hasActivities) {
    sql += ', a.tipo AS type, a.nivel AS level, a.descripcion AS description';
  }

  if (hasWorks) {
    sql += ', w.titulo AS "workName"';
  }

  sql += ' FROM user_progress up';

  if (hasActivities) {
    sql += ' LEFT JOIN activities a ON up.activity_id = a.id';
  }

  if (hasWorks) {
    sql += ' LEFT JOIN works w ON a.work_id = w.id';
  }

  sql += ' WHERE up.user_id = $1 ORDER BY up.fecha DESC';

  const rows = await query(sql, [userId]);

  return rows.map((row) => {
    const fallback = FALLBACK_ACTIVITIES[row.activityId] || { type: 'Actividad', level: 'General', description: 'Actividad de Jane Eyre' };
    return {
      id: row.id,
      activityId: row.activityId,
      score: Number(row.score || 0),
      completed: Boolean(row.completed),
      date: row.date,
      type: row.type || fallback.type,
      level: row.level || fallback.level,
      description: row.description || fallback.description,
      workName: row.workName || 'Jane Eyre',
    };
  });
}

function buildStats(progress) {
  const scores = progress.map((item) => Number(item.score || 0));
  const completed = progress.filter((item) => item.completed).length;
  const total = progress.length;
  const sum = scores.reduce((left, right) => left + right, 0);

  return {
    totalActivities: total,
    completed,
    averageScore: total > 0 ? sum / total : 0,
    bestScore: scores.length > 0 ? Math.max(...scores) : 0,
    worstScore: scores.length > 0 ? Math.min(...scores) : 0,
  };
}

function buildCategories(progress) {
  const grouped = new Map();

  for (const item of progress) {
    const current = grouped.get(item.type) || { type: item.type, total: 0, completed: 0, scoreSum: 0 };
    current.total += 1;
    current.completed += item.completed ? 1 : 0;
    current.scoreSum += item.score || 0;
    grouped.set(item.type, current);
  }

  return [...grouped.values()]
    .map((item) => ({ ...item, average: item.total > 0 ? item.scoreSum / item.total : 0, scoreSum: undefined }))
    .sort((left, right) => right.total - left.total);
}

async function chatbot(request, response) {
  const body = await readJson(request);
  const message = String(body.message || '').trim();

  if (!message) {
    throw httpError('Escribe una pregunta.', 400);
  }

  const normalized = normalizeText(message);

  if (isGreetingOrHelp(normalized)) {
    return sendJson(response, {
      response: 'Hola. Puedo ayudarte con personajes, capitulos, temas, simbolos, glosario y contexto de Jane Eyre.',
    });
  }

  if (isOutOfScopeQuestion(normalized)) {
    return sendJson(response, { response: getOutOfScopeResponse() });
  }

  const result = await searchKnowledge(message, normalized);
  sendJson(response, {
    response: result || getOutOfScopeResponse(),
  });
}

const CHARACTER_ALIASES = [
  { aliases: ['edward rochester', 'sr rochester', 'senor rochester', 'mr rochester', 'rochester'], patterns: ['Edward Rochester'] },
  { aliases: ['bertha mason', 'bertha'], patterns: ['Bertha Mason'] },
  { aliases: ['grace poole'], patterns: ['Grace Poole'] },
  { aliases: ['blanche ingram', 'ingram'], patterns: ['Blanche Ingram'] },
  { aliases: ['helen burns', 'helen'], patterns: ['Helen Burns'] },
  { aliases: ['senora temple', 'miss temple', 'temple'], patterns: ['Señora Temple'] },
  { aliases: ['senora reed', 'mrs reed'], patterns: ['Señora Reed'] },
  { aliases: ['senora fairfax', 'senora fairfaix', 'mrs fairfax', 'fairfax', 'fairfaix'], patterns: ['Señora Fairfaix', 'Señora Fairfax'] },
  { aliases: ['adele varens', 'adele'], patterns: ['Adèle Varens', 'Adele Varens'] },
  { aliases: ['bessie'], patterns: ['Bessie'] },
  { aliases: ['diana rivers', 'diana'], patterns: ['Diana Rivers'] },
  { aliases: ['mary rivers', 'mary'], patterns: ['Mary Rivers'] },
  { aliases: ['st john rivers', 'st john', 'john rivers'], patterns: ['John Rivers'] },
  { aliases: ['john reed', 'primo john'], patterns: ['John Reed'] },
  { aliases: ['lloyd', 'senor lloyd', 'sr lloyd'], patterns: ['Lloyd'] },
  { aliases: ['brocklehurst', 'senor brocklehurst', 'sr brocklehurst'], patterns: ['Señor Brocklehurst'] },
  { aliases: ['georgiana reed', 'georgiana', 'georgina reed', 'georgina'], patterns: ['Georgina Reed'] },
  { aliases: ['eliza reed', 'eliza'], patterns: ['Eliza Reed'] },
  { aliases: ['jane eyre', 'jane'], patterns: ['Jane Eyre'] },
];

function hasPhrase(normalized, phrase) {
  const escaped = normalizeText(phrase).replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+');
  return new RegExp(`(?:^|\\s)${escaped}(?:$|\\s)`).test(normalized);
}

function hasAnyPhrase(normalized, phrases) {
  return phrases.some((phrase) => hasPhrase(normalized, phrase));
}

function resolveCharacterNames(normalized) {
  for (const entry of CHARACTER_ALIASES) {
    if (entry.aliases.some((alias) => hasPhrase(normalized, alias))) {
      return entry.patterns;
    }
  }

  return [];
}

function isGreetingOrHelp(normalized) {
  return ['hola', 'hola litto', 'hi', 'hey', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'ayuda'].includes(normalized);
}

function hasJaneEyreScopeSignal(normalized) {
  if (resolveCharacterNames(normalized).length > 0) return true;

  return hasAnyPhrase(normalized, [
    'jane eyre', 'jane', 'rochester', 'bronte', 'charlotte', 'thornfield', 'lowood', 'gateshead',
    'moor house', 'ferndean', 'bertha', 'adele', 'helen', 'bessie', 'reed', 'rivers',
    'personaje', 'personajes', 'protagonista', 'capitulo', 'chapter', 'resumen', 'resumeme',
    'sinopsis', 'argumento', 'trama', 'tema', 'temas', 'simbolo', 'simbolismo', 'glosario',
    'fuego', 'hielo', 'naturaleza', 'cuarto rojo', 'casa', 'luz', 'oscuridad', 'libertad',
    'moralidad', 'clase social', 'independencia', 'desigualdad', 'religion', 'justicia',
    'resurgam', 'institutriz', 'termino', 'concepto', 'contexto', 'historico', 'victoriana',
    'gotico', 'romanticismo', 'autor', 'autora', 'obra', 'novela', 'libro',
  ]);
}

function isOutOfScopeQuestion(normalized) {
  if (hasJaneEyreScopeSignal(normalized)) return false;

  return hasAnyPhrase(normalized, [
    'capital', 'receta', 'cocina', 'programacion', 'codigo', 'matematicas', 'fisica',
    'quimica', 'biologia', 'futbol', 'deporte', 'noticias', 'politica', 'musica',
    'pelicula', 'serie', 'clima', 'tiempo', 'viaje', 'hotel', 'restaurante', 'traduce',
    'traducir', 'escribe un poema', 'hazme', 'cuentame un chiste',
  ]);
}

function getOutOfScopeResponse() {
  return 'Solo puedo ayudarte con preguntas sobre Jane Eyre: personajes, capitulos, temas, simbolos, glosario y contexto historico. Si quieres, reformula tu pregunta relacionandola con la novela.';
}

function getJaneRochesterResponse() {
  return [
    'Jane y Rochester forman la relacion central de la novela.',
    '',
    'Al principio, Rochester reconoce en Jane una igual intelectual: no la trata solo como institutriz, sino como alguien capaz de responderle con independencia y criterio. Ese vinculo se vuelve amoroso, pero tambien conflictivo, porque el secreto de Bertha rompe la confianza y obliga a Jane a elegir su dignidad antes que el deseo.',
    '',
    'El final recompone la relacion desde otra posicion: Jane vuelve cuando ya es independiente y Rochester ha perdido parte de su antiguo poder. Por eso su union final funciona como una relacion mas igualitaria, no como un rescate romantico simple.',
  ].join('\n');
}

async function searchKnowledge(message, normalized) {
  const terms = normalizeText(message)
    .split(' ')
    .filter((term) => term.length >= 4 && !STOP_WORDS.has(term))
    .slice(0, 4);

  const characterNames = resolveCharacterNames(normalized);
  if (characterNames.length > 0) {
    const character = await firstCharacterByName(characterNames);
    if (character) {
      return formatCharacter(character);
    }
  }

  if (terms.length === 0) {
    return null;
  }

  if (!hasJaneEyreScopeSignal(normalized)) {
    return null;
  }

  if (hasPhrase(normalized, 'jane') && hasPhrase(normalized, 'rochester')) {
    return getJaneRochesterResponse();
  }

  const exactCharacterNames = resolveCharacterNames(normalized);
  if (exactCharacterNames.length > 0) {
    const character = await firstCharacterByName(exactCharacterNames);
    if (character) {
      return formatCharacter(character);
    }
  }

  if (/(personaje|jane|rochester|bertha|helen|bessie|reed|rivers|adele)/.test(normalized)) {
    const character = await firstLike('characters', ['nombre', 'descripcion', 'rol', 'relaciones'], terms);
    if (character) {
      return formatCharacter(character);
    }
  }

  if (/(glosario|termino|concepto|palabra)/.test(normalized)) {
    const glossary = await firstLike('glossary', ['concept', 'definition'], terms);
    if (glossary) {
      return `${glossary.concept}: ${cleanText(glossary.definition, 650)}`;
    }
  }

  if (/(tema|simbolo|fuego|hielo|naturaleza|roja|habitacion)/.test(normalized)) {
    const symbol = await firstLike('symbols', ['contenido'], terms);
    if (symbol) {
      return cleanText(symbol.contenido, 1400);
    }

    const theme = await firstLike('themes', ['contenido'], terms);
    if (theme) {
      return cleanText(theme.contenido, 1400);
    }
  }

  if (/(capitulo|resumen|obra|historia)/.test(normalized)) {
    const summary = await firstLike('summaries', ['chapter', 'tipo', 'contenido'], terms);
    if (summary) {
      return `${summary.chapter || 'Resumen'}: ${cleanText(summary.contenido, 1400)}`;
    }
  }

  if (/(contexto|historico|bronte|charlotte|victoriana|inglaterra)/.test(normalized)) {
    const historical = await firstLike('work_historical_context', ['section', 'content'], terms);
    if (historical) {
      return `${historical.section || 'Contexto historico'}: ${cleanText(historical.content, 1400)}`;
    }

    const context = await firstLike('work_context', ['content'], terms);
    if (context) {
      return cleanText(context.content, 1400);
    }
  }

  const genericSearches = [
    ['characters', ['nombre', 'descripcion', 'rol', 'relaciones']],
    ['glossary', ['concept', 'definition']],
    ['summaries', ['chapter', 'contenido']],
    ['work_historical_context', ['section', 'content']],
    ['work_context', ['content']],
    ['symbols', ['contenido']],
    ['themes', ['contenido']],
  ];

  for (const [table, columns] of genericSearches) {
    const row = await firstLike(table, columns, terms);
    if (row) {
      return cleanText(Object.values(row).filter(Boolean).join(': '), 1400);
    }
  }

  return null;
}

function isGreetingOrHelp(normalized) {
  return [
    'hola',
    'hola litto',
    'buenas',
    'buenos dias',
    'buenas tardes',
    'buenas noches',
    'que tal',
    'ayuda',
  ].includes(normalized);
}

const STOP_WORDS = new Set([
  'quien',
  'sobre',
  'como',
  'donde',
  'cuando',
  'porque',
  'explica',
  'significa',
  'simboliza',
  'representa',
  'personaje',
  'personajes',
]);

const CHARACTER_ALIASES = [
  { aliases: ['edward rochester', 'sr rochester', 'senor rochester', 'mr rochester', 'rochester'], names: ['Edward Rochester'] },
  { aliases: ['bertha mason', 'bertha'], names: ['Bertha Mason'] },
  { aliases: ['grace poole'], names: ['Grace Poole'] },
  { aliases: ['blanche ingram', 'ingram'], names: ['Blanche Ingram'] },
  { aliases: ['helen burns', 'helen'], names: ['Helen Burns'] },
  { aliases: ['senora temple', 'miss temple', 'temple'], names: ['Señora Temple', 'SeÃ±ora Temple'] },
  { aliases: ['senora reed', 'mrs reed'], names: ['Señora Reed', 'SeÃ±ora Reed'] },
  { aliases: ['senora fairfax', 'senora fairfaix', 'mrs fairfax', 'fairfax', 'fairfaix'], names: ['Señora Fairfaix', 'Señora Fairfax', 'SeÃ±ora Fairfaix', 'SeÃ±ora Fairfax'] },
  { aliases: ['adele varens', 'adele'], names: ['Adèle Varens', 'Adele Varens', 'AdÃ¨le Varens'] },
  { aliases: ['bessie'], names: ['Bessie'] },
  { aliases: ['diana rivers', 'diana'], names: ['Diana Rivers'] },
  { aliases: ['mary rivers', 'mary'], names: ['Mary Rivers'] },
  { aliases: ['st john rivers', 'st john', 'john rivers'], names: ['John Rivers'] },
  { aliases: ['john reed', 'primo john'], names: ['John Reed'] },
  { aliases: ['lloyd', 'senor lloyd', 'sr lloyd'], names: ['Lloyd'] },
  { aliases: ['brocklehurst', 'senor brocklehurst', 'sr brocklehurst'], names: ['Señor Brocklehurst', 'SeÃ±or Brocklehurst'] },
  { aliases: ['georgiana reed', 'georgiana', 'georgina reed', 'georgina'], names: ['Georgina Reed'] },
  { aliases: ['eliza reed', 'eliza'], names: ['Eliza Reed'] },
  { aliases: ['jane eyre', 'jane'], names: ['Jane Eyre'] },
];

function hasPhrase(normalized, phrase) {
  const escaped = phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+');
  return new RegExp(`(?:^|\\s)${escaped}(?:$|\\s)`).test(normalized);
}

function resolveCharacterNames(normalized) {
  for (const entry of CHARACTER_ALIASES) {
    if (entry.aliases.some((alias) => hasPhrase(normalized, alias))) {
      return entry.names;
    }
  }

  return [];
}

async function firstCharacterByName(names) {
  if (!await tableExists('characters')) return null;

  const params = names.map((name) => name.toLowerCase());
  const placeholders = params.map((_, index) => `$${index + 1}`).join(', ');
  const rows = await query(
    `SELECT * FROM characters WHERE LOWER(nombre::text) IN (${placeholders}) LIMIT 1`,
    params,
  );

  return rows[0] || null;
}

function formatCharacter(character) {
  const parts = [`${character.nombre}: ${cleanText(character.descripcion, 500)}`];

  if (character.rol) {
    parts.push(`Rol: ${cleanText(character.rol, 200)}`);
  }

  if (character.relaciones) {
    parts.push(`Relaciones: ${cleanText(character.relaciones, 250)}`);
  }

  return parts.join(' ');
}

async function firstLike(table, columns, terms) {
  if (!await tableExists(table)) return null;

  const conditions = [];
  const params = [];

  for (const term of terms) {
    for (const column of columns) {
      conditions.push(`LOWER(${column}::text) LIKE $${params.length + 1}`);
      params.push(`%${term}%`);
    }
  }

  const rows = await query(
    `SELECT * FROM ${table} WHERE ${conditions.join(' OR ')} LIMIT 1`,
    params,
  );

  return rows[0] || null;
}

function normalizeText(value) {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function cleanText(value, limit) {
  const text = String(value || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  if (text.length <= limit) return text;
  return `${text.slice(0, limit).replace(/\s+\S*$/, '')}...`;
}
