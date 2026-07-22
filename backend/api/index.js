import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import bcrypt from 'bcryptjs';
import { parse as parseCookie, serialize as serializeCookie } from 'cookie';
import jwt from 'jsonwebtoken';
import pg from 'pg';

const { Pool } = pg;

const API_DIR = dirname(fileURLToPath(import.meta.url));
const COOKIE_NAME = 'litterally_session';
const SEED_SCHEMA_PATH = join(API_DIR, '..', 'schema', 'supabase.sql');
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
let seedKnowledgeCache;
let seedUsersCache;

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
      return sendJson(response, {
        ok: true,
        service: 'litterally-backend',
        database: databaseConfigured() ? 'configured' : 'missing',
      });
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
    if (status >= 500) {
      console.error('API error:', formatErrorForLog(error));
    }

    const message = status >= 500 && !error.expose ? 'Error interno del servidor' : error.message;
    return sendJson(response, { error: message }, status);
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
    'http://localhost:5174',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5174',
    'http://127.0.0.1:3000',
    'https://localhost:5173',
    'https://localhost:5174',
    'https://localhost:3000',
  ];

  if (!origin) return;

  if (originAllowed(origin, allowedOrigins)) {
    response.setHeader('Access-Control-Allow-Origin', origin);
    response.setHeader('Vary', 'Origin');
    response.setHeader('Access-Control-Allow-Credentials', 'true');
    response.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
    response.setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With');
    response.setHeader('Access-Control-Max-Age', '86400');
  }
}

function originAllowed(origin, allowedOrigins) {
  const normalizedOrigin = normalizeOrigin(origin);

  if (allowedOrigins.length === 0) return true;

  return allowedOrigins.some((allowedOrigin) => {
    if (allowedOrigin === '*') return true;
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
  error.expose = statusCode < 500;
  return error;
}

function exposedHttpError(message, statusCode) {
  const error = httpError(message, statusCode);
  error.expose = true;
  return error;
}

function databaseConfigured() {
  return Boolean(process.env.DATABASE_URL || process.env.POSTGRES_URL);
}

function databaseSetupError(error) {
  const setupError = exposedHttpError(
    'Base de datos no disponible. Revisa DATABASE_URL/POSTGRES_URL en Vercel y carga backend/schema/supabase.sql en Supabase.',
    503,
  );
  setupError.cause = error;
  return setupError;
}

function isDatabaseUnavailableError(error) {
  if (!error) return false;

  const code = String(error.code || '');
  const message = String(error.message || '');

  if (error.statusCode === 500 && /DATABASE_URL|POSTGRES_URL/i.test(message)) return true;
  if (code.startsWith('08')) return true;

  const knownDatabaseErrorCodes = [
    'ENOTFOUND',
    'ECONNREFUSED',
    'ECONNRESET',
    'ETIMEDOUT',
    'EAI_AGAIN',
    '3D000',
    '28P01',
    '42P01',
    '42703',
  ];
  const knownDatabaseErrorMessage = /DATABASE_URL|POSTGRES_URL|relation .* does not exist|column .* does not exist|password authentication failed|getaddrinfo|connect ECONNREFUSED/i;

  return knownDatabaseErrorCodes.includes(code) || knownDatabaseErrorMessage.test(message);
}

function formatErrorForLog(error) {
  return {
    message: error?.message,
    code: error?.code,
    statusCode: error?.statusCode,
    cause: error?.cause ? {
      message: error.cause.message,
      code: error.cause.code,
      statusCode: error.cause.statusCode,
    } : undefined,
  };
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

async function usersByEmail(email) {
  return await query(
    'SELECT id, nombre, email, "contraseña", fecha_registro FROM users WHERE email = $1 LIMIT 1',
    [email],
  );
}

async function usersById(id) {
  return await query('SELECT id, nombre, email, fecha_registro FROM users WHERE id = $1 LIMIT 1', [id]);
}

function seedUsers() {
  if (seedUsersCache) return seedUsersCache;

  const users = [];

  try {
    const sql = readFileSync(SEED_SCHEMA_PATH, 'utf8');
    const block = getSqlInsertBlock(sql, 'users');
    const rowPattern = /\((\d+),\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)'\)(?:,|$)/g;
    let match;

    while ((match = rowPattern.exec(block)) !== null) {
      users.push({
        id: Number.parseInt(match[1], 10),
        nombre: decodeSqlString(match[2]),
        email: decodeSqlString(match[3]).toLowerCase(),
        'contraseña': decodeSqlString(match[4]),
        fecha_registro: decodeSqlString(match[5]),
        __seed: true,
      });
    }
  } catch (error) {
    console.error('No se pudo cargar fallback local de usuarios:', error.message);
  }

  seedUsersCache = users;
  return seedUsersCache;
}

function seedUserByEmail(email) {
  return seedUsers().find((user) => user.email === String(email || '').toLowerCase());
}

function seedUserByToken(decoded) {
  const user = seedUserByEmail(decoded?.email);
  if (!user) return null;
  if (String(user.id) !== String(decoded?.sub)) return null;

  return user;
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

  let existing;
  try {
    existing = await query('SELECT id FROM users WHERE email = $1 LIMIT 1', [email]);
  } catch (error) {
    if (isDatabaseUnavailableError(error)) {
      throw databaseSetupError(error);
    }

    throw error;
  }

  if (existing.length > 0) {
    throw httpError('Este correo ya esta registrado.', 409);
  }

  const hash = await bcrypt.hash(password, 12);
  try {
    await query('INSERT INTO users (nombre, email, "contraseña") VALUES ($1, $2, $3)', [name, email, hash]);
  } catch (error) {
    if (isDatabaseUnavailableError(error)) {
      throw databaseSetupError(error);
    }

    throw error;
  }

  sendJson(response, { ok: true }, 201);
}

async function login(request, response) {
  const body = await readJson(request);
  const email = String(body.email || '').trim().toLowerCase();
  const password = String(body.password || '');

  if (!email || !password) {
    throw httpError('Completa todos los campos.', 400);
  }

  let rows;
  let usingSeedUser = false;
  let loginDatabaseError = null;

  try {
    rows = await usersByEmail(email);
  } catch (error) {
    if (!isDatabaseUnavailableError(error)) {
      throw error;
    }

    loginDatabaseError = error;
    console.error('Login database unavailable, trying seed user:', formatErrorForLog(error));
    const seedUser = seedUserByEmail(email);
    rows = seedUser ? [seedUser] : [];
    usingSeedUser = Boolean(seedUser);
  }

  if (rows.length === 0) {
    if (loginDatabaseError) {
      throw databaseSetupError(loginDatabaseError);
    }

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

  if (!bcryptHash && !usingSeedUser) {
    const hash = await bcrypt.hash(password, 12);
    try {
      await query('UPDATE users SET "contraseña" = $1 WHERE id = $2', [hash, userRow.id]);
    } catch (error) {
      console.error('No se pudo actualizar password legacy a bcrypt:', formatErrorForLog(error));
    }
  }

  const user = normalizeUser(userRow);
  const tokenPayload = usingSeedUser
    ? { sub: user.id, seed: true, email: user.email }
    : { sub: user.id };
  const token = jwt.sign(tokenPayload, jwtSecret(), { expiresIn: '7d' });
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

  let rows;
  try {
    rows = await usersById(decoded.sub);
  } catch (error) {
    if (isDatabaseUnavailableError(error) && decoded.seed) {
      const seedUser = seedUserByToken(decoded);
      if (seedUser) {
        return normalizeUser(seedUser);
      }
    }

    throw error;
  }

  if (rows.length === 0) {
    if (decoded.seed) {
      const seedUser = seedUserByToken(decoded);
      if (seedUser) {
        return normalizeUser(seedUser);
      }
    }

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
  let progress;
  try {
    progress = await progressRows(userId);
  } catch (error) {
    if (!isDatabaseUnavailableError(error)) {
      throw error;
    }

    console.error('Progress database unavailable, returning empty progress:', formatErrorForLog(error));
    progress = [];
  }

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

  try {
    if (!await tableExists('user_progress')) {
      throw exposedHttpError(
        'No se pudo guardar el progreso porque falta la tabla user_progress. Carga backend/schema/supabase.sql en la base de datos.',
        503,
      );
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
  } catch (error) {
    if (!isDatabaseUnavailableError(error)) {
      throw error;
    }

    console.error('Progress database unavailable, rejecting save:', formatErrorForLog(error));
    throw databaseSetupError(error);
  }

  sendJson(response, { ok: true });
}

async function progressRows(userId) {
  if (!await tableExists('user_progress')) {
    return [];
  }

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

  let result = null;
  try {
    result = await searchKnowledge(message, normalized);
  } catch (error) {
    if (!hasJaneEyreScopeSignal(normalized) || !isKnowledgeLookupError(error)) {
      throw error;
    }

    console.error('Error buscando conocimiento de Litto:', error);
  }

  sendJson(response, {
    response: result || (hasJaneEyreScopeSignal(normalized) ? getNoKnowledgeResponse() : getOutOfScopeResponse()),
  });
}

function isKnowledgeLookupError(error) {
  const status = Number(error?.statusCode || 0);
  return status >= 500 || typeof error?.code === 'string';
}

function hasAnyPhrase(normalized, phrases) {
  return phrases.some((phrase) => hasPhrase(normalized, phrase));
}

function hasJaneEyreScopeSignal(normalized) {
  if (resolveCharacterNames(normalized).length > 0) return true;
  if (hasStaticGlossaryTerm(normalized)) return true;

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

function getNoKnowledgeResponse() {
  return 'No he encontrado una respuesta clara en la base de conocimiento de Jane Eyre. Prueba a preguntar por un personaje, capitulo, tema, simbolo, glosario o contexto historico.';
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

const STATIC_CHARACTER_RESPONSES = new Map([
  ['Jane Eyre', [
    'Jane Eyre es la protagonista y narradora de la novela.',
    '',
    'Es una huerfana que crece en Gateshead bajo el maltrato de la familia Reed, se forma en Lowood y despues trabaja como institutriz en Thornfield Hall. Su historia gira alrededor de la busqueda de dignidad, independencia, amor y justicia moral.',
    '',
    'Jane destaca porque no acepta ser tratada como inferior: defiende su conciencia incluso cuando eso le cuesta seguridad, pertenencia o amor.',
  ].join('\n')],
  ['Edward Rochester', [
    'Edward Rochester es el dueno de Thornfield Hall y el gran interes amoroso de Jane.',
    '',
    'Es un heroe byroniano: oscuro, contradictorio, apasionado y marcado por un pasado que intenta ocultar. Reconoce la inteligencia de Jane y la trata como una igual, pero su secreto sobre Bertha Mason rompe la confianza y obliga a Jane a elegir su dignidad.',
    '',
    'Su evolucion final pasa por la perdida y la purificacion: tras el incendio de Thornfield queda vulnerable, y solo entonces puede reencontrarse con Jane en una relacion mas equilibrada.',
  ].join('\n')],
  ['Bertha Mason', [
    'Bertha Mason es la esposa secreta de Rochester, encerrada en el atico de Thornfield.',
    '',
    'Funciona como el gran secreto gotico de la novela y como obstaculo moral para el matrimonio entre Jane y Rochester. Tambien puede leerse como doble oscuro de Jane: representa la pasion, la rabia y la violencia que la sociedad victoriana intenta encerrar o borrar.',
  ].join('\n')],
  ['Helen Burns', [
    'Helen Burns es la amiga de Jane en Lowood.',
    '',
    'Representa la paciencia, el estoicismo y una forma de religiosidad basada en el perdon. Su influencia ayuda a Jane a controlar la rabia que trae de Gateshead, aunque Jane no adopta por completo su pasividad ante la injusticia.',
  ].join('\n')],
  ['Bessie', [
    'Bessie es la ninera de Gateshead y una de las primeras figuras de afecto para Jane.',
    '',
    'Aunque a veces es brusca, tambien le ofrece canciones, relatos y pequenos gestos de carino. Para Jane, Bessie es una primera fuente de imaginacion y consuelo dentro de una casa hostil.',
  ].join('\n')],
  ['Señora Reed', [
    'La senora Reed es la tia politica de Jane y su antagonista durante la infancia.',
    '',
    'Promete cuidar de Jane, pero la rechaza, la humilla y la encierra en el Cuarto Rojo. Su crueldad despierta en Jane una conciencia temprana de la injusticia y el deseo de defenderse.',
  ].join('\n')],
  ['John Reed', [
    'John Reed es el primo de Jane en Gateshead.',
    '',
    'Es violento, consentido y clasista. Su maltrato provoca la rebelion inicial de Jane y muestra el abuso de poder dentro de la familia Reed.',
  ].join('\n')],
  ['Eliza Reed', [
    'Eliza Reed es una de las primas de Jane.',
    '',
    'Es fria, calculadora y disciplinada. Contrasta con Georgina y sirve para mostrar un modelo de vida dominado por el control, el interes y la falta de afecto.',
  ].join('\n')],
  ['Georgina Reed', [
    'Georgina Reed es la prima menor de Jane.',
    '',
    'Es bella, mimada y superficial. Su trato privilegiado contrasta con el rechazo que sufre Jane y denuncia una sociedad que premia apariencia y estatus antes que valor moral.',
  ].join('\n')],
  ['Lloyd', [
    'El senor Lloyd es el boticario que atiende a Jane tras el episodio del Cuarto Rojo.',
    '',
    'Es el primer adulto que la escucha con cierta empatía. Al sugerir que Jane vaya a la escuela, abre la posibilidad de que salga de Gateshead.',
  ].join('\n')],
  ['Señor Brocklehurst', [
    'El senor Brocklehurst dirige Lowood y representa la hipocresia religiosa.',
    '',
    'Predica humildad y sacrificio para las huerfanas mientras su propia familia vive con privilegios. Su crueldad denuncia el uso de la religion como control social.',
  ].join('\n')],
  ['Señora Temple', [
    'La senora Temple es una figura protectora y educativa en Lowood.',
    '',
    'Ofrece a Jane justicia, serenidad y reconocimiento. Su influencia ayuda a Jane a confiar en su inteligencia y a construir una identidad mas estable.',
  ].join('\n')],
  ['Adèle Varens', [
    'Adele Varens es la pupila francesa de Rochester y alumna de Jane en Thornfield.',
    '',
    'A traves de Adele, Jane demuestra paciencia y capacidad como educadora. Tambien muestra la responsabilidad moral de Rochester, que se hace cargo de ella pese a su pasado ambiguo.',
  ].join('\n')],
  ['Grace Poole', [
    'Grace Poole es la sirvienta encargada de vigilar a Bertha Mason.',
    '',
    'Durante buena parte de la novela funciona como falsa sospechosa: Jane cree que ella causa las risas, incendios y sucesos extranos de Thornfield, ocultando asi el verdadero secreto del atico.',
  ].join('\n')],
  ['Blanche Ingram', [
    'Blanche Ingram es una aristocrata que parece candidata ideal para casarse con Rochester.',
    '',
    'Representa belleza, clase social y conveniencia, pero tambien superficialidad. Contrasta con Jane porque tiene el estatus que la sociedad valora, pero no la profundidad moral e intelectual que Rochester encuentra en Jane.',
  ].join('\n')],
  ['Señora Fairfax', [
    'La senora Fairfax es el ama de llaves de Thornfield.',
    '',
    'Recibe a Jane con amabilidad y aporta una sensacion de orden domestico. Tambien representa la mirada prudente de la sociedad ante la relacion desigual entre Jane y Rochester.',
  ].join('\n')],
  ['Diana Rivers', [
    'Diana Rivers es una de las primas que Jane encuentra en Moor House.',
    '',
    'Es inteligente, afectuosa e independiente. Junto con Mary, ofrece a Jane una familia basada en igualdad intelectual y carino real.',
  ].join('\n')],
  ['Mary Rivers', [
    'Mary Rivers es hermana de Diana y prima de Jane.',
    '',
    'Representa la mujer educada pero limitada por la pobreza. Su relacion con Jane muestra apoyo femenino, estudio compartido y pertenencia familiar.',
  ].join('\n')],
  ['John Rivers', [
    'St. John Rivers es primo de Jane y clérigo.',
    '',
    'Representa una moral fria, disciplinada y sacrificial. Quiere que Jane se case con él por deber religioso, no por amor, y por eso funciona como contraste con Rochester y con la libertad emocional de Jane.',
  ].join('\n')],
]);

const STATIC_SYMBOL_RESPONSES = [
  {
    keywords: ['fuego', 'hielo'],
    response: 'El fuego y el hielo representan dos extremos emocionales en Jane Eyre. El fuego se asocia con pasion, deseo, hogar y purificacion, sobre todo alrededor de Rochester y Thornfield. El hielo representa frialdad, represion, soledad y deber sin afecto, especialmente en Lowood y St. John Rivers. Jane debe encontrar equilibrio: demasiado fuego destruye, demasiado hielo apaga la vida interior.',
  },
  {
    keywords: ['cuarto rojo', 'habitacion roja', 'rojo'],
    response: 'El Cuarto Rojo simboliza trauma, encierro e injusticia. Es la habitacion donde Jane es castigada de nina y donde toma conciencia de su propia opresion. El color rojo concentra miedo, ira reprimida y despertar de la rebeldia. Tambien anticipa otros encierros femeninos de la novela, como el atico de Bertha.',
  },
  {
    keywords: ['thornfield', 'casa', 'atico'],
    response: 'Thornfield simboliza la casa respetable que esconde un secreto. En la planta visible hay vida social y apariencia; en el atico se oculta Bertha Mason y el pasado reprimido de Rochester. Cuando Thornfield arde, la novela convierte la destruccion de la casa en purificacion: el secreto ya no puede sostenerse.',
  },
  {
    keywords: ['luz', 'oscuridad', 'doble oscuro'],
    response: 'La luz y la oscuridad muestran conocimiento y secreto. La luz permite descubrir la verdad, aunque duela; la oscuridad rodea a Bertha Mason y al secreto de Thornfield. Bertha funciona como una figura oscura porque encarna lo reprimido: pasion, violencia, deseo y sufrimiento ocultos por la sociedad.',
  },
  {
    keywords: ['naturaleza', 'castano', 'arbol'],
    response: 'La naturaleza refleja el estado emocional de Jane. En los momentos de opresion aparece como frio o intemperie; cuando Jane ama o se siente libre, la naturaleza florece. El castano partido tras la declaracion de Rochester anticipa que esa union esta rota por un secreto moral.',
  },
  {
    keywords: ['pajaro', 'aire'],
    response: 'Los pajaros y el aire simbolizan libertad. Jane se asocia con aves porque desea escapar de jaulas sociales, familiares y amorosas. Incluso su apellido, Eyre, recuerda a air en ingles: movimiento, independencia y capacidad de atravesar espacios sin perder identidad.',
  },
  {
    keywords: ['madres', 'figuras maternas'],
    response: 'Las figuras maternas sustitutas ayudan a Jane a construirse. La senora Reed despierta su rebeldia por contraste; Bessie alimenta su imaginacion; la senora Temple le da educacion y templanza; la senora Fairfax le ofrece una primera sensacion de hogar; Diana y Mary le dan pertenencia familiar.',
  },
];

const STATIC_THEME_RESPONSES = [
  {
    keywords: ['amor', 'moralidad'],
    response: 'Amor y moralidad chocan en la relacion entre Jane y Rochester. Jane ama a Rochester, pero cuando descubre que sigue casado con Bertha decide marcharse para no traicionar su conciencia. La novela defiende un amor que no exige perder dignidad: Jane vuelve solo cuando puede elegir desde la independencia.',
  },
  {
    keywords: ['clase social', 'clases sociales', 'desigualdad', 'independencia', 'institutriz'],
    response: 'La clase social atraviesa toda la novela. Jane es pobre, huerfana e institutriz: educada pero socialmente vulnerable. Su independencia economica y moral es clave porque le permite dejar de depender de la caridad, del matrimonio o del poder de otros.',
  },
  {
    keywords: ['religion', 'espiritualidad', 'brocklehurst', 'helen', 'st john'],
    response: 'La religion aparece en tres modelos: Brocklehurst representa hipocresia y control; Helen Burns representa perdon y paciencia; St. John Rivers representa deber frio y sacrificio. Jane construye una espiritualidad propia, basada en conciencia, libertad moral y amor sin sometimiento.',
  },
  {
    keywords: ['justicia', 'justicia moral', 'justicia poetica'],
    response: 'La justicia en Jane Eyre es sobre todo moral y poetica. Los Reed, Brocklehurst y Rochester afrontan consecuencias por sus actos. Jane, en cambio, es recompensada con libertad, herencia, familia y amor porque no sacrifica su conciencia.',
  },
];

const STATIC_CONTEXT_RESPONSES = [
  {
    keywords: ['contexto', 'historico', 'victoriana', 'victoriano', 'inglaterra'],
    response: 'Jane Eyre se entiende dentro de la Inglaterra victoriana: una sociedad marcada por jerarquias de clase, moral religiosa, desigualdad de genero e ideal domestico femenino. La novela cuestiona esos limites al presentar a una mujer pobre e independiente que exige respeto intelectual, moral y afectivo.',
  },
  {
    keywords: ['romanticismo', 'ilustracion'],
    response: 'La novela combina Romanticismo e Ilustracion. Del Romanticismo toma la intensidad emocional, la naturaleza, lo sublime y la subjetividad; de la Ilustracion aparece la tension con la razon, el autocontrol y el juicio moral. Jane busca equilibrio entre pasion y conciencia.',
  },
  {
    keywords: ['gotico', 'gótico'],
    response: 'El gotico aparece en Thornfield: ruidos extranos, secretos, fuego, oscuridad, una mujer encerrada y una casa que parece esconder su propio subconsciente. Bronte usa el terror gotico para hablar de culpa, deseo reprimido y violencia domestica.',
  },
  {
    keywords: ['charlotte', 'bronte', 'brontë', 'autora'],
    response: 'Charlotte Bronte publico Jane Eyre en 1847 bajo el seudonimo Currer Bell. Su experiencia como institutriz, la educacion religiosa severa y la vida de las hermanas Bronte influyen en la novela, especialmente en Lowood, la figura de la institutriz y la defensa de una voz femenina propia.',
  },
  {
    keywords: ['legado', 'adaptaciones', 'por que seguimos leyendo'],
    response: 'Jane Eyre sigue leyendose porque combina novela de formacion, romance, critica social, gotico y una voz femenina muy moderna. Su protagonista no pide solo amor: exige igualdad, conciencia, libertad y derecho a narrarse a si misma.',
  },
];

const STATIC_STAGE_SUMMARIES = [
  {
    keywords: ['gateshead', 'infancia'],
    start: 1,
    end: 4,
    title: 'Resumen Gateshead (capitulos 1-4)',
    response: 'Jane vive oprimida en Gateshead con la familia Reed. El maltrato de John Reed y el encierro en el Cuarto Rojo despiertan su conciencia de injusticia. Tras hablar con el boticario Lloyd, se abre la posibilidad de ir a Lowood. Antes de marcharse, Jane desafia a la senora Reed y afirma por primera vez su dignidad.',
  },
  {
    keywords: ['lowood'],
    start: 5,
    end: 10,
    title: 'Resumen Lowood (capitulos 5-10)',
    response: 'Jane llega a Lowood, una escuela dura, fria y marcada por la disciplina religiosa de Brocklehurst. Alli conoce a Helen Burns y a la senora Temple, dos influencias decisivas. El brote de tifus revela la crueldad del sistema, Helen muere y Lowood mejora. Jane crece como alumna y maestra, pero decide buscar libertad fuera de la escuela.',
  },
  {
    keywords: ['thornfield', 'rochester', 'adele'],
    start: 11,
    end: 27,
    title: 'Resumen Thornfield (capitulos 11-27)',
    response: 'Jane llega a Thornfield como institutriz de Adele y conoce a Rochester. La atraccion entre ambos crece entre conversaciones intensas, secretos goticos y sucesos extranos atribuidos a Grace Poole. Rochester propone matrimonio, pero en la boda se descubre que ya esta casado con Bertha Mason. Jane rechaza ser amante y huye para proteger su conciencia.',
  },
  {
    keywords: ['moor house', 'whitcross', 'morton', 'st john', 'rivers'],
    start: 28,
    end: 35,
    title: 'Resumen Moor House y Morton (capitulos 28-35)',
    response: 'Tras huir de Thornfield, Jane queda sin dinero ni refugio hasta que Diana, Mary y St. John Rivers la acogen. Recupera dignidad trabajando como maestra y descubre que ellos son sus primos. Tambien hereda la fortuna de su tio y la reparte con ellos. St. John intenta casarse con Jane por deber religioso, pero ella rechaza una vida sin amor.',
  },
  {
    keywords: ['ferndean', 'regreso', 'final'],
    start: 36,
    end: 38,
    title: 'Resumen regreso a Rochester (capitulos 36-38)',
    response: 'Jane regresa a Thornfield y descubre la mansion destruida por el incendio provocado por Bertha, que muere en la caida. Rochester queda ciego y herido, viviendo retirado en Ferndean. Jane vuelve a el desde una posicion independiente: ya tiene familia, dinero y libertad. La novela termina con un matrimonio mas igualitario y una reconciliacion moral.',
  },
];

function getSeedKnowledge() {
  if (seedKnowledgeCache) return seedKnowledgeCache;

  const knowledge = {
    glossary: new Map(),
    summaries: new Map(),
  };

  try {
    const sql = readFileSync(SEED_SCHEMA_PATH, 'utf8');
    parseSeedGlossary(sql, knowledge.glossary);
    parseSeedSummaries(sql, knowledge.summaries);
  } catch (error) {
    console.error('No se pudo cargar fallback local de Litto:', error.message);
  }

  seedKnowledgeCache = knowledge;
  return seedKnowledgeCache;
}

function parseSeedGlossary(sql, glossary) {
  const block = getSqlInsertBlock(sql, 'glossary');
  const rowPattern = /\(\d+,\s*1,\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)'\)(?:,|$)/g;
  let match;

  while ((match = rowPattern.exec(block)) !== null) {
    const concept = decodeSqlString(match[1]);
    const definition = decodeSqlString(match[2]);
    glossary.set(normalizeText(concept), { concept, definition });
  }
}

function parseSeedSummaries(sql, summaries) {
  const block = getSqlInsertBlock(sql, 'summaries');
  const rowPattern = /\(\d+,\s*1,\s*(\d+),\s*'[^']*',\s*'([\s\S]*?)'\)(?:,|$)/g;
  let match;

  while ((match = rowPattern.exec(block)) !== null) {
    const chapter = Number.parseInt(match[1], 10);
    if (!Number.isInteger(chapter)) continue;

    summaries.set(chapter, cleanText(decodeSqlString(match[2]), 1600));
  }
}

function getSqlInsertBlock(sql, tableName) {
  const start = sql.indexOf(`INSERT INTO "${tableName}"`);
  if (start === -1) return '';

  const end = sql.indexOf('\n;', start);
  return end === -1 ? sql.slice(start) : sql.slice(start, end);
}

function decodeSqlString(value) {
  return String(value || '').replace(/''/g, "'");
}

function getStaticGlossaryResponse(normalized, { listWhenNoTerm = true } = {}) {
  const entry = findStaticGlossaryEntry(normalized);
  if (entry) {
    return `${entry.concept}: ${cleanText(entry.definition, 650)}`;
  }

  if (!listWhenNoTerm || !hasGlossaryIntent(normalized)) {
    return null;
  }

  const concepts = [...getSeedKnowledge().glossary.values()].map((item) => item.concept);
  if (concepts.length === 0) return null;

  return `Glosario de Jane Eyre: ${concepts.join(', ')}. Pregunta por cualquiera de estos terminos y te doy la definicion.`;
}

function findStaticGlossaryEntry(normalized) {
  for (const entry of getSeedKnowledge().glossary.values()) {
    if (glossaryEntryMatches(entry, normalized)) {
      return entry;
    }
  }

  return null;
}

function glossaryEntryMatches(entry, normalized) {
  const normalizedConcept = normalizeText(entry.concept);
  if (hasPhrase(normalized, normalizedConcept)) return true;

  return normalizedConcept
    .split(' ')
    .filter((term) => term.length >= 4)
    .some((term) => hasPhrase(normalized, term));
}

function hasStaticGlossaryTerm(normalized) {
  return Boolean(findStaticGlossaryEntry(normalized));
}

function hasGlossaryIntent(normalized) {
  return hasAnyPhrase(normalized, [
    'glosario',
    'termino',
    'terminos',
    'concepto',
    'conceptos',
    'palabra',
    'significa',
    'significado',
    'define',
    'definicion',
  ]);
}

function getStaticChapterResponse(chapterNumber) {
  const summary = getSeedKnowledge().summaries.get(chapterNumber);
  if (!summary) {
    return `No he encontrado el resumen del capitulo ${chapterNumber}. Puedo resumir capitulos del 1 al 38 si el numero existe en Jane Eyre.`;
  }

  return [
    `Capitulo ${chapterNumber}`,
    '',
    summary,
  ].join('\n');
}

function getStaticSummaryResponse(normalized) {
  if (!hasSummaryIntent(normalized)) return null;

  const range = extractChapterRange(normalized);
  if (range) {
    return getStaticChapterRangeResponse(range.start, range.end);
  }

  const stage = STATIC_STAGE_SUMMARIES.find((item) => item.keywords.some((keyword) => hasPhrase(normalized, keyword)));
  if (stage) {
    return `${stage.title}\n\n${stage.response}`;
  }

  if (hasAnyPhrase(normalized, ['capitulos', 'resumenes'])) {
    return 'Puedo resumir capitulos concretos de Jane Eyre del 1 al 38. Ejemplos: "resumen capitulo 1", "resumen capitulo 11", "resumen capitulos 5 a 10", "resumen de Lowood" o "resumen de Thornfield".';
  }

  return 'Jane Eyre cuenta la formacion moral de Jane, una huerfana que pasa de Gateshead a Lowood, Thornfield, Moor House y Ferndean. La novela sigue su busqueda de libertad, dignidad, amor e independencia, hasta que puede elegir a Rochester sin renunciar a su conciencia.';
}

function hasSummaryIntent(normalized) {
  return hasAnyPhrase(normalized, [
    'resumen',
    'resumenes',
    'resumeme',
    'sinopsis',
    'argumento',
    'trama',
    'capitulo',
    'capitulos',
    'chapter',
  ]);
}

function extractChapterRange(normalized) {
  if (!hasAnyPhrase(normalized, ['capitulos', 'resumenes'])) return null;

  const numbers = normalized
    .split(' ')
    .map((token) => Number.parseInt(token, 10))
    .filter((number) => Number.isInteger(number) && number >= 1 && number <= 38);

  if (numbers.length < 2) return null;

  const [left, right] = numbers;
  if (left === right) return null;

  return {
    start: Math.min(left, right),
    end: Math.max(left, right),
  };
}

function getStaticChapterRangeResponse(start, end) {
  const stage = STATIC_STAGE_SUMMARIES.find((item) => item.start === start && item.end === end);
  if (stage) {
    return `${stage.title}\n\n${stage.response}`;
  }

  const summaries = getSeedKnowledge().summaries;
  const count = end - start + 1;

  if (count > 6) {
    return `Resumen capitulos ${start}-${end}\n\nEs un tramo amplio. Pide un capitulo concreto para ver el detalle, por ejemplo "resumen capitulo ${start}".`;
  }

  const parts = [`Resumen capitulos ${start}-${end}`, ''];
  for (let chapter = start; chapter <= end; chapter += 1) {
    const summary = summaries.get(chapter);
    if (summary) {
      parts.push(`Capitulo ${chapter}: ${cleanText(summary, 360)}`);
    }
  }

  return parts.join('\n');
}

function getStaticKnowledgeResponse(normalized) {
  const context = getStaticEntryResponse(STATIC_CONTEXT_RESPONSES, normalized);
  if (context) return context;

  const symbol = getStaticEntryResponse(STATIC_SYMBOL_RESPONSES, normalized);
  if (symbol) return symbol;

  const theme = getStaticEntryResponse(STATIC_THEME_RESPONSES, normalized);
  if (theme && hasThemeIntent(normalized)) return theme;

  if (hasAnyPhrase(normalized, ['simbolo', 'simbolos', 'simbolismo'])) {
    return 'Los simbolos principales de Jane Eyre incluyen el fuego y el hielo, el Cuarto Rojo, Thornfield, la luz y la oscuridad, la naturaleza, los pajaros y las figuras maternas. Todos ayudan a explicar la libertad, el deseo, el miedo, la moralidad y la identidad de Jane.';
  }

  if (hasAnyPhrase(normalized, ['tema', 'temas'])) {
    return 'Los temas principales de Jane Eyre son amor y moralidad, independencia, clase social, desigualdad, religion, justicia moral, identidad femenina y busqueda de pertenencia. La novela defiende que Jane solo puede amar plenamente cuando conserva su libertad y su conciencia.';
  }

  if (hasAnyPhrase(normalized, ['personaje', 'personajes', 'protagonista'])) {
    return 'Personajes principales de Jane Eyre: Jane Eyre, Edward Rochester, Bertha Mason, Helen Burns, Bessie, la senora Reed, John Reed, Brocklehurst, la senora Temple, Adele Varens, Grace Poole, Blanche Ingram, la senora Fairfax, Diana Rivers, Mary Rivers y St. John Rivers.';
  }

  const characterNames = resolveCharacterNames(normalized);
  if (characterNames.length > 0) {
    const character = getStaticCharacterResponse(characterNames, normalized);
    if (character) return character;
  }

  return theme || null;
}

function getStaticEntryResponse(entries, normalized) {
  const entry = entries.find((item) => item.keywords.some((keyword) => hasPhrase(normalized, keyword)));
  return entry?.response || null;
}

function hasThemeIntent(normalized) {
  return hasAnyPhrase(normalized, [
    'tema',
    'temas',
    'amor',
    'moralidad',
    'clase social',
    'clases sociales',
    'desigualdad',
    'independencia',
    'religion',
    'espiritualidad',
    'justicia',
  ]);
}

function getStaticCharacterResponse(names, normalized) {
  for (const name of names) {
    if (name === 'Jane Eyre' && !isJaneIdentityQuestion(normalized)) {
      continue;
    }

    const response = STATIC_CHARACTER_RESPONSES.get(name);
    if (response) {
      return response;
    }
  }

  return null;
}

function isJaneIdentityQuestion(normalized) {
  if (normalized === 'jane' || normalized === 'jane eyre') return true;

  return hasAnyPhrase(normalized, [
    'quien es jane',
    'quien es jane eyre',
    'quien era jane',
    'quien era jane eyre',
    'personaje jane',
    'personaje de jane',
    'protagonista jane',
    'describe a jane',
    'hablame de jane',
  ]);
}

async function searchKnowledge(message, normalized) {
  if (isAuthorQuestion(normalized)) {
    return await getAuthorResponse();
  }

  if (hasPhrase(normalized, 'jane') && hasPhrase(normalized, 'rochester')) {
    return getJaneRochesterResponse();
  }

  const chapterRange = extractChapterRange(normalized);
  if (chapterRange) {
    return getStaticChapterRangeResponse(chapterRange.start, chapterRange.end);
  }

  const chapterNumber = extractChapterNumber(normalized);
  if (chapterNumber !== null) {
    return await getChapterResponse(chapterNumber);
  }

  const staticGlossary = getStaticGlossaryResponse(normalized);
  if (staticGlossary && (hasGlossaryIntent(normalized) || hasStaticGlossaryTerm(normalized))) {
    return staticGlossary;
  }

  const staticSummary = getStaticSummaryResponse(normalized);
  if (staticSummary) {
    return staticSummary;
  }

  const staticResponse = getStaticKnowledgeResponse(normalized);
  if (staticResponse) {
    return staticResponse;
  }

  const characterNames = resolveCharacterNames(normalized);
  if (characterNames.length > 0) {
    const staticCharacter = getStaticCharacterResponse(characterNames, normalized);
    if (staticCharacter) {
      return staticCharacter;
    }

    const character = await firstCharacterByName(characterNames);
    if (character) {
      return formatCharacter(character);
    }
  }

  const terms = normalizeText(message)
    .split(' ')
    .filter((term) => term.length >= 4 && !STOP_WORDS.has(term))
    .slice(0, 4);

  if (terms.length === 0) {
    return null;
  }

  if (!hasJaneEyreScopeSignal(normalized)) {
    return null;
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
    try {
      const glossary = await firstLike('glossary', ['concept', 'definition'], terms);
      if (glossary) {
        return `${glossary.concept}: ${cleanText(glossary.definition, 650)}`;
      }
    } catch (error) {
      if (!isKnowledgeLookupError(error)) {
        throw error;
      }
    }

    return getStaticGlossaryResponse(normalized);
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
    try {
      const summary = await firstLike('summaries', ['chapter', 'tipo', 'contenido'], terms);
      if (summary) {
        return `${summary.chapter || 'Resumen'}: ${cleanText(summary.contenido, 1400)}`;
      }
    } catch (error) {
      if (!isKnowledgeLookupError(error)) {
        throw error;
      }
    }

    return getStaticSummaryResponse(normalized);
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

function isAuthorQuestion(normalized) {
  return hasAnyPhrase(normalized, [
    'autor',
    'autora',
    'quien escribio',
    'quien es el autor',
    'quien es la autora',
  ]);
}

async function getAuthorResponse() {
  try {
    if (!await tableExists('works')) {
      return 'Jane Eyre fue escrita por Charlotte Bronte.';
    }

    const rows = await query('SELECT autor FROM works WHERE id = 1 LIMIT 1');
    const author = cleanText(rows[0]?.autor || 'Charlotte Bronte', 120);

    return `Jane Eyre fue escrita por ${author}.`;
  } catch (error) {
    if (!isKnowledgeLookupError(error)) {
      throw error;
    }

    return 'Jane Eyre fue escrita por Charlotte Bronte.';
  }
}

function extractChapterNumber(normalized) {
  const tokens = normalized.split(' ').filter(Boolean);

  for (let index = 0; index < tokens.length; index += 1) {
    if (!isChapterMarker(tokens[index])) continue;

    const nearby = tokens.slice(index + 1, index + 4);
    for (const token of nearby) {
      const chapter = parseChapterToken(token);
      if (chapter !== null) return chapter;
    }
  }

  if (!hasAnyPhrase(normalized, ['resumen', 'capitulo', 'chapter'])) {
    return null;
  }

  for (const token of tokens) {
    const chapter = parseChapterToken(token, false);
    if (chapter !== null) return chapter;
  }

  return null;
}

const CHAPTER_MARKERS = new Set([
  'capitulo',
  'capitulos',
  'cap',
  'chapter',
  'chapters',
  'cpaitlu',
  'cpaitulo',
  'capitlu',
  'capitlo',
  'capiutlo',
  'captulo',
  'capituloo',
]);

function isChapterMarker(token) {
  if (CHAPTER_MARKERS.has(token)) return true;
  if (token.startsWith('capit') && token.length >= 5 && token.length <= 10) return true;

  return false;
}

function parseChapterToken(token, allowRoman = true) {
  const numeric = Number.parseInt(token, 10);
  if (Number.isInteger(numeric) && String(numeric) === token && numeric >= 1 && numeric <= 38) {
    return numeric;
  }

  if (!allowRoman) {
    return null;
  }

  const roman = ROMAN_CHAPTERS[token];
  return roman || null;
}

const ROMAN_CHAPTERS = {
  i: 1,
  ii: 2,
  iii: 3,
  iv: 4,
  v: 5,
  vi: 6,
  vii: 7,
  viii: 8,
  ix: 9,
  x: 10,
  xi: 11,
  xii: 12,
  xiii: 13,
  xiv: 14,
  xv: 15,
  xvi: 16,
  xvii: 17,
  xviii: 18,
  xix: 19,
  xx: 20,
  xxi: 21,
  xxii: 22,
  xxiii: 23,
  xxiv: 24,
  xxv: 25,
  xxvi: 26,
  xxvii: 27,
  xxviii: 28,
  xxix: 29,
  xxx: 30,
  xxxi: 31,
  xxxii: 32,
  xxxiii: 33,
  xxxiv: 34,
  xxxv: 35,
  xxxvi: 36,
  xxxvii: 37,
  xxxviii: 38,
};

async function getChapterResponse(chapterNumber) {
  try {
    if (!await tableExists('summaries')) {
      return getStaticChapterResponse(chapterNumber);
    }

    const summaryRows = await query(
      'SELECT chapter, contenido FROM summaries WHERE work_id = 1 AND chapter::text = $1 LIMIT 1',
      [String(chapterNumber)],
    );

    if (summaryRows.length === 0) {
      return getStaticChapterResponse(chapterNumber);
    }

    const parts = [
      `Capitulo ${chapterNumber}`,
      '',
      cleanText(summaryRows[0].contenido, 1600),
    ];

    if (await tableExists('blocks')) {
      const blockRows = await query(
        'SELECT concepto_clave, nota_chatbot FROM blocks WHERE work_id = 1 AND titulo ILIKE $1 LIMIT 1',
        [`%Capítulo ${chapterNumber}%`],
      );
      const block = blockRows[0];

      if (block?.concepto_clave) {
        parts.push('', `Idea clave: ${cleanText(block.concepto_clave, 220)}`);
      }

      if (block?.nota_chatbot) {
        parts.push(`Lectura guiada: ${cleanText(block.nota_chatbot, 260)}`);
      }
    }

    return parts.join('\n');
  } catch (error) {
    if (!isKnowledgeLookupError(error)) {
      throw error;
    }

    return getStaticChapterResponse(chapterNumber);
  }
}

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
